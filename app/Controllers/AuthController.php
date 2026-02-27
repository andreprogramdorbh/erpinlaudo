<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Logger;
use App\Core\Controller;
use App\Core\Mail;
use App\Core\Audit\AuditLogger;
use App\Models\User;
use App\Models\PasswordResetToken;
use App\Models\PortalCliente;

/**
 * AuthController — Autenticação unificada para usuários do ERP e clientes do portal.
 *
 * Fluxo de login:
 *  1. Tenta autenticar como usuário do ERP (tabela users).
 *     → Sucesso: redireciona para /dashboard
 *  2. Se não encontrado no ERP, tenta autenticar como cliente do portal (tabela portal_clientes).
 *     → Sucesso: inicia sessão de portal e redireciona para /portal/dashboard
 *  3. Se nenhum dos dois: erro de credenciais.
 *
 * Fluxo de primeiro acesso (clientes do portal):
 *  1. Cliente informa o e-mail → sistema verifica se existe em clientes.email_principal.
 *  2. Se encontrado, gera token e exibe formulário de criação de senha.
 *  3. Cliente cria senha → sistema salva hash, inicia sessão e redireciona para /portal/dashboard.
 */
class AuthController extends Controller
{
    // =========================================================
    // LOGIN UNIFICADO
    // =========================================================

    public function showLoginForm(): void
    {
        // Se já autenticado como usuário ERP, vai para o dashboard
        if (Auth::check()) {
            header('Location: /dashboard');
            exit();
        }
        // Se já autenticado como cliente do portal, vai para o portal
        if (!empty($_SESSION['portal_cliente_id'])) {
            header('Location: /portal/dashboard');
            exit();
        }
        $title = 'Acesso ao Sistema';
        require dirname(__DIR__) . '/Views/auth/login.php';
    }

    public function login(): void
    {
        $logger = new Logger();
        $email    = trim($_POST['email']    ?? '');
        $password = trim($_POST['password'] ?? '');
        $ip        = $_SERVER['REMOTE_ADDR']     ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

        $logger->auth('Tentativa de login unificado', [
            'email'      => $email,
            'ip'         => $ip,
            'user_agent' => $userAgent,
        ]);

        // ----------------------------------------------------------
        // 1. Tenta autenticar como usuário do ERP
        // ----------------------------------------------------------
        $userModel = new User();
        $user      = $userModel->findByEmail($email);

        if ($user) {
            if (Auth::login($email, $password)) {
                $logger->auth('Login ERP bem-sucedido', [
                    'user_id' => $_SESSION['user_id'],
                    'email'   => $email,
                    'ip'      => $ip,
                ]);
                AuditLogger::log('login_success', [
                    'user_id' => $_SESSION['user_id'],
                    'email'   => $email,
                    'tipo'    => 'erp',
                    'ip'      => $ip,
                ]);
                header('Location: /dashboard');
                exit();
            }

            // E-mail existe no ERP mas senha errada
            $logger->auth('Falha no login ERP — senha incorreta', [
                'email'       => $email,
                'ip'          => $ip,
                'hash_prefix' => substr($user->password ?? '', 0, 7),
            ]);
            AuditLogger::log('login_failed', [
                'email'  => $email,
                'motivo' => 'senha_incorreta_erp',
                'ip'     => $ip,
            ]);
            header('Location: /login?error=credenciais');
            exit();
        }

        // ----------------------------------------------------------
        // 2. Tenta autenticar como cliente do portal
        // ----------------------------------------------------------
        $portalModel  = new PortalCliente();
        $portalCliente = $portalModel->findByEmail($email);

        if ($portalCliente) {
            // Primeiro acesso: senha ainda não foi criada
            if ($portalCliente->primeiro_acesso || empty($portalCliente->password_hash)) {
                $logger->auth('Login portal — primeiro acesso pendente', ['email' => $email]);
                header('Location: /primeiro-acesso?email=' . urlencode($email));
                exit();
            }

            // Conta inativa
            if (!$portalCliente->ativo) {
                $logger->auth('Login portal — conta inativa', ['email' => $email]);
                AuditLogger::log('login_failed', [
                    'email'  => $email,
                    'motivo' => 'conta_portal_inativa',
                    'ip'     => $ip,
                ]);
                header('Location: /login?error=conta_inativa');
                exit();
            }

            // Verifica senha
            if (password_verify($password, $portalCliente->password_hash)) {
                // Inicia sessão do portal
                session_regenerate_id(true);
                $_SESSION['portal_cliente_id']   = (int) $portalCliente->id;
                $_SESSION['portal_cliente_nome']  = $portalCliente->nome_fantasia ?? $portalCliente->razao_social ?? 'Cliente';
                $_SESSION['portal_cliente_email'] = $portalCliente->email;
                $_SESSION['portal_login_time']    = time();

                $portalModel->registrarAcesso((int) $portalCliente->id);

                $logger->auth('Login portal bem-sucedido', [
                    'portal_id' => $portalCliente->id,
                    'email'     => $email,
                    'ip'        => $ip,
                ]);
                AuditLogger::log('login_success', [
                    'portal_id' => $portalCliente->id,
                    'email'     => $email,
                    'tipo'      => 'portal',
                    'ip'        => $ip,
                ]);
                header('Location: /portal/dashboard');
                exit();
            }

            // Senha incorreta no portal
            $logger->auth('Falha no login portal — senha incorreta', [
                'email'       => $email,
                'ip'          => $ip,
                'hash_prefix' => substr($portalCliente->password_hash ?? '', 0, 7),
            ]);
            AuditLogger::log('login_failed', [
                'email'  => $email,
                'motivo' => 'senha_incorreta_portal',
                'ip'     => $ip,
            ]);
            header('Location: /login?error=credenciais');
            exit();
        }

        // ----------------------------------------------------------
        // 3. E-mail não encontrado em nenhum dos dois sistemas
        // ----------------------------------------------------------
        $logger->auth('Falha no login — e-mail não encontrado', [
            'email' => $email,
            'ip'    => $ip,
        ]);
        AuditLogger::log('login_failed', [
            'email'  => $email,
            'motivo' => 'email_nao_encontrado',
            'ip'     => $ip,
        ]);
        header('Location: /login?error=credenciais');
        exit();
    }

