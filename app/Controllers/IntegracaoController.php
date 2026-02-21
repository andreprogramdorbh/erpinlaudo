<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\View;
use App\Core\Auth;
use App\Models\Integracao;
use App\Models\User;
use App\Models\ContaReceber;
use App\Core\Audit\AuditLogger;
use App\Core\Logger;
use App\Services\CryptoService;
use App\Services\MailService;
use App\Services\ContaReceberRecorrenciaService;

class IntegracaoController extends Controller
{
    private Integracao $integracaoModel;
    private Logger $logger;

    public function __construct()
    {
        $this->integracaoModel = new Integracao();
        $this->logger = new Logger();
    }

    /**
     * Classifica o tipo de erro de e-mail para melhor tratamento
     */
    private function classifyEmailError(string $errorMessage): string
    {
        $errorMessage = strtolower($errorMessage);
        
        // Erros de autenticação
        if (strpos($errorMessage, 'authentication') !== false || 
            strpos($errorMessage, 'auth') !== false ||
            strpos($errorMessage, 'senha') !== false ||
            strpos($errorMessage, 'password') !== false ||
            strpos($errorMessage, 'login') !== false ||
            strpos($errorMessage, 'credentials') !== false) {
            return 'authentication_error';
        }
        
        // Erros de conexão
        if (strpos($errorMessage, 'connection') !== false ||
            strpos($errorMessage, 'connect') !== false ||
            strpos($errorMessage, 'timeout') !== false ||
            strpos($errorMessage, 'refused') !== false ||
            strpos($errorMessage, 'network') !== false) {
            return 'connection_error';
        }
        
        // Erros de configuração
        if (strpos($errorMessage, 'configuration') !== false ||
            strpos($errorMessage, 'config') !== false ||
            strpos($errorMessage, 'ssl') !== false ||
            strpos($errorMessage, 'tls') !== false ||
            strpos($errorMessage, 'certificate') !== false) {
            return 'configuration_error';
        }
        
        // Erros de DNS/resolução
        if (strpos($errorMessage, 'dns') !== false ||
            strpos($errorMessage, 'host') !== false ||
            strpos($errorMessage, 'resolve') !== false ||
            strpos($errorMessage, 'not found') !== false) {
            return 'dns_error';
        }
        
        // Erros de permissão/acesso
        if (strpos($errorMessage, 'permission') !== false ||
            strpos($errorMessage, 'access') !== false ||
            strpos($errorMessage, 'blocked') !== false ||
            strpos($errorMessage, 'firewall') !== false) {
            return 'access_error';
        }
        
        // Erros de protocolo
        if (strpos($errorMessage, 'protocol') !== false ||
            strpos($errorMessage, 'smtp') !== false ||
            strpos($errorMessage, 'port') !== false) {
            return 'protocol_error';
        }
        
        return 'unknown_error';
    }

    public function email(): void
    {
        if (!Auth::can('manage_settings')) {
            header("Location: /dashboard?error=unauthorized");
            exit();
        }

        $usuarioId = Auth::user()->id;
        $row = $this->integracaoModel->findByNomeAndUsuarioId('email', (int) $usuarioId);
        $config = [];

        if ($row) {
            $config = $this->integracaoModel->getDecodedConfig($row);
        }

        View::render('integracoes/email', [
            'title' => 'Configuração E-mail',
            'config' => $config,
            'breadcrumb' => [
                'Configurações' => '/configuracoes',
                'Integrações' => '#',
                'E-mail' => '/integracao/email'
            ],
            '_layout' => 'erp'
        ]);
    }

    public function saveEmail(): void
    {
        if (!Auth::can('manage_settings')) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Não autorizado']);
            return;
        }

