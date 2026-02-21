<?php

namespace App\Services;

use App\Core\Logger;
use App\Core\Audit\AuditLogger;

class AsaasService
{
    private string $apiKey;
    private string $baseUrl;
    private Logger $logger;

    public function __construct()
    {
        $this->apiKey = getenv('ASAAS_API_KEY') ?: $_ENV['ASAAS_API_KEY'] ?? '';
        $environment = getenv('ASAAS_ENVIRONMENT') ?: $_ENV['ASAAS_ENVIRONMENT'] ?? 'sandbox';
        
        $this->baseUrl = $environment === 'production' 
            ? 'https://api.asaas.com/v3' 
            : 'https://sandbox.asaas.com/api/v3';
            
        $this->logger = new Logger();
        
        if (empty($this->apiKey)) {
            throw new \RuntimeException('Asaas API Key não configurada. Defina ASAAS_API_KEY no .env');
        }
    }

    /**
     * Cria uma cobrança no Asaas
     */
    public function criarCobranca(array $dados): array
    {
        try {
            $endpoint = '/payments';
            $response = $this->makeRequest('POST', $endpoint, $dados);
            
            AuditLogger::log('asaas_payment_created', [
                'payment_id' => $response['id'] ?? null,
                'customer_id' => $dados['customer'] ?? null,
                'value' => $dados['value'] ?? 0,
                'dueDate' => $dados['dueDate'] ?? null,
                'billingType' => $dados['billingType'] ?? null
            ]);

            return $response;
        } catch (\Exception $e) {
            AuditLogger::log('asaas_payment_creation_failed', [
                'error' => $e->getMessage(),
                'customer_id' => $dados['customer'] ?? null,
                'value' => $dados['value'] ?? 0
            ]);
            throw $e;
        }
    }

    /**
     * Obtém o link de pagamento de uma cobrança
     */
    public function getLinkPagamento(string $paymentId): ?string
    {
        try {
            $endpoint = "/payments/{$paymentId}/paymentLink";
            $response = $this->makeRequest('GET', $endpoint);
            
            return $response['paymentUrl'] ?? null;
        } catch (\Exception $e) {
            $this->logger->error('Erro ao obter link de pagamento: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Cria um cliente no Asaas
     */
    public function criarCliente(array $dados): array
    {
        try {
            $endpoint = '/customers';
            $response = $this->makeRequest('POST', $endpoint, $dados);
            
            AuditLogger::log('asaas_customer_created', [
                'customer_id' => $response['id'] ?? null,
                'name' => $dados['name'] ?? null,
                'email' => $dados['email'] ?? null,
                'cpfCnpj' => $dados['cpfCnpj'] ?? null
            ]);

            return $response;
        } catch (\Exception $e) {
            AuditLogger::log('asaas_customer_creation_failed', [
                'error' => $e->getMessage(),
                'name' => $dados['name'] ?? null,
                'email' => $dados['email'] ?? null
            ]);
            throw $e;
        }
    }

    /**
     * Busca cliente por CPF/CNPJ ou e-mail
     */
    public function buscarCliente(string $cpfCnpj = null, string $email = null): ?array
    {
        try {
            $params = [];
            if ($cpfCnpj) {
                $params['cpfCnpj'] = $cpfCnpj;
            }
            if ($email) {
                $params['email'] = $email;
            }

            $endpoint = '/customers?' . http_build_query($params);
            $response = $this->makeRequest('GET', $endpoint);
            
            return $response['data'][0] ?? null;
        } catch (\Exception $e) {
            $this->logger->error('Erro ao buscar cliente Asaas: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Cria uma assinatura recorrente
     */
    public function criarAssinatura(array $dados): array
    {
        try {
            $endpoint = '/subscriptions';
            $response = $this->makeRequest('POST', $endpoint, $dados);
            
            AuditLogger::log('asaas_subscription_created', [
                'subscription_id' => $response['id'] ?? null,
                'customer_id' => $dados['customer'] ?? null,
                'value' => $dados['value'] ?? 0,
                'cycle' => $dados['cycle'] ?? null
            ]);

            return $response;
        } catch (\Exception $e) {
            AuditLogger::log('asaas_subscription_creation_failed', [
                'error' => $e->getMessage(),
                'customer_id' => $dados['customer'] ?? null,
                'value' => $dados['value'] ?? 0
            ]);
            throw $e;
        }
    }

    /**
     * Obtém status de um pagamento
     */
    public function getStatusPagamento(string $paymentId): ?string
    {
        try {
            $endpoint = "/payments/{$paymentId}";
            $response = $this->makeRequest('GET', $endpoint);
            
            return $response['status'] ?? null;
        } catch (\Exception $e) {
            $this->logger->error('Erro ao obter status pagamento: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Cancela uma cobrança
     */
    public function cancelarCobranca(string $paymentId): bool
    {
        try {
            $endpoint = "/payments/{$paymentId}";
            $response = $this->makeRequest('DELETE', $endpoint);
            
            AuditLogger::log('asaas_payment_cancelled', [
                'payment_id' => $paymentId
            ]);

            return true;
        } catch (\Exception $e) {
            AuditLogger::log('asaas_payment_cancellation_failed', [
                'payment_id' => $paymentId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Faz requisição HTTP para API Asaas
     */
    private function makeRequest(string $method, string $endpoint, array $data = []): array
    {
        $url = $this->baseUrl . $endpoint;
        
        $headers = [
            'Content-Type: application/json',
            'access_token: ' . $this->apiKey,
            'User-Agent: ERP-InLaudo/1.0'
        ];

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

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false) {
            throw new \RuntimeException('Falha na comunicação com API Asaas');
        }

        $responseData = json_decode($response, true);

        if ($httpCode >= 400) {
            $errorMessage = $responseData['errors'][0]['description'] ?? 'Erro desconhecido';
            throw new \RuntimeException("Asaas API Error ({$httpCode}): {$errorMessage}");
        }

        return $responseData;
    }

    /**
     * Verifica se o serviço está configurado
     */
    public static function isConfigured(): bool
    {
        $apiKey = getenv('ASAAS_API_KEY') ?: $_ENV['ASAAS_API_KEY'] ?? '';
        return !empty($apiKey);
    }

    /**
     * Formata CPF/CNPJ para o Asaas
     */
    public static function formatarDocumento(string $documento): string
    {
        return preg_replace('/\D/', '', $documento);
    }

    /**
     * Mapeia status do Asaas para status interno
     */
    public static function mapearStatus(string $asaasStatus): string
    {
        $statusMap = [
            'PENDING' => 'pendente',
            'RECEIVED' => 'paga',
            'CONFIRMED' => 'paga',
            'OVERDUE' => 'vencida',
            'REFUNDED' => 'cancelada',
            'RECEIVED_IN_CASH' => 'paga',
            'DUNNING_REQUESTED' => 'vencida',
            'DUNNING_RECEIVED' => 'paga',
            'AWAITING_CHARGEBACK_REVERSAL' => 'pendente',
            'CHARGEBACK_REQUESTED' => 'disputada',
            'CHARGEBACK_DISPUTE' => 'disputada',
            'CHARGEBACK_REVERSED' => 'paga'
        ];

        return $statusMap[$asaasStatus] ?? 'pendente';
    }

    /**
     * Mapeia meio de pagamento do Asaas
     */
    public static function mapearMeioPagamento(string $billingType): string
    {
        $meioMap = [
            'BOLETO' => 'boleto',
            'CREDIT_CARD' => 'cartao',
            'PIX' => 'pix',
            'UNDEFINED' => 'outro'
        ];

        return $meioMap[$billingType] ?? 'outro';
    }
}
