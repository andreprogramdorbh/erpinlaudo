<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\View;
use App\Core\Auth;
use App\Models\Integracao;
use App\Models\User;
use App\Models\ContaReceber;
use App\Models\NotaFiscal;
use App\Core\Audit\AuditLogger;
use App\Core\Logger;
use App\Services\AsaasService;
use App\Services\CryptoService;
use App\Services\MailService;
use App\Services\ContaReceberRecorrenciaService;
use App\Models\EmailAlerta;
use App\Services\EmailAlertaService;
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
            'crypto_configured' => CryptoService::isConfigured(),
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
            ob_start(); ob_end_clean();
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

            // Se o usuário manteve '********', verifica se a senha existente ainda é descriptogravável
            // Se não for (chave trocada), exige que o usuário redigite
            if ($password === '********') {
                $existingEnc = (string)($existing['password_enc'] ?? '');
                if ($existingEnc !== '') {
                    try {
                        $crypto = new CryptoService();
                        $crypto->decryptString($existingEnc); // valida se é descriptogravável
                    } catch (\RuntimeException $cryptoEx) {
                        ob_start(); ob_end_clean();
        header('Content-Type: application/json');
                        echo json_encode([
                            'success' => false,
                            'error' => 'A senha salva foi criptografada com uma chave diferente da atual. Digite a senha novamente para atualizá-la com a chave correta.',
                            'error_type' => 'password_key_mismatch'
                        ]);
                        return;
                    }
                }
            }

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
            ob_start(); ob_end_clean();
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
                try {
                    $crypto = new CryptoService();
                    $password = $crypto->decryptString((string) $config['password_enc']);
                } catch (\RuntimeException $cryptoEx) {
                    // Senha salva com APP_KEY diferente da atual — precisa ser redigitada
                    $this->logger->error('Senha SMTP incompatível com APP_KEY atual: ' . $cryptoEx->getMessage());
                    AuditLogger::log('email_password_key_mismatch', [
                        'usuario_id' => $usuarioId,
                        'action' => 'Senha criptografada com chave diferente da atual — necessário redigitar'
                    ]);
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'error' => 'A senha salva foi criptografada com uma chave diferente da atual. Por segurança, digite a senha novamente e salve antes de testar.',
                        'error_type' => 'password_key_mismatch',
                        'timestamp' => date('Y-m-d H:i:s')
                    ]);
                    return;
                }
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

            $subject = 'Teste de E-mail — ERP InLaudo';
            $bodyHtml = '
<!DOCTYPE html>
<html lang="pt-BR">
<head><meta charset="UTF-8"><title>Teste de E-mail</title></head>
<body style="margin:0;padding:0;background:#f4f6f9;font-family:Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f9;padding:32px 0;">
  <tr><td align="center">
    <table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.08);">
      <tr><td style="background:#1a56db;padding:24px 32px;">
        <h1 style="margin:0;color:#fff;font-size:20px;font-weight:700;">ERP InLaudo</h1>
      </td></tr>
      <tr><td style="padding:32px;color:#333;font-size:15px;line-height:1.6;">
        <h2 style="color:#1a56db;margin-top:0;">&#10003; Configuração de E-mail Validada</h2>
        <p>Este é um e-mail de teste enviado pelo <strong>ERP InLaudo</strong>.</p>
        <p>Se você recebeu esta mensagem, a configuração SMTP está correta e o sistema está pronto para enviar notificações.</p>
        <table style="border-collapse:collapse;margin:16px 0;width:100%;">
          <tr style="background:#f8f9fa;"><td style="padding:8px 12px;font-weight:600;color:#555;">Servidor</td><td style="padding:8px 12px;">' . htmlspecialchars((string)($config['host'] ?? '')) . '</td></tr>
          <tr><td style="padding:8px 12px;font-weight:600;color:#555;">Porta</td><td style="padding:8px 12px;">' . htmlspecialchars((string)($config['port'] ?? '')) . '</td></tr>
          <tr style="background:#f8f9fa;"><td style="padding:8px 12px;font-weight:600;color:#555;">Protocolo</td><td style="padding:8px 12px;">' . strtoupper(htmlspecialchars((string)($config['protocol'] ?? ''))) . '</td></tr>
          <tr><td style="padding:8px 12px;font-weight:600;color:#555;">Remetente</td><td style="padding:8px 12px;">' . htmlspecialchars((string)($config['from_email'] ?? '')) . '</td></tr>
          <tr style="background:#f8f9fa;"><td style="padding:8px 12px;font-weight:600;color:#555;">Data/Hora</td><td style="padding:8px 12px;">' . date('d/m/Y H:i:s') . '</td></tr>
        </table>
      </td></tr>
      <tr><td style="background:#f8f9fa;padding:16px 32px;border-top:1px solid #e9ecef;">
        <p style="margin:0;color:#888;font-size:12px;text-align:center;">Este e-mail foi enviado automaticamente pelo ERP InLaudo. Não responda.</p>
      </td></tr>
    </table>
  </td></tr>
