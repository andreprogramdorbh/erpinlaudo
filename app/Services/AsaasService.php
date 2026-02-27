<?php

namespace App\Services;

use App\Core\Logger;
use App\Core\Audit\AuditLogger;

/**
 * AsaasService — integração completa com a API Asaas v3.
 *
 * Métodos de cobrança suportados:
 *  - PIX       → criarPix()      → getPixQrCode()
 *  - Boleto    → criarBoleto()   → getBoletoUrl()
 *  - Checkout  → criarCheckout() → getLinkPagamento()  (cliente escolhe o meio no Asaas)
 *
 * Log dedicado: storage/logs/asaas.log
 *  - Toda requisição enviada e resposta recebida é registrada
 *  - Erros da API são registrados com detalhes completos
 *  - Facilita diagnóstico sem precisar vasculhar logs gerais
 */
class AsaasService
{
    /** Valor mínimo aceito pelo Asaas para cobranças UNDEFINED (Checkout) */
    public const VALOR_MINIMO_CHECKOUT = 5.00;

    /** Valor mínimo aceito pelo Asaas para cobranças PIX */
    public const VALOR_MINIMO_PIX = 0.01;

    /** Valor mínimo aceito pelo Asaas para cobranças Boleto */
    public const VALOR_MINIMO_BOLETO = 5.00;

    private string $apiKey;
    private string $baseUrl;
    private string $environment;
    private Logger $logger;

    public function __construct(?string $apiKey = null, ?string $environment = null)
    {
        // Dotenv::createImmutable() popula $_ENV e $_SERVER, NÃO getenv()
        $this->apiKey = $apiKey
            ?? $_ENV['ASAAS_API_KEY']
            ?? $_SERVER['ASAAS_API_KEY']
            ?? (string) getenv('ASAAS_API_KEY')
            ?? '';

        $this->environment = $environment
            ?? $_ENV['ASAAS_ENVIRONMENT']
            ?? $_SERVER['ASAAS_ENVIRONMENT']
            ?? (string) getenv('ASAAS_ENVIRONMENT')
            ?? 'sandbox';

        $this->baseUrl = ($this->environment === 'production')
            ? 'https://api.asaas.com/v3'
            : 'https://sandbox.asaas.com/api/v3';

        $this->logger = new Logger();

        if (empty($this->apiKey)) {
            throw new \RuntimeException(
                'Asaas API Key não configurada. Defina ASAAS_API_KEY no .env'
            );
        }
    }

    // ---------------------------------------------------------------
    // COBRANÇAS
    // ---------------------------------------------------------------

    /**
     * Cria uma cobrança genérica no Asaas.
     * O campo 'billingType' deve ser definido antes de chamar este método.
     */
    public function criarCobranca(array $dados): array
    {
        // Validação de valor mínimo por tipo de cobrança
        $billingType = $dados['billingType'] ?? 'UNDEFINED';
        $valor       = (float) ($dados['value'] ?? 0);
        $this->validarValorMinimo($valor, $billingType);

        try {
            $response = $this->makeRequest('POST', '/payments', $dados);

            AuditLogger::log('asaas_payment_created', [
                'payment_id'  => $response['id'] ?? null,
                'customer_id' => $dados['customer'] ?? null,
                'value'       => $dados['value'] ?? 0,
                'dueDate'     => $dados['dueDate'] ?? null,
                'billingType' => $dados['billingType'] ?? null,
            ]);

            return $response;
        } catch (\Exception $e) {
            AuditLogger::log('asaas_payment_creation_failed', [
                'error'       => $e->getMessage(),
                'customer_id' => $dados['customer'] ?? null,
                'value'       => $dados['value'] ?? 0,
            ]);
            throw $e;
        }
    }

    /**
     * Cria cobrança PIX — retorna payment_id para buscar QR Code depois.
     */
    public function criarPix(array $dados): array
    {
        $dados['billingType'] = 'PIX';
        return $this->criarCobranca($dados);
    }

