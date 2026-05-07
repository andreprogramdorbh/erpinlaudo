<?php

namespace App\Services;

use App\Core\Logger;
use App\Core\Audit\AuditLogger;

/**
 * CoraService — integração completa com a API Cora v2 (Integração Direta).
 *
 * Autenticação: OAuth2 Client Credentials com mTLS (certificado .pem + chave .key).
 * O token é obtido via POST em matls-clients.api.cora.com.br/token e válido por 24h.
 *
 * Métodos suportados:
 *  - Boleto Registrado → emitirBoleto()
 *  - Pix               → emitirPix()
 *  - Consulta          → consultarFatura()
 *  - Cancelamento      → cancelarFatura()
 *  - Webhook           → registrarWebhook()
 *
 * Log dedicado: storage/logs/cora.log
 */
class CoraService
{
    /** Valor mínimo aceito pela Cora para boletos */
    public const VALOR_MINIMO_BOLETO = 5.00;

    /** Valor mínimo para Pix */
    public const VALOR_MINIMO_PIX = 0.01;

    private string $clientId;
    private string $certPath;
    private string $keyPath;
    private string $environment;
    private string $authBaseUrl;
    private string $apiBaseUrl;
    private ?string $accessToken = null;
    private Logger $logger;

    public function __construct(
        string $clientId,
        string $certPath,
        string $keyPath,
        string $environment = 'production'
    ) {
        $this->clientId    = $clientId;
        $this->certPath    = $certPath;
        $this->keyPath     = $keyPath;
        $this->environment = $environment;

        if ($environment === 'production') {
            $this->authBaseUrl = 'https://matls-clients.api.cora.com.br';
            $this->apiBaseUrl  = 'https://api.cora.com.br';
        } else {
            $this->authBaseUrl = 'https://matls-clients.api.stage.cora.com.br';
            $this->apiBaseUrl  = 'https://api.stage.cora.com.br';
        }

        $this->logger = new Logger();

        if (empty($this->clientId)) {
            throw new \RuntimeException('Cora: client_id não configurado.');
        }
        if (!file_exists($this->certPath)) {
            throw new \RuntimeException("Cora: certificado não encontrado em {$this->certPath}");
        }
        if (!file_exists($this->keyPath)) {
            throw new \RuntimeException("Cora: chave privada não encontrada em {$this->keyPath}");
        }
    }

    // ---------------------------------------------------------------
    // AUTENTICAÇÃO
    // ---------------------------------------------------------------