    public function logout(): void
    {
        // Logout do ERP
        if (Auth::check()) {
            AuditLogger::log('logout', ['user_id' => $_SESSION['user_id'] ?? null]);
            Auth::logout();
        }
        // Logout do portal (caso ambas as sessões coexistam por algum motivo)
        if (!empty($_SESSION['portal_cliente_id'])) {
            AuditLogger::log('logout_portal', ['portal_id' => $_SESSION['portal_cliente_id']]);
            session_unset();
            session_destroy();
        }
        header('Location: /login?logout=1');
        exit();
    }

    // =========================================================
    // PRIMEIRO ACESSO — CLIENTES DO PORTAL
    // =========================================================

    /**
     * Etapa 1: exibe formulário para o cliente informar o e-mail.
     * Etapa 2: se token válido na URL, exibe formulário de criação de senha.
     */
    public function showPrimeiroAcesso(): void
    {
        if (!empty($_SESSION['portal_cliente_id'])) {
            header('Location: /portal/dashboard');
            exit();
        }

        $token       = null;
        $nomeCliente = null;
        $erro        = null;

        // Etapa 2: token na URL
        $tokenParam = trim($_GET['token'] ?? '');
        if ($tokenParam !== '') {
            $portalModel = new PortalCliente();
            $registro    = $portalModel->validarToken($tokenParam, 'primeiro_acesso');
            if (!$registro) {
                header('Location: /primeiro-acesso?error=token_invalido');
                exit();
            }
            $token = $tokenParam;
            // Busca nome do cliente para personalizar a tela
            $portalDados = $portalModel->findById((int) $registro->portal_id);
            $nomeCliente = $portalDados->nome_fantasia ?? $portalDados->razao_social ?? 'Cliente';
        }

        $title = 'Primeiro Acesso';
        require dirname(__DIR__) . '/Views/auth/primeiro_acesso.php';
    }

