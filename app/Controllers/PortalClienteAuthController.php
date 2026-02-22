<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\View;
use App\Core\Logger;
use App\Core\Audit\AuditLogger;
use App\Models\PortalCliente;

/**
 * Controller de autenticação do Portal do Cliente.
 * Gerencia: login, primeiro acesso (definição de senha), logout.
 */
class PortalClienteAuthController extends Controller
{
    private PortalCliente $portalModel;
    private Logger $logger;

    public function __construct()
    {
        $this->portalModel = new PortalCliente();
        $this->logger = new Logger();
    }

    // ---------------------------------------------------------------
    // GET /login
    // ---------------------------------------------------------------
    public function showLogin(): void
    {
        // Se já estiver logado no portal, redireciona para o dashboard
        if (!empty($_SESSION['portal_cliente_id'])) {
            header('Location: /portal/dashboard');
            exit();
        }

        View::render('portal/auth/login', [
            'title'   => 'Área do Cliente',
            '_layout' => 'portal_public',
        ]);
    }

    // ---------------------------------------------------------------
    // POST /login
    // ---------------------------------------------------------------
    public function login(): void
    {
        $email = strtolower(trim($_POST['email'] ?? ''));
        $senha = $_POST['senha'] ?? '';
        $ip    = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        $this->logger->info('[Portal] Tentativa de login', [
            'email' => $email,
            'ip'    => $ip,
            'ua'    => $_SERVER['HTTP_USER_AGENT'] ?? '',
        ]);

        if (empty($email) || empty($senha)) {
            header('Location: /login?error=campos_obrigatorios');
            exit();
        }

        $portal = $this->portalModel->findByEmail($email);

        if (!$portal) {
            $this->logger->warning('[Portal] Login falhou — e-mail não encontrado', ['email' => $email, 'ip' => $ip]);
            AuditLogger::log('portal_login_failed', ['email' => $email, 'motivo' => 'email_nao_encontrado', 'ip' => $ip]);
            header('Location: /login?error=credenciais');
            exit();
        }

        // Primeiro acesso: redireciona para definir senha
        if ($portal->primeiro_acesso || empty($portal->password_hash)) {
            $this->logger->info('[Portal] Primeiro acesso detectado', ['email' => $email, 'cliente_id' => $portal->cliente_id]);
            $token = $this->portalModel->criarToken((int) $portal->cliente_id, 'primeiro_acesso');
            header("Location: /primeiro-acesso?token={$token}");
            exit();
        }

        // Verifica senha com ARGON2ID (compatível com Auth::hashPassword)
        if (!password_verify($senha, $portal->password_hash)) {
            $this->logger->warning('[Portal] Login falhou — senha incorreta', [
                'email'       => $email,
                'ip'          => $ip,
                'hash_prefix' => substr($portal->password_hash, 0, 10),
            ]);
            AuditLogger::log('portal_login_failed', ['email' => $email, 'motivo' => 'senha_incorreta', 'ip' => $ip]);
            header('Location: /login?error=credenciais');
            exit();
        }

        // Sucesso — inicia sessão do portal
        session_regenerate_id(true);
        $_SESSION['portal_cliente_id']   = (int) $portal->id;
        $_SESSION['portal_cliente_nome'] = $portal->razao_social ?? $portal->nome_fantasia ?? $email;
        $_SESSION['portal_cliente_email']= $email;
        $_SESSION['portal_tenant_id']    = (int) $portal->tenant_id;
        $_SESSION['portal_login_time']   = time();

        $this->portalModel->registrarAcesso((int) $portal->id);

        AuditLogger::log('portal_login_success', [
            'portal_id'  => $portal->id,
            'cliente_id' => $portal->cliente_id,
            'email'      => $email,
            'ip'         => $ip,
        ]);

        $this->logger->info('[Portal] Login bem-sucedido', ['email' => $email, 'portal_id' => $portal->id]);

        header('Location: /portal/dashboard');
        exit();
    }