    /**
     * Cria cobrança Boleto Bancário.
     */
    public function criarBoleto(array $dados): array
    {
        $dados['billingType'] = 'BOLETO';
        return $this->criarCobranca($dados);
    }

    /**
     * Cria cobrança Checkout (UNDEFINED) — cliente escolhe o meio no Asaas.
     */
    public function criarCheckout(array $dados): array
    {
        $dados['billingType'] = 'UNDEFINED';
        return $this->criarCobranca($dados);
    }

    // ---------------------------------------------------------------
    // LINKS E QR CODES
    // ---------------------------------------------------------------

    /**
     * Retorna o link de checkout público (invoiceUrl) de uma cobrança.
     * Funciona para todos os tipos: PIX, Boleto e Checkout.
     */
    public function getLinkPagamento(string $paymentId): ?string
    {
        try {
            $response = $this->makeRequest('GET', "/payments/{$paymentId}");
            // invoiceUrl é o link de checkout público do Asaas
            return $response['invoiceUrl']
                ?? $response['bankSlipUrl']
                ?? null;
        } catch (\Exception $e) {
            $this->logAsaas('error', 'Erro ao obter link de pagamento', [
                'payment_id' => $paymentId,
                'error'      => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Retorna o QR Code PIX de uma cobrança.
     */
    public function getPixQrCode(string $paymentId): ?array
    {
        try {
            $response = $this->makeRequest('GET', "/payments/{$paymentId}/pixQrCode");
            return [
                'encodedImage'   => $response['encodedImage'] ?? null,
                'payload'        => $response['payload'] ?? null,
                'expirationDate' => $response['expirationDate'] ?? null,
            ];
        } catch (\Exception $e) {
            $this->logAsaas('error', 'Erro ao obter QR Code PIX', [
                'payment_id' => $paymentId,
                'error'      => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Retorna a URL do boleto bancário.
     */
    public function getBoletoUrl(string $paymentId): ?string
    {
        try {
            $response = $this->makeRequest('GET', "/payments/{$paymentId}");
            return $response['bankSlipUrl'] ?? null;
        } catch (\Exception $e) {
            $this->logAsaas('error', 'Erro ao obter URL do boleto', [
                'payment_id' => $paymentId,
                'error'      => $e->getMessage(),
            ]);
            return null;
        }
    }

    // ---------------------------------------------------------------
    // CLIENTES
    // ---------------------------------------------------------------

    /**
     * Cria um cliente no Asaas.
     */
    public function criarCliente(array $dados): array
    {
        try {
            $response = $this->makeRequest('POST', '/customers', $dados);

            AuditLogger::log('asaas_customer_created', [
                'customer_id' => $response['id'] ?? null,
                'name'        => $dados['name'] ?? null,
                'email'       => $dados['email'] ?? null,
                'cpfCnpj'     => $dados['cpfCnpj'] ?? null,
            ]);

            return $response;
        } catch (\Exception $e) {
            AuditLogger::log('asaas_customer_creation_failed', [
                'error' => $e->getMessage(),
                'name'  => $dados['name'] ?? null,
                'email' => $dados['email'] ?? null,
            ]);
            throw $e;
        }
    }

    /**
     * Busca cliente por CPF/CNPJ ou e-mail.
     */
    public function buscarCliente(?string $cpfCnpj = null, ?string $email = null): ?array
    {
        try {
            $params = [];
            if ($cpfCnpj) {
                $params['cpfCnpj'] = $cpfCnpj;
            }
            if ($email) {
                $params['email'] = $email;
            }

            $response = $this->makeRequest('GET', '/customers?' . http_build_query($params));
            return $response['data'][0] ?? null;
        } catch (\Exception $e) {
            $this->logAsaas('error', 'Erro ao buscar cliente Asaas', [
                'cpfCnpj' => $cpfCnpj,
                'email'   => $email,
                'error'   => $e->getMessage(),
            ]);
            return null;
        }
    }

    // ---------------------------------------------------------------
    // ASSINATURAS
    // ---------------------------------------------------------------

    /**
     * Cria uma assinatura recorrente no Asaas.
     */
    public function criarAssinatura(array $dados): array
    {
        try {
            $response = $this->makeRequest('POST', '/subscriptions', $dados);

            AuditLogger::log('asaas_subscription_created', [
                'subscription_id' => $response['id'] ?? null,
                'customer_id'     => $dados['customer'] ?? null,
                'value'           => $dados['value'] ?? 0,
                'cycle'           => $dados['cycle'] ?? null,
            ]);

            return $response;
        } catch (\Exception $e) {
            AuditLogger::log('asaas_subscription_creation_failed', [
                'error'       => $e->getMessage(),
                'customer_id' => $dados['customer'] ?? null,
                'value'       => $dados['value'] ?? 0,
            ]);
            throw $e;
        }
    }

    // ---------------------------------------------------------------
    // STATUS E CANCELAMENTO
    // ---------------------------------------------------------------

    /**
     * Obtém o status atual de um pagamento.
     */
    public function getStatusPagamento(string $paymentId): ?string
    {
        try {
            $response = $this->makeRequest('GET', "/payments/{$paymentId}");
            return $response['status'] ?? null;
        } catch (\Exception $e) {
            $this->logAsaas('error', 'Erro ao obter status pagamento', [
                'payment_id' => $paymentId,
                'error'      => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Cancela uma cobrança.
     */
    public function cancelarCobranca(string $paymentId): bool
    {
        try {
            $this->makeRequest('DELETE', "/payments/{$paymentId}");

            AuditLogger::log('asaas_payment_cancelled', ['payment_id' => $paymentId]);
            return true;
        } catch (\Exception $e) {
            AuditLogger::log('asaas_payment_cancellation_failed', [
                'payment_id' => $paymentId,
                'error'      => $e->getMessage(),
            ]);
            return false;
        }
    }

    // ---------------------------------------------------------------
    // UTILITÁRIOS ESTÁTICOS
    // ---------------------------------------------------------------

    /**
     * Verifica se a chave Asaas está configurada no ambiente.
     */
    public static function isConfigured(): bool
    {
        $apiKey = $_ENV['ASAAS_API_KEY']
            ?? $_SERVER['ASAAS_API_KEY']
            ?? (string) getenv('ASAAS_API_KEY')
            ?? '';
        return !empty($apiKey);
    }

    /**
     * Remove caracteres não numéricos de CPF/CNPJ.
     */
    public static function formatarDocumento(string $documento): string
    {
        return preg_replace('/\D/', '', $documento);
    }

    /**
     * Mapeia status Asaas para status interno do ERP.
     * Valores válidos no ENUM contas_receber.status: aberta | recebida | cancelada
     */
    public static function mapearStatus(string $asaasStatus): string
    {
        return match ($asaasStatus) {
            'RECEIVED', 'CONFIRMED', 'RECEIVED_IN_CASH',
            'DUNNING_RECEIVED', 'CHARGEBACK_REVERSED'  => 'recebida',
            'REFUNDED', 'CHARGEBACK_REQUESTED',
            'CHARGEBACK_DISPUTE', 'DELETED'             => 'cancelada',
            default                                     => 'aberta',
        };
    }

    /**
     * Mapeia billingType Asaas para meio_pagamento interno.
     */
    public static function mapearMeioPagamento(string $billingType): string
    {
        return match ($billingType) {
            'BOLETO'      => 'boleto',
            'CREDIT_CARD' => 'cartao',
            'PIX'         => 'pix',
            'UNDEFINED'   => 'checkout',
            default       => 'outro',
        };
    }

    // ---------------------------------------------------------------
    // LOG DEDICADO ASAAS
    // ---------------------------------------------------------------

    /**
     * Grava uma entrada no log dedicado do Asaas: storage/logs/asaas.log
     *
     * Formato:
     * [2026-02-27 00:00:00] [ENV: production] [LEVEL: error] Mensagem | Context: {...}
     */
    public function logAsaas(string $level, string $message, array $context = []): void
    {
        $logDir  = __DIR__ . '/../../storage/logs';
        $logFile = $logDir . '/asaas.log';

        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $timestamp = date('Y-m-d H:i:s');
        $env       = strtoupper($this->environment);
        $ip        = $_SERVER['REMOTE_ADDR'] ?? '-';
        $uri       = $_SERVER['REQUEST_URI'] ?? '-';
        $level     = strtoupper($level);

        $line = "[{$timestamp}] [ENV: {$env}] [IP: {$ip}] [URI: {$uri}] [{$level}] {$message}";

        if (!empty($context)) {
            $line .= ' | ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $line .= "\n";

        file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
    }

    // ---------------------------------------------------------------
    // VALIDAÇÃO DE VALOR MÍNIMO
    // ---------------------------------------------------------------

    /**
     * Valida se o valor da cobrança atende ao mínimo exigido pelo Asaas.
     * Lança exceção com mensagem amigável se o valor for insuficiente.
     */
    private function validarValorMinimo(float $valor, string $billingType): void
    {
        $minimos = [
            'UNDEFINED' => self::VALOR_MINIMO_CHECKOUT,  // R$ 5,00
            'BOLETO'    => self::VALOR_MINIMO_BOLETO,    // R$ 5,00
            'PIX'       => self::VALOR_MINIMO_PIX,       // R$ 0,01
        ];

        $minimo = $minimos[$billingType] ?? self::VALOR_MINIMO_CHECKOUT;

        if ($valor < $minimo) {
            $minimoFormatado = number_format($minimo, 2, ',', '.');
            $valorFormatado  = number_format($valor, 2, ',', '.');
            $tipo            = match ($billingType) {
                'UNDEFINED' => 'Checkout (Pergunte ao Cliente)',
                'BOLETO'    => 'Boleto',
                'PIX'       => 'PIX',
                default     => $billingType,
            };

            $this->logAsaas('warning', 'Valor abaixo do mínimo exigido pelo Asaas', [
                'billingType'      => $billingType,
                'valor'            => $valor,
                'valor_minimo'     => $minimo,
            ]);

            throw new \InvalidArgumentException(
                "O valor R$ {$valorFormatado} é inferior ao mínimo de R$ {$minimoFormatado} " .
                "exigido pelo Asaas para cobranças do tipo {$tipo}."
            );
        }
    }

    // ---------------------------------------------------------------
    // HTTP INTERNO
    // ---------------------------------------------------------------

    private function makeRequest(string $method, string $endpoint, array $data = []): array
    {
        $url = $this->baseUrl . $endpoint;

        $headers = [
            'Content-Type: application/json',
            'access_token: ' . $this->apiKey,
            'User-Agent: ERP-InLaudo/1.0',
        ];

        // Log da requisição enviada
        $this->logAsaas('debug', "→ {$method} {$endpoint}", [
            'url'  => $url,
            'body' => !empty($data) ? $data : null,
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

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
            $this->logAsaas('error', "Falha cURL: {$method} {$endpoint}", [
                'curl_error' => $curlError,
            ]);
            throw new \RuntimeException('Falha na comunicação com API Asaas: ' . $curlError);
        }

        $responseData = json_decode($response, true);

        // Log da resposta recebida
        if ($httpCode >= 400) {
            $errorMessage = $responseData['errors'][0]['description']
                ?? $responseData['message']
                ?? "HTTP {$httpCode}";

            $this->logAsaas('error', "← {$httpCode} {$method} {$endpoint} | {$errorMessage}", [
                'http_code'    => $httpCode,
                'response'     => $responseData,
                'request_body' => !empty($data) ? $data : null,
            ]);

            throw new \RuntimeException("Asaas API Error ({$httpCode}): {$errorMessage}");
        }

        // Log de sucesso (apenas para POST/DELETE — GET é muito verboso)
        if ($method !== 'GET') {
            $this->logAsaas('info', "← {$httpCode} {$method} {$endpoint}", [
                'http_code' => $httpCode,
                'id'        => $responseData['id'] ?? null,
                'status'    => $responseData['status'] ?? null,
            ]);
        }

        return $responseData ?? [];
    }
}
