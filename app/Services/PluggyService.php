<?php

namespace App\Services;

/**
 * PluggyService
 * Integração com a API Pluggy para Open Finance Brasil.
 * Documentação: https://docs.pluggy.ai
 */
class PluggyService
{
    private string $clientId;
    private string $clientSecret;
    private string $baseUrl = 'https://api.pluggy.ai';
    private ?string $apiKey = null;

    public function __construct(string $clientId, string $clientSecret)
    {
        $this->clientId     = $clientId;
        $this->clientSecret = $clientSecret;
    }

    // ----------------------------------------------------------------
    // Autenticação
    // ----------------------------------------------------------------

    /**
     * Obtém ou renova a API Key da Pluggy.
     */
    public function getApiKey(): ?string
    {
        if ($this->apiKey) return $this->apiKey;

        $resp = $this->request('POST', '/auth', [
            'clientId'     => $this->clientId,
            'clientSecret' => $this->clientSecret,
        ], false);

        if (!empty($resp['apiKey'])) {
            $this->apiKey = $resp['apiKey'];
            return $this->apiKey;
        }

        return null;
    }

    // ----------------------------------------------------------------
    // Connect Token (para o widget Pluggy Connect)
    // ----------------------------------------------------------------

    /**
     * Gera um Connect Token para iniciar o fluxo de conexão no frontend.
     *
     * @param array $options ['itemId' => '...'] para reconexão
     */
    public function criarConnectToken(array $options = []): array
    {
        $body = [];
        if (!empty($options['itemId'])) {
            $body['itemId'] = $options['itemId'];
        }

        return $this->request('POST', '/connect_token', $body);
    }

    // ----------------------------------------------------------------
    // Items (conexões bancárias)
    // ----------------------------------------------------------------

    /**
     * Busca um item (conexão bancária) pelo ID.
     */
    public function getItem(string $itemId): array
    {
        return $this->request('GET', "/items/{$itemId}");
    }

    /**
     * Remove um item (desconecta a conta).
     */
    public function deleteItem(string $itemId): array
    {
        return $this->request('DELETE', "/items/{$itemId}");
    }

    /**
     * Atualiza (sincroniza) um item.
     */
    public function updateItem(string $itemId): array
    {
        return $this->request('PATCH', "/items/{$itemId}");
    }

    // ----------------------------------------------------------------
    // Accounts (contas bancárias)
    // ----------------------------------------------------------------

    /**
     * Lista todas as contas de um item.
     */
    public function getAccounts(string $itemId): array
    {
        return $this->request('GET', "/accounts?itemId={$itemId}");
    }

    /**
     * Busca uma conta específica.
     */
    public function getAccount(string $accountId): array
    {
        return $this->request('GET', "/accounts/{$accountId}");
    }

    // ----------------------------------------------------------------
    // Transactions (transações)
    // ----------------------------------------------------------------

    /**
     * Lista transações de uma conta com paginação e filtro de data.
     *
     * @param string $accountId
     * @param string|null $from  Data início (YYYY-MM-DD)
     * @param string|null $to    Data fim (YYYY-MM-DD)
     * @param int $pageSize
     * @param int $page
     */
    public function getTransactions(
        string $accountId,
        ?string $from = null,
        ?string $to = null,
        int $pageSize = 100,
        int $page = 1
    ): array {
        $params = [
            'accountId' => $accountId,
            'pageSize'  => $pageSize,
            'page'      => $page,
        ];

        if ($from) $params['from'] = $from;
        if ($to)   $params['to']   = $to;

        $query = http_build_query($params);
        return $this->request('GET', "/transactions?{$query}");
    }

    /**
     * Importa todas as transações de uma conta (com paginação automática).
     *
     * @param string $accountId
     * @param string|null $from Data início
     * @return array Lista de transações normalizadas
     */
    public function importarTransacoes(string $accountId, ?string $from = null): array
    {
        $transacoes = [];
        $page       = 1;
        $pageSize   = 100;

        do {
            $resp = $this->getTransactions($accountId, $from, null, $pageSize, $page);

            if (empty($resp['results'])) break;

            foreach ($resp['results'] as $t) {
                $transacoes[] = $this->normalizarTransacao($t);
            }

            $totalPages = ceil(($resp['total'] ?? 0) / $pageSize);
            $page++;
        } while ($page <= $totalPages && $page <= 50); // máx 50 páginas

        return $transacoes;
    }

    /**
     * Normaliza uma transação Pluggy para o formato interno do sistema.
     */
    private function normalizarTransacao(array $t): array
    {
        $valor = abs((float)($t['amount'] ?? 0));
        $tipo  = ($t['type'] ?? '') === 'CREDIT' ? 'credito' : 'debito';

        return [
            'fitid'             => $t['id'] ?? '',
            'tipo'              => $tipo,
            'valor'             => $valor,
            'data_movimentacao' => substr($t['date'] ?? date('Y-m-d'), 0, 10),
            'descricao'         => substr($t['description'] ?? '', 0, 255),
            'categoria'         => $t['category'] ?? '',
            'numero_documento'  => $t['paymentData']['paymentMethod'] ?? '',
            'origem'            => 'openfinance',
            'hash_transacao'    => md5(($t['id'] ?? '') . ($t['amount'] ?? '') . ($t['date'] ?? '')),
            'dados_extras'      => json_encode([
                'pluggy_id'    => $t['id'] ?? '',
                'merchant'     => $t['merchant']['name'] ?? '',
                'payment_data' => $t['paymentData'] ?? [],
            ]),
        ];
    }

    // ----------------------------------------------------------------
    // Connectors (instituições disponíveis)
    // ----------------------------------------------------------------

    /**
     * Lista todos os conectores (bancos) disponíveis.
     */
    public function getConnectors(string $search = ''): array
    {
        $query = $search ? '?name=' . urlencode($search) : '';
        return $this->request('GET', "/connectors{$query}");
    }

    // ----------------------------------------------------------------
    // HTTP Client
    // ----------------------------------------------------------------

    /**
     * Executa uma requisição HTTP para a API Pluggy.
     */
    private function request(string $method, string $endpoint, array $body = [], bool $auth = true): array
    {
        $url = $this->baseUrl . $endpoint;

        $headers = ['Content-Type: application/json'];

        if ($auth) {
            $apiKey = $this->getApiKey();
            if (!$apiKey) return ['error' => 'Falha na autenticação Pluggy.'];
            $headers[] = "X-API-KEY: {$apiKey}";
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        } elseif ($method === 'PATCH') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
            if (!empty($body)) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            $this->log("cURL error: {$curlError}");
            return ['error' => $curlError];
        }

        $data = json_decode($response, true);

        if ($httpCode >= 400) {
            $msg = $data['message'] ?? $data['error'] ?? "HTTP {$httpCode}";
            $this->log("API error [{$httpCode}] {$endpoint}: {$msg}");
            return ['error' => $msg, 'http_code' => $httpCode];
        }

        return $data ?? [];
    }

    /**
     * Registra logs de erro.
     */
    private function log(string $msg): void
    {
        $logFile = dirname(__DIR__, 2) . '/storage/logs/pluggy.log';
        $line = '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
        @file_put_contents($logFile, $line, FILE_APPEND);
    }
}