    /**
     * Etapa 1 — POST: valida e-mail contra clientes.email_principal,
     * cria registro no portal_clientes (se não existir) e gera token.
     */
    public function processarPrimeiroAcesso(): void
    {
        $logger = new Logger();
        $email  = trim(strtolower($_POST['email'] ?? ''));

        if ($email === '') {
            header('Location: /primeiro-acesso?error=email_nao_encontrado');
            exit();
        }

        $portalModel = new PortalCliente();

        // Verifica se o e-mail existe como E-mail Principal de algum cliente
        $portalCliente = $portalModel->findByEmail($email);

        if (!$portalCliente) {
            // Tenta criar o registro a partir da tabela de clientes
            $clienteModel = new \App\Models\Cliente();
            $cliente      = $clienteModel->findByEmail($email);

            if (!$cliente) {
                $logger->warning('[PrimeiroAcesso] E-mail não encontrado em clientes', ['email' => $email]);
                header('Location: /primeiro-acesso?error=email_nao_encontrado');
                exit();
            }

            // Cria o registro no portal_clientes
            $portalModel->upsert((int) $cliente->id, $email);
            $portalCliente = $portalModel->findByEmail($email);
        }

        // Se já tem senha definida, redireciona para login
        if ($portalCliente && !$portalCliente->primeiro_acesso && !empty($portalCliente->password_hash)) {
            $logger->info('[PrimeiroAcesso] Cliente já possui senha — redirecionando para login', ['email' => $email]);
            header('Location: /login');
            exit();
        }

        // Gera token de primeiro acesso
        $token = $portalModel->criarToken((int) $portalCliente->cliente_id, 'primeiro_acesso');
        $logger->info('[PrimeiroAcesso] Token gerado', ['portal_id' => $portalCliente->id, 'cliente_id' => $portalCliente->cliente_id, 'email' => $email]);
        AuditLogger::log('portal_primeiro_acesso_solicitado', ['portal_id' => $portalCliente->id, 'email' => $email]);

        // Redireciona para etapa 2 com o token
        header('Location: /primeiro-acesso?token=' . urlencode($token));
        exit();
    }

    /**
     * Etapa 2 — POST: valida token, salva senha e inicia sessão do portal.
     */
    public function salvarPrimeiroAcesso(): void
    {
        $logger = new Logger();
        $token        = trim($_POST['token']             ?? '');
        $senha        = $_POST['senha']                  ?? '';
        $confirmacao  = $_POST['senha_confirmacao']      ?? '';

        if ($token === '') {
            header('Location: /primeiro-acesso?error=token_invalido');
            exit();
        }

        if (strlen($senha) < 8) {
            header('Location: /primeiro-acesso?token=' . urlencode($token) . '&error=senha_curta');
            exit();
        }

        if ($senha !== $confirmacao) {
            header('Location: /primeiro-acesso?token=' . urlencode($token) . '&error=senhas_diferentes');
            exit();
        }

        $portalModel = new PortalCliente();
        $registro    = $portalModel->validarToken($token, 'primeiro_acesso');

        if (!$registro) {
            $logger->warning('[PrimeiroAcesso] Token inválido ou expirado ao salvar senha', ['token_prefix' => substr($token, 0, 8)]);
            header('Location: /primeiro-acesso?error=token_invalido');
            exit();
        }

        // Salva a senha com ARGON2ID
        $hash = password_hash($senha, PASSWORD_ARGON2ID);
        $ok = $portalModel->definirSenha((int) $registro->portal_id, $hash);
        if (!$ok) {
            $logger->error('[PrimeiroAcesso] Falha ao salvar senha no banco', [
                'portal_id'    => (int) $registro->portal_id,
                'cliente_id'   => (int) ($registro->cliente_id ?? 0),
                'token_prefix' => substr($token, 0, 8),
            ]);
            header('Location: /primeiro-acesso?token=' . urlencode($token) . '&error=salvar_falhou');
            exit();
        }
        $portalModel->consumirToken($token);

        $logger->info('[PrimeiroAcesso] Senha criada com sucesso', ['portal_id' => $registro->portal_id]);
        AuditLogger::log('portal_senha_criada', ['portal_id' => $registro->portal_id]);

        // Inicia sessão do portal automaticamente
        $portalDados = $portalModel->findById((int) $registro->portal_id);
        if (!$portalDados) {
            $logger->error('[PrimeiroAcesso] Senha salva, mas portal_clientes nao encontrado', [
                'portal_id' => (int) $registro->portal_id,
            ]);
            header('Location: /login?error=sessao_expirada');
            exit();
        }
        session_regenerate_id(true);
        $_SESSION['portal_cliente_id']   = (int) $registro->portal_id;
        $_SESSION['portal_cliente_nome']  = $portalDados->nome_fantasia ?? $portalDados->razao_social ?? 'Cliente';
        $_SESSION['portal_cliente_email'] = $portalDados->email ?? '';
        $_SESSION['portal_login_time']    = time();

        $portalModel->registrarAcesso((int) $registro->portal_id);

        // Redireciona direto para o portal
        header('Location: /portal/dashboard');
        exit();
    }