    /**
     * Obtém o access_token via OAuth2 Client Credentials + mTLS.
     * O token é cacheado em memória durante a requisição (válido 24h na Cora).
     */
    public function getAccessToken(): string
    {
        if ($this->accessToken !== null) {
            return $this->accessToken;
        }

        $url  = $this->authBaseUrl . '/token';
        $body = http_build_query([
            'grant_type' => 'client_credentials',
            'client_id'  => $this->clientId,
        ]);

        $this->logCora('debug', '→ POST /token (autenticação mTLS)');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
        ]);
        // mTLS — certificado do cliente
        curl_setopt($ch, CURLOPT_SSLCERT, $this->certPath);
        curl_setopt($ch, CURLOPT_SSLKEY, $this->keyPath);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        $response  = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new \RuntimeException('Cora: falha cURL na autenticação: ' . $curlError);
        }

        $data = json_decode($response, true);

        if ($httpCode !== 200 || empty($data['access_token'])) {
            $msg = $data['error_description'] ?? $data['error'] ?? "HTTP {$httpCode}";
            $this->logCora('error', "← {$httpCode} /token | {$msg}", ['response' => $data]);
            throw new \RuntimeException("Cora: falha na autenticação: {$msg}");
        }

        $this->accessToken = $data['access_token'];
        $this->logCora('info', '← 200 /token | token obtido com sucesso', [
            'expires_in' => $data['expires_in'] ?? null,
        ]);

        return $this->accessToken;
    }

    // ---------------------------------------------------------------
    // BOLETO
    // ---------------------------------------------------------------

    /**
     * Emite um boleto registrado na Cora.
     *
     * @param array $dados {
     *   'code'           => string (id interno do sistema),
     *   'customer'       => array (name, email, document, address),
     *   'services'       => array de [{name, description, amount (centavos)}],
     *   'due_date'       => string YYYY-MM-DD,
     *   'fine_rate'      => float (% multa, ex: 2.00),
     *   'interest_rate'  => float (% juros ao mês, ex: 1.00),
     *   'notify_email'   => bool,
     * }
     * @return array Resposta completa da Cora (id, status, payment_options.bank_slip, pix, etc.)
     */
    public function emitirBoleto(array $dados): array
    {
        $valor = 0;
        foreach ($dados['services'] ?? [] as $s) {
            $valor += (int) ($s['amount'] ?? 0);
        }
        if ($valor < (self::VALOR_MINIMO_BOLETO * 100)) {
            throw new \InvalidArgumentException(
                sprintf('Valor mínimo para boleto Cora é R$ %.2f.', self::VALOR_MINIMO_BOLETO)
            );
        }

        $payload = $this->montarPayloadFatura($dados);

        $this->logCora('info', '→ POST /v2/invoices (boleto)', [
            'code'     => $dados['code'] ?? null,
            'valor_rs' => number_format($valor / 100, 2, ',', '.'),
            'due_date' => $dados['due_date'] ?? null,
        ]);

        $response = $this->makeRequest('POST', '/v2/invoices', $payload);

        AuditLogger::log('cora_boleto_emitido', [
            'cora_invoice_id' => $response['id'] ?? null,
            'code'            => $dados['code'] ?? null,
            'valor_centavos'  => $valor,
            'due_date'        => $dados['due_date'] ?? null,
            'status'          => $response['status'] ?? null,
        ]);

        return $response;
    }

    /**
     * Emite um QR Code Pix via fatura Cora.
     * A Cora retorna o Pix junto com o boleto na mesma fatura (payment_options.pix).
     */
    public function emitirPix(array $dados): array
    {
        // Pix é emitido como fatura normal; o QR Code vem no campo pix da resposta
        return $this->emitirBoleto($dados);
    }

    // ---------------------------------------------------------------
    // CONSULTA E CANCELAMENTO
    // ---------------------------------------------------------------

    /**
     * Consulta os detalhes de uma fatura pelo ID Cora.
     */
    public function consultarFatura(string $invoiceId): array
    {
        return $this->makeRequest('GET', "/v2/invoices/{$invoiceId}");
    }

    /**
     * Consulta a lista de faturas (paginada).
     */
    public function listarFaturas(int $page = 1, int $size = 20): array
    {
        return $this->makeRequest('GET', "/v2/invoices?page={$page}&size={$size}");
    }

    /**
     * Cancela uma fatura.
     */
    public function cancelarFatura(string $invoiceId): bool
    {
        try {
            $this->makeRequest('DELETE', "/v2/invoices/{$invoiceId}", [], [
                'Idempotency-Key: ' . $this->gerarIdempotencyKey(),
            ]);

            AuditLogger::log('cora_fatura_cancelada', ['cora_invoice_id' => $invoiceId]);
            return true;
        } catch (\Exception $e) {
            $this->logCora('error', 'Erro ao cancelar fatura Cora', [
                'cora_invoice_id' => $invoiceId,
                'error'           => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Obtém a URL do boleto (linha digitável / PDF).
     */
    public function getBoletoUrl(string $invoiceId): ?string
    {
        try {
            $fatura = $this->consultarFatura($invoiceId);
            return $fatura['payment_options']['bank_slip']['document_url']
                ?? $fatura['payment_options']['bank_slip']['barcode']
                ?? null;
        } catch (\Exception $e) {
            $this->logCora('error', 'Erro ao obter URL do boleto Cora', [
                'cora_invoice_id' => $invoiceId,
                'error'           => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Obtém a linha digitável do boleto.
     */
    public function getLinhaDigitavel(string $invoiceId): ?string
    {
        try {
            $fatura = $this->consultarFatura($invoiceId);
            return $fatura['payment_options']['bank_slip']['digitable_line'] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Obtém o QR Code Pix de uma fatura.
     */
    public function getPixQrCode(string $invoiceId): ?array
    {
        try {
            $fatura = $this->consultarFatura($invoiceId);
            $pix    = $fatura['pix'] ?? null;
            if (empty($pix)) {
                return null;
            }
            return [
                'qr_code'     => $pix['qr_code'] ?? null,
                'qr_code_url' => $pix['qr_code_url'] ?? null,
            ];
        } catch (\Exception $e) {
            $this->logCora('error', 'Erro ao obter QR Code Pix Cora', [
                'cora_invoice_id' => $invoiceId,
                'error'           => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Obtém o status atual de uma fatura.
     * Retorna: PENDING, PAID, OVERDUE, CANCELED, IN_PAYMENT
     */
    public function getStatusFatura(string $invoiceId): ?string
    {
        try {
            $fatura = $this->consultarFatura($invoiceId);
            return $fatura['status'] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }

    // ---------------------------------------------------------------
    // WEBHOOK
    // ---------------------------------------------------------------

    /**
     * Registra um endpoint de webhook na Cora.
     */
    public function registrarWebhook(string $url, array $events = []): array
    {
        if (empty($events)) {
            $events = ['invoice.paid', 'invoice.overdue', 'invoice.canceled'];
        }

        return $this->makeRequest('POST', '/v1/notifications', [
            'url'    => $url,
            'events' => $events,
        ]);
    }

    /**
     * Lista os webhooks registrados.
     */
    public function listarWebhooks(): array
    {
        return $this->makeRequest('GET', '/v1/notifications');
    }

    // ---------------------------------------------------------------
    // MAPEAMENTO DE STATUS
    // ---------------------------------------------------------------

    /**
     * Mapeia o status da Cora para o status interno do ERP.
     */
    public static function mapearStatus(string $coraStatus): string
    {
        return match (strtoupper($coraStatus)) {
            'PAID', 'IN_PAYMENT' => 'recebida',
            'CANCELED'           => 'cancelada',
            default              => 'aberta',
        };
    }

    /**
     * Mapeia o status da Cora para label em português.
     */
    public static function mapearStatusLabel(string $coraStatus): string
    {
        return match (strtoupper($coraStatus)) {
            'PENDING'    => 'Aguardando Pagamento',
            'PAID'       => 'Pago',
            'OVERDUE'    => 'Vencido',
            'CANCELED'   => 'Cancelado',
            'IN_PAYMENT' => 'Em Liquidação',
            default      => $coraStatus,
        };
    }

    // ---------------------------------------------------------------
    // HELPERS INTERNOS
    // ---------------------------------------------------------------

    /**
     * Monta o payload completo da fatura para a API Cora.
     */
    private function montarPayloadFatura(array $dados): array
    {
        $payload = [
            'code'     => $dados['code'] ?? null,
            'customer' => $dados['customer'],
            'services' => $dados['services'],
            'payment_terms' => [
                'due_date' => $dados['due_date'],
            ],
        ];

        // Multa
        if (!empty($dados['fine_rate'])) {
            $payload['payment_terms']['fine'] = [
                'rate' => (float) $dados['fine_rate'],
            ];
        }

        // Juros
        if (!empty($dados['interest_rate'])) {
            $payload['payment_terms']['interest'] = [
                'rate' => (float) $dados['interest_rate'],
            ];
        }

        // Notificação por e-mail
        if (!empty($dados['notify_email']) && !empty($dados['customer']['email'])) {
            $payload['notification'] = [
                'channels' => [
                    [
                        'channel'   => 'EMAIL',
                        'notify_on' => ['DUE_DATE', 'OVERDUE'],
                    ],
                ],
            ];
        }

        return $payload;
    }

    /**
     * Gera um UUID v4 para o header Idempotency-Key.
     */
    public function gerarIdempotencyKey(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    /**
     * Executa uma requisição autenticada para a API Cora.
     */
    private function makeRequest(
        string $method,
        string $endpoint,
        array $data = [],
        array $extraHeaders = []
    ): array {
        $token = $this->getAccessToken();
        $url   = $this->apiBaseUrl . $endpoint;

        $headers = array_merge([
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token,
        ], $extraHeaders);

        // Adiciona Idempotency-Key em POST e DELETE
        if (in_array($method, ['POST', 'DELETE'], true)) {
            $headers[] = 'Idempotency-Key: ' . $this->gerarIdempotencyKey();
        }

        $this->logCora('debug', "→ {$method} {$endpoint}", [
            'url'  => $url,
            'body' => !empty($data) ? $data : null,
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        // mTLS
        curl_setopt($ch, CURLOPT_SSLCERT, $this->certPath);
        curl_setopt($ch, CURLOPT_SSLKEY, $this->keyPath);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }

        $response  = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            $this->logCora('error', "Falha cURL: {$method} {$endpoint}", [
                'curl_error' => $curlError,
            ]);
            throw new \RuntimeException('Cora: falha na comunicação: ' . $curlError);
        }

        $responseData = json_decode($response, true) ?? [];

        if ($httpCode >= 400) {
            $errorMsg = $responseData['message']
                ?? $responseData['error']
                ?? "HTTP {$httpCode}";
            $this->logCora('error', "← {$httpCode} {$method} {$endpoint} | {$errorMsg}", [
                'http_code'    => $httpCode,
                'response'     => $responseData,
                'request_body' => !empty($data) ? $data : null,
            ]);
            throw new \RuntimeException("Cora API Error ({$httpCode}): {$errorMsg}");
        }

        if ($method !== 'GET') {
            $this->logCora('info', "← {$httpCode} {$method} {$endpoint}", [
                'http_code' => $httpCode,
                'id'        => $responseData['id'] ?? null,
                'status'    => $responseData['status'] ?? null,
            ]);
        }

        return $responseData;
    }

    /**
     * Registra log dedicado no arquivo storage/logs/cora.log.
     */
    public function logCora(string $level, string $message, array $context = []): void
    {
        $logPath = dirname(__DIR__, 2) . '/storage/logs/cora.log';
        $line    = sprintf(
            "[%s] [%s] %s%s\n",
            date('Y-m-d H:i:s'),
            strtoupper($level),
            $message,
            !empty($context) ? ' | ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : ''
        );

        // Rotação simples: se o arquivo ultrapassar 5 MB, renomeia para .old
        if (file_exists($logPath) && filesize($logPath) > 5 * 1024 * 1024) {
            rename($logPath, $logPath . '.old');
        }

        file_put_contents($logPath, $line, FILE_APPEND | LOCK_EX);
    }

    /**
     * Formata CPF/CNPJ removendo caracteres não numéricos.
     */
    public static function formatarDocumento(string $doc): string
    {
        return preg_replace('/\D/', '', $doc);
    }
}