</table>
</body></html>';

            // Tentar envio com tratamento detalhado de erros
            try {
                $service->send((string) $user->email, $subject, $bodyHtml, true, $user->name ?? null);
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
     * Gera uma nova APP_ENCRYPTION_KEY segura e retorna ao frontend.
     * A chave gerada deve ser copiada e adicionada ao arquivo .env do servidor.
     * POST /integracao/email/gerar-chave
     */
    public function gerarChaveEmail(): void
    {
        ob_start(); ob_end_clean();
        header('Content-Type: application/json');

        if (!Auth::can('manage_settings')) {
            echo json_encode(['success' => false, 'error' => 'Não autorizado']);
            return;
        }

        try {
            // Gera 32 bytes aleatórios criptograficamente seguros e codifica em base64
            $rawBytes = random_bytes(32);
            $chave = 'base64:' . base64_encode($rawBytes);

            AuditLogger::log('generate_app_encryption_key', [
                'usuario_id' => Auth::user()->id,
                'action' => 'Nova APP_ENCRYPTION_KEY gerada via painel',
            ]);

            echo json_encode([
                'success' => true,
                'chave' => $chave,
                'instrucao' => 'Adicione esta linha ao arquivo .env do servidor: APP_KEY=' . $chave,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erro ao gerar chave de criptografia: ' . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'Erro ao gerar chave: ' . $e->getMessage()]);
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
            ob_start(); ob_end_clean();
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
            ob_start(); ob_end_clean();
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
    /**
     * Webhook para o Asaas.
     *
     * URL: POST /api/webhooks/asaas
     * Rota pública (sem autenticação ERP) — validada pelo token Asaas no header.
     *
     * Eventos tratados:
     *  - PAYMENT_CONFIRMED / PAYMENT_RECEIVED  -> status = recebida
     *  - PAYMENT_OVERDUE                        -> log de aviso (status permanece aberta)
     *  - PAYMENT_DELETED / PAYMENT_REFUNDED     -> status = cancelada
     *  - PAYMENT_CHARGEBACK_REQUESTED           -> log de alerta
     *  - GET /api/webhooks/asaas/ping           -> health-check para teste
     */
    public function webhook()
    {
        header('Content-Type: application/json; charset=utf-8');

        // 1. Le e valida o payload
        $payload = (string) file_get_contents('php://input');

        // Captura todos os headers relevantes para diagnostico
        $allHeaders = [];
        foreach ($_SERVER as $k => $v) {
            if (strpos($k, 'HTTP_') === 0) {
                $allHeaders[str_replace('HTTP_', '', $k)] = $v;
            }
        }
        $this->logger->info('Webhook Asaas: payload recebido', [
            'ip'                       => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'content_length'           => strlen($payload),
            'user_agent'               => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'has_asaas_access_token'   => isset($_SERVER['HTTP_ASAAS_ACCESS_TOKEN']),
            'asaas_access_token_len'   => strlen($_SERVER['HTTP_ASAAS_ACCESS_TOKEN'] ?? ''),
            'has_authorization'        => isset($_SERVER['HTTP_AUTHORIZATION']),
            'content_type'             => $_SERVER['CONTENT_TYPE'] ?? '',
            'request_method'           => $_SERVER['REQUEST_METHOD'] ?? '',
            'all_http_headers'         => $allHeaders,
        ]);

        if (empty($payload)) {
            $this->logger->warning('Webhook Asaas: payload vazio');
            http_response_code(400);
            echo json_encode(['error' => 'Empty payload']);
            exit();
        }

        $data = json_decode($payload, true);
        if (!is_array($data)) {
            $this->logger->warning('Webhook Asaas: JSON invalido', ['raw' => substr($payload, 0, 200)]);
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON']);
            exit();
        }

        // 2. Extrai tenantId do externalReference
        // Formato esperado: "u:{usuario_id}|cr:{conta_receber_id}"
        // Suporta tanto eventos PAYMENT_* ($data['payment']) quanto INVOICE_* ($data['invoice'])
        $tenantId = null;
        $payment  = $data['payment'] ?? [];
        $invoice  = $data['invoice'] ?? [];

        // Para eventos INVOICE_*, o externalReference fica dentro de $data['invoice']
        // Para eventos PAYMENT_*, fica dentro de $data['payment']
        $extRef = (string)(
            $payment['externalReference']
            ?? $invoice['externalReference']
            ?? $data['externalReference']
            ?? ''
        );

        if ($extRef !== '' && preg_match('/u:(\d+)/', $extRef, $m)) {
            $tenantId = (int)$m[1];
        }

        // 3. Valida token de autenticacao do webhook
        $tokenHeaderRaw  = (string)($_SERVER['HTTP_ASAAS_ACCESS_TOKEN'] ?? '');
        $tokenHeaderTrim = trim($tokenHeaderRaw);
        $tokenHeader     = $tokenHeaderRaw;

        if ($tenantId) {
            try {
                $configRow = $this->integracaoModel->findByNomeAndUsuarioId('asaas', $tenantId);
                if ($configRow) {
                    $cfg      = $this->integracaoModel->getDecodedConfig($configRow);
                    $expected = '';

                    if (!empty($cfg['webhook_token_enc'])) {
                        $crypto   = new CryptoService();
                        $expected = $crypto->decryptString((string)$cfg['webhook_token_enc']);
                    }

                    if ($expected !== '') {
                        if ($tokenHeader === '') {
                            $this->logger->warning('Webhook Asaas: token ausente no header', [
                                'tenant_id'  => $tenantId,
                                'payment_id' => $payment['id'] ?? null,
                                'ip'         => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                                'ua'         => $_SERVER['HTTP_USER_AGENT'] ?? '',
                                'ext_ref'    => $extRef,
                                'cfg'        => [
                                    'has_integration'   => (bool) $configRow,
                                    'has_token_config'  => !empty($cfg['webhook_token_enc']),
                                    'expected_len'      => strlen($expected),
                                ],
                                'headers'    => [
                                    'has_ASAAS_ACCESS_TOKEN' => isset($_SERVER['HTTP_ASAAS_ACCESS_TOKEN']),
                                    'has_AUTHORIZATION'      => isset($_SERVER['HTTP_AUTHORIZATION']),
                                    'content_type'           => $_SERVER['CONTENT_TYPE'] ?? '',
                                ],
                            ]);
                            // IMPORTANTE: Asaas penaliza qualquer resposta nao-200 com "Penalizacao aplicada".
                            // Nunca retornar 4xx/5xx para o Asaas — logar e responder 200.
                            http_response_code(200);
                            echo json_encode([
                                'received'  => true,
                                'processed' => false,
                                'reason'    => 'missing_token',
                                'hint'      => 'Token ausente no header asaas-access-token. Verifique a configuracao do webhook no painel Asaas e o token salvo nas integracoes do ERP.',
                            ]);
                            exit();
                        }

                        // Tolerância a espaços acidentais no header (ex.: proxy/WAF adicionando whitespace)
                        $matchTrim = ($tokenHeaderTrim !== '' && hash_equals($expected, $tokenHeaderTrim));
                        if ($matchTrim) {
                            $this->logger->warning('Webhook Asaas: token recebido com whitespace (aceito apos trim)', [
                                'tenant_id'         => $tenantId,
                                'payment_id'        => $payment['id'] ?? null,
                                'received_len_raw'  => strlen($tokenHeaderRaw),
                                'received_len_trim' => strlen($tokenHeaderTrim),
                            ]);
                            $tokenHeader = $tokenHeaderTrim;
                        }

                        if (!hash_equals($expected, $tokenHeader)) {
                            $this->logger->warning('Webhook Asaas: token invalido', [
                                'tenant_id'  => $tenantId,
                                'payment_id' => $payment['id'] ?? null,
                                'ip'         => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                                'ua'         => $_SERVER['HTTP_USER_AGENT'] ?? '',
                                'ext_ref'    => $extRef,
                                'diag'       => [
                                    'expected_len'      => strlen($expected),
                                    'received_len_raw'  => strlen($tokenHeaderRaw),
                                    'received_len_trim' => strlen($tokenHeaderTrim),
                                    'received_trim_eq'  => $matchTrim,
                                    'expected_sha256_12' => substr(hash('sha256', $expected), 0, 12),
                                    'received_sha256_12' => substr(hash('sha256', $tokenHeaderRaw), 0, 12),
                                ],
                            ]);
                            // IMPORTANTE: Asaas penaliza qualquer resposta nao-200 com "Penalizacao aplicada".
                            // Nunca retornar 4xx/5xx para o Asaas — logar e responder 200.
                            http_response_code(200);
                            echo json_encode([
                                'received'  => true,
                                'processed' => false,
                                'reason'    => 'invalid_token',
                                'hint'      => 'Token invalido. Verifique se o token configurado no painel Asaas bate com o salvo nas integracoes do ERP.',
                            ]);
                            exit();
                        }
                    }
                }
            } catch (\Throwable $e) {
                $this->logger->error('Webhook Asaas: erro na validacao do token', [
                    'tenant_id' => $tenantId,
                    'error'     => $e->getMessage(),
                    'trace'     => substr($e->getTraceAsString(), 0, 500),
                ]);
                // IMPORTANTE: Asaas penaliza qualquer resposta nao-200.
                http_response_code(200);
                echo json_encode(['received' => true, 'processed' => false, 'reason' => 'token_validation_error']);
                exit();
            }
        } else {
            $eventType = (string)($data['event'] ?? '');

            // Eventos INVOICE_* podem ser processados sem tenantId via externalReference
            // porque usamos findByAsaasInvoiceIdGlobal() para localizar a NF pelo invoice.id
            $isInvoiceEvent = str_starts_with($eventType, 'INVOICE_');

            if (!$isInvoiceEvent) {
                $this->logger->warning('Webhook Asaas: tenantId nao identificado no externalReference', [
                    'external_reference' => $extRef,
                    'event'              => $eventType,
                    'payment_id'         => $payment['id'] ?? null,
                ]);
                http_response_code(200);
                echo json_encode(['received' => true, 'processed' => false, 'reason' => 'tenant_not_identified']);
                exit();
            }

            // Para eventos INVOICE_* sem tenantId: continua o processamento
            // O handler INVOICE_* usara findByAsaasInvoiceIdGlobal() como fallback
            $this->logger->info('Webhook Asaas: evento INVOICE sem tenantId no extRef, processando via invoice.id', [
                'event'      => $eventType,
                'invoice_id' => $invoice['id'] ?? null,
            ]);
        }

        // 4. Processa o evento
        $event = (string)($data['event'] ?? '');

        $this->logger->info('Webhook Asaas: processando evento', [
            'event'      => $event,
            'tenant_id'  => $tenantId,
            'payment_id' => $payment['id'] ?? null,
            'invoice_id' => $invoice['id'] ?? null,
            'value'      => $payment['value'] ?? $invoice['value'] ?? null,
            'status'     => $payment['status'] ?? $invoice['status'] ?? null,
            'ext_ref'    => $extRef,
        ]);

        switch ($event) {

            // Pagamento confirmado / recebido
            case 'PAYMENT_CONFIRMED':
            case 'PAYMENT_RECEIVED':
            case 'PAYMENT_RECEIVED_IN_CASH':
                try {
                    if (!empty($payment['id'])) {
                        $contaModel = new ContaReceber();

                        $row = $contaModel->findByAsaasPaymentIdAndUsuarioId($tenantId, (string)$payment['id']);

                        if (!$row && $extRef !== '') {
                            $row = $contaModel->findByExternalReferenceAndUsuarioId($tenantId, $extRef);
                            if ($row && empty($row->asaas_payment_id)) {
                                $contaModel->update((int)$row->id, ['asaas_payment_id' => (string)$payment['id']]);
                            }
                        }

                        if ($row) {
                            $dataRec = null;
                            foreach (['paymentDate', 'confirmedDate', 'clientPaymentDate'] as $field) {
                                if (!empty($payment[$field])) {
                                    $dataRec = substr((string)$payment[$field], 0, 10);
                                    break;
                                }
                            }

                            $contaModel->update((int)$row->id, [
                                'status'           => 'recebida',
                                'data_recebimento' => $dataRec ?: date('Y-m-d'),
                            ]);

                            AuditLogger::log('webhook_asaas_pagamento_recebido', [
                                'usuario_id'       => $tenantId,
                                'conta_receber_id' => (int)$row->id,
                                'asaas_payment_id' => (string)$payment['id'],
                                'event'            => $event,
                                'valor'            => $payment['value'] ?? null,
                                'data_recebimento' => $dataRec ?: date('Y-m-d'),
                            ]);

                            $svc = new ContaReceberRecorrenciaService();
                            $svc->gerarProximaSeRecorrente($tenantId, (int)$row->id);

                            $this->logger->info('Webhook Asaas: conta marcada como recebida', [
                                'conta_receber_id' => (int)$row->id,
                                'payment_id'       => (string)$payment['id'],
                            ]);
                        } else {
                            $this->logger->warning('Webhook Asaas: conta a receber nao encontrada', [
                                'tenant_id'  => $tenantId,
                                'payment_id' => (string)$payment['id'],
                                'ext_ref'    => $extRef,
                            ]);
                        }
                    }
                } catch (\Throwable $e) {
                    $this->logger->error('Webhook Asaas: erro ao processar recebimento', [
                        'event'      => $event,
                        'payment_id' => $payment['id'] ?? null,
                        'tenant_id'  => $tenantId,
                        'error'      => $e->getMessage(),
                        'trace'      => substr($e->getTraceAsString(), 0, 500),
                    ]);
                }
                break;

            // Pagamento vencido
            case 'PAYMENT_OVERDUE':
                try {
                    if (!empty($payment['id'])) {
                        $contaModel = new ContaReceber();
                        $row = $contaModel->findByAsaasPaymentIdAndUsuarioId($tenantId, (string)$payment['id']);
                        if (!$row && $extRef !== '') {
                            $row = $contaModel->findByExternalReferenceAndUsuarioId($tenantId, $extRef);
                        }
                        if ($row) {
                            AuditLogger::log('webhook_asaas_pagamento_vencido', [
                                'usuario_id'       => $tenantId,
                                'conta_receber_id' => (int)$row->id,
                                'asaas_payment_id' => (string)$payment['id'],
                                'due_date'         => $payment['dueDate'] ?? null,
                            ]);
                            $this->logger->warning('Webhook Asaas: pagamento vencido', [
                                'conta_receber_id' => (int)$row->id,
                                'payment_id'       => (string)$payment['id'],
                                'due_date'         => $payment['dueDate'] ?? null,
                            ]);
                        }
                    }
                } catch (\Throwable $e) {
                    $this->logger->error('Webhook Asaas: erro ao processar vencimento', [
                        'payment_id' => $payment['id'] ?? null,
                        'error'      => $e->getMessage(),
                    ]);
                }
                break;

            // Pagamento excluido / estornado
            case 'PAYMENT_DELETED':
            case 'PAYMENT_REFUNDED':
            case 'PAYMENT_PARTIALLY_REFUNDED':
                try {
                    if (!empty($payment['id'])) {
                        $contaModel = new ContaReceber();
                        $row = $contaModel->findByAsaasPaymentIdAndUsuarioId($tenantId, (string)$payment['id']);
                        if (!$row && $extRef !== '') {
                            $row = $contaModel->findByExternalReferenceAndUsuarioId($tenantId, $extRef);
                        }
                        if ($row && $row->status !== 'cancelada') {
                            $contaModel->update((int)$row->id, ['status' => 'cancelada']);
                            AuditLogger::log('webhook_asaas_pagamento_cancelado', [
                                'usuario_id'       => $tenantId,
                                'conta_receber_id' => (int)$row->id,
                                'asaas_payment_id' => (string)$payment['id'],
                                'event'            => $event,
                            ]);
                            $this->logger->info('Webhook Asaas: conta marcada como cancelada', [
                                'conta_receber_id' => (int)$row->id,
                                'event'            => $event,
                            ]);
                        }
                    }
                } catch (\Throwable $e) {
                    $this->logger->error('Webhook Asaas: erro ao processar cancelamento', [
                        'event'      => $event,
                        'payment_id' => $payment['id'] ?? null,
                        'error'      => $e->getMessage(),
                    ]);
                }
                break;

            // Chargeback
            case 'PAYMENT_CHARGEBACK_REQUESTED':
            case 'PAYMENT_CHARGEBACK_DISPUTE':
            case 'PAYMENT_AWAITING_CHARGEBACK_REVERSAL':
                $this->logger->warning('Webhook Asaas: chargeback detectado', [
                    'event'      => $event,
                    'tenant_id'  => $tenantId,
                    'payment_id' => $payment['id'] ?? null,
                    'value'      => $payment['value'] ?? null,
                ]);
                AuditLogger::log('webhook_asaas_chargeback', [
                    'usuario_id' => $tenantId,
                    'event'      => $event,
                    'payment_id' => $payment['id'] ?? null,
                    'value'      => $payment['value'] ?? null,
                ]);
                break;

            // Eventos informativos
            case 'PAYMENT_CREATED':
            case 'PAYMENT_UPDATED':
            case 'PAYMENT_DUNNING_RECEIVED':
            case 'PAYMENT_DUNNING_REQUESTED':
                $this->logger->info('Webhook Asaas: evento informativo recebido', [
                    'event'      => $event,
                    'tenant_id'  => $tenantId,
                    'payment_id' => $payment['id'] ?? null,
                ]);
                break;

            // ---------------------------------------------------------------
            // EVENTOS DE NOTA FISCAL DE SERVICO (NFS-e) VIA ASAAS
            // Documentacao: https://docs.asaas.com/docs/webhook-para-notas-fiscais
            // Payload: $data['invoice'] contem os dados da NF
            // ---------------------------------------------------------------

            case 'INVOICE_AUTHORIZED':
                // NFS-e autorizada pela prefeitura — pdfUrl e xmlUrl disponiveis
                try {
                    $invoice     = $data['invoice'] ?? [];
                    $invoiceId   = (string)($invoice['id'] ?? '');
                    $asaasStatus = (string)($invoice['status'] ?? 'AUTHORIZED');
                    $pdfUrl      = $invoice['pdfUrl'] ?? $invoice['invoiceUrl'] ?? null;
                    $xmlUrl      = $invoice['xmlUrl'] ?? null;
                    $numeroNf    = $invoice['number'] ?? null;

                    if (empty($invoiceId)) {
                        $this->logger->warning('Webhook Asaas INVOICE_AUTHORIZED: invoice.id ausente', [
                            'data' => $invoice,
                        ]);
                        break;
                    }

                    $notaModel = new NotaFiscal();

                    // Tenta buscar pelo invoiceId global (sem tenantId)
                    $nota = $notaModel->findByAsaasInvoiceIdGlobal($invoiceId);

                    // Fallback: tenta com tenantId se disponivel
                    if (!$nota && $tenantId) {
                        $nota = $notaModel->findByAsaasInvoiceId($invoiceId, $tenantId);
                    }

                    if (!$nota) {
                        $this->logger->warning('Webhook Asaas INVOICE_AUTHORIZED: NF nao encontrada', [
                            'invoice_id' => $invoiceId,
                            'tenant_id'  => $tenantId,
                        ]);
                        break;
                    }

                    $updateData = [
                        'asaas_status' => $asaasStatus,
                        'status'       => AsaasService::mapearStatusNfsParaBanco($asaasStatus),
                    ];
                    if ($pdfUrl) {
                        $updateData['asaas_pdf_url'] = $pdfUrl;
                    }
                    if ($numeroNf && empty($nota->numero_nf)) {
                        $updateData['numero_nf'] = (string)$numeroNf;
                    }

                    $notaModel->update((int)$nota->id, $updateData);

                    AuditLogger::log('asaas_invoice_authorized', [
                        'nota_id'    => $nota->id,
                        'invoice_id' => $invoiceId,
                        'numero_nf'  => $numeroNf,
                        'pdf_url'    => $pdfUrl ? 'sim' : 'nao',
                    ]);

                    $this->logger->info('Webhook Asaas: NFS-e autorizada e atualizada', [
                        'nota_id'    => $nota->id,
                        'invoice_id' => $invoiceId,
                        'numero_nf'  => $numeroNf,
                        'pdf_url'    => $pdfUrl ? 'sim' : 'nao',
                    ]);
                } catch (\Exception $e) {
                    $this->logger->error('Webhook Asaas INVOICE_AUTHORIZED: erro ao processar', [
                        'error'   => $e->getMessage(),
                        'invoice' => $data['invoice'] ?? [],
                    ]);
                }
                break;

            case 'INVOICE_CANCELED':
                // NFS-e cancelada
                try {
                    $invoice   = $data['invoice'] ?? [];
                    $invoiceId = (string)($invoice['id'] ?? '');

                    if (empty($invoiceId)) {
                        break;
                    }

                    $notaModel = new NotaFiscal();
                    $nota = $notaModel->findByAsaasInvoiceIdGlobal($invoiceId);
                    if (!$nota && $tenantId) {
                        $nota = $notaModel->findByAsaasInvoiceId($invoiceId, $tenantId);
                    }

                    if ($nota) {
                        $notaModel->update((int)$nota->id, [
                            'asaas_status' => 'CANCELED',
                            'status'       => 'cancelada',
                        ]);
                        $this->logger->info('Webhook Asaas: NFS-e cancelada', [
                            'nota_id'    => $nota->id,
                            'invoice_id' => $invoiceId,
                        ]);
                    }
                } catch (\Exception $e) {
                    $this->logger->error('Webhook Asaas INVOICE_CANCELED: erro', [
                        'error' => $e->getMessage(),
                    ]);
                }
                break;

            case 'INVOICE_ERROR':
                // NFS-e com erro na emissao
                try {
                    $invoice   = $data['invoice'] ?? [];
                    $invoiceId = (string)($invoice['id'] ?? '');
                    $errorDesc = $invoice['observations'] ?? $invoice['description'] ?? 'Erro na emissao';

                    if (empty($invoiceId)) {
                        break;
                    }

                    $notaModel = new NotaFiscal();
                    $nota = $notaModel->findByAsaasInvoiceIdGlobal($invoiceId);
                    if (!$nota && $tenantId) {
                        $nota = $notaModel->findByAsaasInvoiceId($invoiceId, $tenantId);
                    }

                    if ($nota) {
                        $notaModel->update((int)$nota->id, [
                            'asaas_status' => 'ERROR',
                            'status'       => 'erro_emissao',
                        ]);
                        $this->logger->warning('Webhook Asaas: NFS-e com erro', [
                            'nota_id'    => $nota->id,
                            'invoice_id' => $invoiceId,
                            'error_desc' => $errorDesc,
                        ]);
                    }
                } catch (\Exception $e) {
                    $this->logger->error('Webhook Asaas INVOICE_ERROR: erro', [
                        'error' => $e->getMessage(),
                    ]);
                }
                break;

            case 'INVOICE_SYNCHRONIZED':
                // NFS-e enviada a prefeitura (aguardando autorizacao)
                try {
                    $invoice   = $data['invoice'] ?? [];
                    $invoiceId = (string)($invoice['id'] ?? '');

                    if (empty($invoiceId)) {
                        break;
                    }

                    $notaModel = new NotaFiscal();
                    $nota = $notaModel->findByAsaasInvoiceIdGlobal($invoiceId);
                    if (!$nota && $tenantId) {
                        $nota = $notaModel->findByAsaasInvoiceId($invoiceId, $tenantId);
                    }

                    if ($nota) {
                        $notaModel->update((int)$nota->id, [
                            'asaas_status' => 'SYNCHRONIZED',
                            'status'       => 'agendada',
                        ]);
                        $this->logger->info('Webhook Asaas: NFS-e sincronizada com prefeitura', [
                            'nota_id'    => $nota->id,
                            'invoice_id' => $invoiceId,
                        ]);
                    }
                } catch (\Exception $e) {
                    $this->logger->error('Webhook Asaas INVOICE_SYNCHRONIZED: erro', [
                        'error' => $e->getMessage(),
                    ]);
                }
                break;

            case 'INVOICE_CREATED':
            case 'INVOICE_UPDATED':
            case 'INVOICE_PROCESSING_CANCELLATION':
            case 'INVOICE_CANCELLATION_DENIED':
                // Eventos informativos de NFS-e — apenas log
                $this->logger->info('Webhook Asaas: evento NFS-e informativo', [
                    'event'      => $event,
                    'invoice_id' => ($data['invoice']['id'] ?? null),
                    'status'     => ($data['invoice']['status'] ?? null),
                ]);
                break;

            // Evento desconhecido
            default:
                $this->logger->warning('Webhook Asaas: evento nao mapeado', [
                    'event'      => $event,
                    'tenant_id'  => $tenantId,
                    'payment_id' => $payment['id'] ?? null,
                ]);
                break;
        }

        http_response_code(200);
        echo json_encode(['received' => true, 'event' => $event]);
    }

    /**
     * Health-check do webhook — GET /api/webhooks/asaas/ping
     *
     * Retorna 200 com informacoes basicas para confirmar que o endpoint
     * esta acessivel antes de configurar no painel Asaas.
     */
    public function webhookPing()
    {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(200);
        echo json_encode([
            'status'    => 'ok',
            'endpoint'  => '/api/webhooks/asaas',
            'method'    => 'POST',
            'timestamp' => date('Y-m-d H:i:s'),
            'server'    => $_SERVER['HTTP_HOST'] ?? 'unknown',
            'message'   => 'Webhook Asaas ativo e pronto para receber eventos.',
        ]);
    }

    // =========================================================================
    // ALERTAS DE E-MAIL
    // =========================================================================

    /**
     * GET /integracao/email — sobrescreve para passar alertas à view
     * (método email() já existe; este método é chamado pelo controller
     *  via rota separada /integracao/email/alertas)
     */
    public function emailAlertas(): void
    {
        if (!Auth::can('manage_settings')) {
            ob_start(); ob_end_clean();
        header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode(['error' => 'unauthorized']);
            exit();
        }

        $usuarioId = (int) Auth::user()->id;
        $model     = new EmailAlerta();
        $alertas   = $model->findAllByUsuario($usuarioId);

        // Agrupa por módulo
        $agrupados = ['financeiro' => [], 'faturamento' => [], 'crm' => [], 'corpo_clinico' => []];
        foreach ($alertas as $a) {
            $agrupados[$a->modulo][] = $a;
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => true, 'alertas' => $agrupados]);
    }

    /**
     * POST /integracao/email/alertas/toggle
     * Ativa ou desativa um alerta.
     */
    public function emailAlertasToggle(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!Auth::can('manage_settings')) {
            http_response_code(403);
            echo json_encode(['error' => 'unauthorized']);
            exit();
        }

        $usuarioId = (int) Auth::user()->id;
        $id        = (int) ($_POST['id'] ?? 0);
        $ativo     = (bool) ($_POST['ativo'] ?? false);

        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'ID inválido']);
            exit();
        }

        $model   = new EmailAlerta();
        $success = $model->toggleAtivo($id, $usuarioId, $ativo);

        AuditLogger::log('email_alerta_toggle', [
            'user_id'   => $usuarioId,
            'alerta_id' => $id,
            'ativo'     => $ativo,
            'success'   => $success,
        ]);

        echo json_encode(['success' => $success]);
    }

    /**
     * POST /integracao/email/alertas/salvar
     * Cria ou atualiza a configuração de um alerta.
     */
    public function emailAlertasSalvar(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!Auth::can('manage_settings')) {
            http_response_code(403);
            echo json_encode(['error' => 'unauthorized']);
            exit();
        }

        $usuarioId = (int) Auth::user()->id;

        $data = [
            'codigo'            => trim($_POST['codigo'] ?? ''),
            'modulo'            => trim($_POST['modulo'] ?? ''),
            'nome'              => trim($_POST['nome'] ?? ''),
            'descricao'         => trim($_POST['descricao'] ?? ''),
            'antecedencia_dias' => (int) ($_POST['antecedencia_dias'] ?? 3),
            'frequencia'        => trim($_POST['frequencia'] ?? 'unico'),
            'hora_disparo'      => trim($_POST['hora_disparo'] ?? '08:00:00'),
            'destinatarios'     => $_POST['destinatarios'] ?? '["admin"]',
            'cc'                => $_POST['cc'] ?? null,
            'assunto_template'  => trim($_POST['assunto_template'] ?? ''),
            'corpo_template'    => trim($_POST['corpo_template'] ?? ''),
            'ativo'             => isset($_POST['ativo']) ? (int) $_POST['ativo'] : 1,
        ];

        if (empty($data['codigo']) || empty($data['modulo']) || empty($data['nome'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Campos obrigatórios ausentes']);
            exit();
        }

        $model   = new EmailAlerta();
        $success = $model->salvar($usuarioId, $data);

        AuditLogger::log('email_alerta_salvar', [
            'user_id' => $usuarioId,
            'codigo'  => $data['codigo'],
            'success' => $success,
        ]);

        echo json_encode(['success' => $success]);
    }

    /**
     * POST /integracao/email/alertas/disparar
     * Dispara manualmente um alerta (para testes).
     */
    public function emailAlertasDisparar(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!Auth::can('manage_settings')) {
            http_response_code(403);
            echo json_encode(['error' => 'unauthorized']);
            exit();
        }

        $usuarioId = (int) Auth::user()->id;
        $alertaId  = (int) ($_POST['alerta_id'] ?? 0);

        if (!$alertaId) {
            http_response_code(400);
            echo json_encode(['error' => 'ID inválido']);
            exit();
        }

        $model  = new EmailAlerta();
        $alerta = $model->findById($alertaId);

        if (!$alerta) {
            http_response_code(404);
            echo json_encode(['error' => 'Alerta não encontrado']);
            exit();
        }

        try {
            $service   = new EmailAlertaService($usuarioId);
            $resultado = $service->processarAlerta($alerta);

            AuditLogger::log('email_alerta_disparo_manual', [
                'user_id'   => $usuarioId,
                'alerta_id' => $alertaId,
                'resultado' => $resultado,
            ]);

            echo json_encode(['success' => true, 'resultado' => $resultado]);
        } catch (\Throwable $e) {
            AuditLogger::log('email_alerta_disparo_erro', [
                'user_id'   => $usuarioId,
                'alerta_id' => $alertaId,
                'error'     => $e->getMessage(),
            ]);
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * GET /integracao/email — atualiza o método email() para incluir alertas na view
     * Sobrescreve o email() original para passar também os alertas agrupados.
     */
    public function emailComAlertas(): void
    {
        if (!Auth::can('manage_settings')) {
            header('Location: /dashboard?error=unauthorized');
            exit();
        }

        $usuarioId = (int) Auth::user()->id;
        $row       = $this->integracaoModel->findByNomeAndUsuarioId('email', $usuarioId);
        $config    = $row ? $this->integracaoModel->getDecodedConfig($row) : [];

        // Carrega alertas com tolerância à ausência da tabela (migration pendente)
        $alertasAgrupados = ['financeiro' => [], 'faturamento' => [], 'crm' => [], 'corpo_clinico' => []];
        try {
            $model   = new EmailAlerta();
            $alertas = $model->findAllByUsuario($usuarioId);
            foreach ($alertas as $a) {
                if (isset($alertasAgrupados[$a->modulo])) {
                    $alertasAgrupados[$a->modulo][] = $a;
                }
            }
        } catch (\Throwable $e) {
            // Tabela email_alertas ainda não existe — migration pendente
            $this->logger->warning('Tabela email_alertas indisponível: ' . $e->getMessage());
        }

        View::render('integracoes/email', [
            'title'            => 'Configuração E-mail',
            'config'           => $config,
            'crypto_configured' => CryptoService::isConfigured(),
            'alertas'          => $alertasAgrupados,
            'breadcrumb'       => [
                'Configurações' => '/configuracoes',
                'Integrações'  => '#',
                'E-mail'        => '/integracao/email'
            ],
            '_layout' => 'erp'
        ]);
    }
}