    // =========================================================
    // RECUPERAÇÃO DE SENHA (usuários ERP)
    // =========================================================

    public function showForgotPasswordForm(): void
    {
        if (Auth::check()) {
            header('Location: /dashboard');
            exit();
        }
        $title = 'Esqueci minha senha';
        $sent  = $_GET['sent'] ?? null;
        require dirname(__DIR__) . '/Views/auth/forgot_password.php';
    }

    public function forgotPassword(): void
    {
        if (Auth::check()) {
            header('Location: /dashboard');
            exit();
        }
        $email = trim($_POST['email'] ?? '');
        if ($email === '') {
            header('Location: /forgot-password?error=1');
            exit();
        }
        $userModel = new User();
        $user      = $userModel->findByEmail($email);
        if ($user) {
            $tokenModel = new PasswordResetToken();
            $result     = $tokenModel->createForUser((int) $user->id);
            $rawToken   = $result['raw'];
            $baseUrl    = rtrim($_ENV['APP_URL'] ?? ($_SERVER['REQUEST_SCHEME'] . '://' . ($_SERVER['HTTP_HOST'] ?? '')), '/');
            $resetUrl   = $baseUrl . '/reset-password/' . $rawToken;
            AuditLogger::log('forgot_password_requested', ['user_id' => $user->id]);
            Mail::sendPasswordResetLink($user->email, $resetUrl, (int) $user->id);
        }
        header('Location: /forgot-password?sent=1');
        exit();
    }

    public function showResetPasswordForm(string $token): void
    {
        if (Auth::check()) {
            header('Location: /dashboard');
            exit();
        }
        $tokenHash  = hash('sha256', $token);
        $tokenModel = new PasswordResetToken();
        $record     = $tokenModel->findValidByTokenHash($tokenHash);
        if (!$record) {
            AuditLogger::log('password_reset_failed', ['reason' => 'invalid_or_expired_token']);
            header('Location: /login?reset=invalid');
            exit();
        }
        $title      = 'Redefinir senha';
        $tokenValue = $token;
        require dirname(__DIR__) . '/Views/auth/reset_password.php';
    }

    public function resetPassword(string $token): void
    {
        if (Auth::check()) {
            header('Location: /dashboard');
            exit();
        }
        $password        = $_POST['password']         ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';
        if (strlen($password) < 8) {
            header('Location: /reset-password/' . $token . '?error=short');
            exit();
        }
        if ($password !== $passwordConfirm) {
            header('Location: /reset-password/' . $token . '?error=mismatch');
            exit();
        }
        $tokenHash  = hash('sha256', $token);
        $tokenModel = new PasswordResetToken();
        $record     = $tokenModel->findValidByTokenHash($tokenHash);
        if (!$record) {
            AuditLogger::log('password_reset_failed', ['reason' => 'invalid_or_expired_token']);
            header('Location: /login?reset=invalid');
            exit();
        }
        $tokenModel->markAsUsed((int) $record->id);
        $userModel      = new User();
        $hashedPassword = Auth::hashPassword($password);
        $updated        = $userModel->updatePassword((int) $record->user_id, $hashedPassword);
        if ($updated) {
            AuditLogger::log('password_reset_success', ['user_id' => $record->user_id]);
            header('Location: /login?reset=success');
            exit();
        }
        AuditLogger::log('password_reset_failed', ['reason' => 'update_failed', 'user_id' => $record->user_id]);
        header('Location: /reset-password/' . $token . '?error=1');
        exit();
    }
}