    // ---------------------------------------------------------------
    // GET /primeiro-acesso
    // ---------------------------------------------------------------
    public function showPrimeiroAcesso(): void
    {
        $token = trim($_GET['token'] ?? '');

        if (empty($token)) {
            header('Location: /login?error=token_invalido');
            exit();
        }

        $tokenData = $this->portalModel->validarToken($token, 'primeiro_acesso');

        if (!$tokenData) {
            $this->logger->warning('[Portal] Token de primeiro acesso inválido ou expirado', ['token_prefix' => substr($token, 0, 8)]);
            header('Location: /login?error=token_expirado');
            exit();
        }

        View::render('portal/auth/primeiro-acesso', [
            'title'     => 'Criar Senha — Área do Cliente',
            '_layout'   => 'portal_public',
            'token'     => htmlspecialchars($token),
            'email'     => htmlspecialchars($tokenData->email),
        ]);
    }

    // ---------------------------------------------------------------
    // POST /primeiro-acesso
    // ---------------------------------------------------------------
    public function salvarPrimeiroAcesso(): void
    {
        $token  = trim($_POST['token'] ?? '');
        $senha  = $_POST['senha'] ?? '';
        $senha2 = $_POST['senha_confirmacao'] ?? '';

        if (empty($token)) {
            header('Location: /login?error=token_invalido');
            exit();
        }

        $tokenData = $this->portalModel->validarToken($token, 'primeiro_acesso');

        if (!$tokenData) {
            header('Location: /login?error=token_expirado');
            exit();
        }

        // Validações de senha
        if (strlen($senha) < 8) {
            header("Location: /primeiro-acesso?token={$token}&error=senha_curta");
            exit();
        }

        if ($senha !== $senha2) {
            header("Location: /primeiro-acesso?token={$token}&error=senhas_diferentes");
            exit();
        }

        // Hash ARGON2ID (mesmo algoritmo do Auth::hashPassword)
        $hash = password_hash($senha, PASSWORD_ARGON2ID);

        $this->portalModel->definirSenha((int) $tokenData->portal_id, $hash);
        $this->portalModel->consumirToken($token);

        $this->logger->info('[Portal] Primeiro acesso concluído', [
            'portal_id'  => $tokenData->portal_id,
            'cliente_id' => $tokenData->cliente_id,
        ]);
        AuditLogger::log('portal_primeiro_acesso_concluido', [
            'portal_id'  => $tokenData->portal_id,
            'cliente_id' => $tokenData->cliente_id,
        ]);

        // Loga automaticamente após definir a senha
        $portal = $this->portalModel->findById((int) $tokenData->portal_id);
        if ($portal) {
            session_regenerate_id(true);
            $_SESSION['portal_cliente_id']    = (int) $portal->id;
            $_SESSION['portal_cliente_nome']  = $portal->razao_social ?? $portal->nome_fantasia ?? $portal->email;
            $_SESSION['portal_cliente_email'] = $portal->email;
            $_SESSION['portal_tenant_id']     = (int) $portal->tenant_id;
            $_SESSION['portal_login_time']    = time();
            $this->portalModel->registrarAcesso((int) $portal->id);
        }

        header('Location: /portal/dashboard?welcome=1');
        exit();
    }

    // ---------------------------------------------------------------
    // POST /portal/logout
    // ---------------------------------------------------------------
    public function logout(): void
    {
        AuditLogger::log('portal_logout', [
            'portal_id' => $_SESSION['portal_cliente_id'] ?? null,
        ]);

        // Remove apenas as chaves do portal, preservando sessão ERP se existir
        unset(
            $_SESSION['portal_cliente_id'],
            $_SESSION['portal_cliente_nome'],
            $_SESSION['portal_cliente_email'],
            $_SESSION['portal_tenant_id'],
            $_SESSION['portal_login_time']
        );

        header('Location: /login?logout=1');
        exit();
    }
}