        try {
            $usuarioId = (int) Auth::user()->id;

            $host = trim((string)($_POST['host'] ?? ''));
            $port = (int)($_POST['port'] ?? 587);
            $username = trim((string)($_POST['username'] ?? ''));
            $password = (string)($_POST['password'] ?? '');
            $protocol = strtolower(trim((string)($_POST['protocol'] ?? 'tls')));

            $fromEmail = trim((string)($_POST['from_email'] ?? ''));
            $fromName = trim((string)($_POST['from_name'] ?? 'ERP InLaudo'));
            $status = ($_POST['status'] ?? 'active') === 'inactive' ? 'inativo' : 'ativo';

            if ($host === '' || $port <= 0 || $username === '') {
                throw new \Exception('Host, Porta e Usuário são obrigatórios.');
            }
            if (!in_array($protocol, ['tls', 'ssl'], true)) {
                throw new \Exception('Protocolo inválido.');
            }

            $row = $this->integracaoModel->findByNomeAndUsuarioId('email', $usuarioId);
            $existing = $row ? $this->integracaoModel->getDecodedConfig($row) : [];

            if ($password === '********') {
                $passwordEnc = (string)($existing['password_enc'] ?? '');
            } elseif ($password === '') {
                $passwordEnc = '';
            } else {
                $crypto = new CryptoService();
                $passwordEnc = $crypto->encryptString($password);
            }

            $config = array_merge($existing, [
                'host' => $host,
                'port' => $port,
                'username' => $username,
                'password_enc' => $passwordEnc,
                'protocol' => $protocol,
                'from_email' => $fromEmail,
                'from_name' => $fromName,
            ]);

            unset($config['password']);

            $ok = $this->integracaoModel->upsertConfigJson('email', $usuarioId, [
                'tipo' => 'API',
                'status' => $status,
                'config' => $config,
            ]);

            if (!$ok) {
                throw new \Exception('Erro ao salvar no banco de dados.');
            }

            AuditLogger::log('update_email_config', [
                'status' => $status,
                'protocol' => $protocol,
                'host' => $host,
                'port' => $port,
            ]);

            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Configuração salva com sucesso!']);
        } catch (\RuntimeException $e) {
            $this->logger->error('Erro de criptografia ao salvar config de e-mail: ' . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Criptografia não configurada. Defina APP_KEY ou APP_ENCRYPTION_KEY no .env.'
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erro ao salvar config de e-mail: ' . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function testEmail(): void
    {
        if (!Auth::can('manage_settings')) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Não autorizado']);
            return;
        }

        try {
            $usuarioId = (int) Auth::user()->id;
            $userModel = new User();
            $user = $userModel->findById($usuarioId);
            if (!$user || empty($user->email)) {
                throw new \Exception('E-mail do usuário não encontrado.');
            }

            $row = $this->integracaoModel->findByNomeAndUsuarioId('email', $usuarioId);
            if (!$row) {
                throw new \Exception('Configure o e-mail antes de testar.');
            }

            if (($row->status ?? 'ativo') !== 'ativo') {
                throw new \Exception('Integração de e-mail está inativa.');
            }

            $config = $this->integracaoModel->getDecodedConfig($row);
            $password = '';
            if (!empty($config['password_enc'])) {
                $crypto = new CryptoService();
                $password = $crypto->decryptString((string) $config['password_enc']);
            }

            $service = new MailService([
                'host' => $config['host'] ?? '',
                'port' => $config['port'] ?? 587,
                'username' => $config['username'] ?? '',
                'password' => $password,
                'protocol' => $config['protocol'] ?? 'tls',
                'from_email' => $config['from_email'] ?? ($config['username'] ?? ''),
                'from_name' => $config['from_name'] ?? 'ERP InLaudo',
            ]);

            $subject = 'Teste de E-mail - ERP InLaudo';
            $body = "Este é um e-mail de teste do ERP InLaudo.\n\nSe você recebeu esta mensagem, a configuração está correta.";
            
            // Tentar envio com tratamento detalhado de erros
            try {
                $service->sendText((string) $user->email, $subject, $body);
            } catch (\Throwable $e) {
                // Log detalhado da falha com contexto seguro
                AuditLogger::log('email_test_failure', [
                    'usuario_id' => $usuarioId,
                    'to_email' => $user->email,
                    'error' => $e->getMessage(),
                    'host' => $config['host'] ?? 'unknown',
                    'port' => $config['port'] ?? 'unknown',
                    'username' => $config['username'] ?? 'unknown',
                    'protocol' => $config['protocol'] ?? 'unknown'
                    // NUNCA registrar senha ou chaves de criptografia
                ]);
                
                // Retornar erro específico para o frontend
                $errorType = $this->classifyEmailError($e->getMessage());
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'error' => $e->getMessage(),
                    'error_type' => $errorType,
                    'timestamp' => date('Y-m-d H:i:s')
                ]);
                return;
            }

            $this->integracaoModel->upsertConfigJson('email', $usuarioId, [
                'tipo' => $row->tipo ?? 'API',
                'status' => $row->status ?? 'ativo',
                'config' => array_merge($config, ['last_test_at' => date('Y-m-d H:i:s')]),
            ]);

            AuditLogger::log('test_email_success', ['usuario_id' => $usuarioId]);
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'E-mail de teste enviado para ' . $user->email]);
        } catch (\RuntimeException $e) {
            $this->logger->error('Erro de criptografia no teste de e-mail: ' . $e->getMessage());
            AuditLogger::log('email_test_failure', [
                'usuario_id' => $usuarioId,
                'error' => 'Criptografia não configurada',
                'error_type' => 'crypto_error',
                'technical_details' => $e->getMessage()
                // NUNCA registrar chaves ou senhas
            ]);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Criptografia não configurada. Defina APP_KEY ou APP_ENCRYPTION_KEY no .env.',
                'error_type' => 'crypto_error',
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erro no teste de e-mail: ' . $e->getMessage());
            AuditLogger::log('email_test_failure', [
                'usuario_id' => $usuarioId,
                'error' => $e->getMessage(),
                'error_type' => $this->classifyEmailError($e->getMessage()),
                'technical_details' => $e->getMessage(),
                'stack_trace' => $e->getTraceAsString()
                // NUNCA registrar dados sensíveis
            ]);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false, 
                'error' => $e->getMessage(),
                'error_type' => $this->classifyEmailError($e->getMessage()),
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        }
    }

    /**
     * Exibe a configuração do Asaas.
     */
    public function asaas()
    {
        if (!Auth::can('manage_settings')) {
            header("Location: /dashboard?error=unauthorized");
            exit();
        }

        $config = $this->integracaoModel->findByProvider('asaas');

        View::render('integracoes/asaas', [
            'title' => 'Configuração Asaas',
            'config' => $config,
            'breadcrumb' => [
                'Configurações' => '/configuracoes',
                'Integrações' => '#',
                'Asaas' => '/integracao/asaas'
            ],
            '_layout' => 'erp'
        ]);
    }

    /**
     * Salva a configuração do Asaas.
     */
    public function saveAsaas()
    {
        if (!Auth::can('manage_settings')) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Não autorizado']);
            return;
        }

        try {
            $data = [
                'api_key' => $_POST['api_key'] ?? '',
                'webhook_token' => $_POST['webhook_token'] ?? '',
                'environment' => $_POST['environment'] ?? 'sandbox',
                'status' => $_POST['status'] ?? 'active'
            ];

            if (empty($data['api_key'])) {
                throw new \Exception("A API Key é obrigatória.");
            }

            if ($this->integracaoModel->updateConfig('asaas', $data)) {
                AuditLogger::log('update_asaas_config', [
                    'environment' => $data['environment'],
                    'status' => $data['status']
                ]);
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Configuração salva com sucesso!']);
            } else {
                throw new \Exception("Erro ao salvar no banco de dados.");
            }
        } catch (\Exception $e) {
            $this->logger->error("Erro ao salvar config Asaas: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Testa a conexão com o Asaas.
     */
    public function testAsaas()
    {
        if (!Auth::can('manage_settings')) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Não autorizado']);
            return;
        }

        try {
            $config = $this->integracaoModel->findByProvider('asaas');
            if (!$config || empty($config->api_key)) {
                throw new \Exception("Configure a API Key antes de testar.");
            }

            $baseUrl = $config->environment === 'production'
                ? 'https://www.asaas.com/api/v3'
                : 'https://sandbox.asaas.com/api/v3';

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $baseUrl . '/accounts/profile');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'access_token: ' . $config->api_key,
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200) {
                $this->integracaoModel->updateLastTest('asaas');
                AuditLogger::log('test_asaas_connection_success', ['environment' => $config->environment]);
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Conexão estabelecida com sucesso!']);
            } else {
                $errorData = json_decode($response, true);
                $errorMsg = $errorData['errors'][0]['description'] ?? 'Erro desconhecido';
                AuditLogger::log('test_asaas_connection_failed', [
                    'environment' => $config->environment,
                    'http_code' => $httpCode,
                    'error' => $errorMsg
                ]);
                throw new \Exception("Falha na conexão: {$errorMsg} (HTTP {$httpCode})");
            }
        } catch (\Exception $e) {
            $this->logger->error("Erro no teste conexão Asaas: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Webhook para o Asaas.
     */
    public function webhook()
    {
        // Pega o payload e o token do header (se configurado no Asaas)
        $payload = file_get_contents('php://input');
        $data = json_decode($payload, true);

        if (!is_array($data)) {
            http_response_code(400);
            exit();
        }

        $tokenHeader = (string)($_SERVER['HTTP_ASAAS_ACCESS_TOKEN'] ?? '');
        try {
            $tenantId = null;
            if (isset($data['payment']['externalReference'])) {
                $ref = (string)$data['payment']['externalReference'];
                if (preg_match('/u:(\d+)/', $ref, $m)) {
                    $tenantId = (int)$m[1];
                }
            }

            if ($tenantId) {
                $configRow = $this->integracaoModel->findByNomeAndUsuarioId('asaas', $tenantId);
                if ($configRow) {
                    $cfg = $this->integracaoModel->getDecodedConfig($configRow);
                    $expected = '';
                    if (!empty($cfg['webhook_token_enc'])) {
                        $crypto = new CryptoService();
                        $expected = $crypto->decryptString((string)$cfg['webhook_token_enc']);
                    }

                    if ($expected !== '') {
                        if ($tokenHeader === '' || !hash_equals($expected, $tokenHeader)) {
                            http_response_code(403);
                            exit();
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            http_response_code(403);
            exit();
        }

        $event = $data['event'] ?? '';
        $payment = $data['payment'] ?? [];

        $this->logger->info("Webhook Asaas Recebido", ['event' => $event, 'payment_id' => $payment['id'] ?? null]);

        // Lógica de processamento de eventos
        switch ($event) {
            case 'PAYMENT_CONFIRMED':
            case 'PAYMENT_RECEIVED':
                // Atualizar status da conta a receber no sistema
                // AuditLogger::log('webhook_payment_received', ['payment_id' => $payment['id']]);
                try {
                    if ($tenantId && !empty($payment['id'])) {
                        $contaModel = new ContaReceber();
                        $row = $contaModel->findByAsaasPaymentIdAndUsuarioId((int) $tenantId, (string) $payment['id']);

                        if (!$row && !empty($payment['externalReference'])) {
                            $row = $contaModel->findByExternalReferenceAndUsuarioId((int) $tenantId, (string) $payment['externalReference']);
                            if ($row && empty($row->asaas_payment_id)) {
                                $contaModel->update((int)$row->id, ['asaas_payment_id' => (string) $payment['id']]);
                            }
                        }

                        if ($row) {
                            $dataRec = null;
                            if (!empty($payment['paymentDate'])) {
                                $dataRec = substr((string)$payment['paymentDate'], 0, 10);
                            } elseif (!empty($payment['confirmedDate'])) {
                                $dataRec = substr((string)$payment['confirmedDate'], 0, 10);
                            } elseif (!empty($payment['dateCreated'])) {
                                $dataRec = substr((string)$payment['dateCreated'], 0, 10);
                            }

                            $update = [
                                'status' => 'recebida',
                                'data_recebimento' => $dataRec ?: date('Y-m-d'),
                            ];

                            $contaModel->update((int)$row->id, $update);

                            AuditLogger::log('webhook_asaas_conta_receber_recebida', [
                                'usuario_id' => (int) $tenantId,
                                'conta_receber_id' => (int) $row->id,
                                'asaas_payment_id' => (string) $payment['id'],
                                'event' => (string) $event,
                            ]);

                            $svc = new ContaReceberRecorrenciaService();
                            $svc->gerarProximaSeRecorrente((int) $tenantId, (int) $row->id);
                        }
                    }
                } catch (\Throwable $e) {
                    $this->logger->error('Erro ao processar webhook Asaas (recebimento): ' . $e->getMessage());
                }
                break;
        }

        http_response_code(200);
        echo json_encode(['received' => true]);
    }
}
