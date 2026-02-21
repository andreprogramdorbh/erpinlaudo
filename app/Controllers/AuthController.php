<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Logger;
use App\Core\Controller;
use App\Core\View;
use App\Core\Mail;
use App\Core\Audit\AuditLogger;
use App\Models\User;
use App\Models\PasswordResetToken;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            header("Location: /dashboard");
            exit();
        }

        $title = 'Acesso ao ERP';

        require dirname(__DIR__) . '/Views/auth/login.php';
    }

    public function login()
    {
        $logger = new Logger();
        $email = $_POST["email"] ?? "";
        $password = $_POST["password"] ?? "";

        $logger->auth("Tentativa de login", ["email" => $email]);

        if (Auth::login($email, $password)) {
            $logger->auth("Login bem-sucedido", [
                "user_id" => $_SESSION["user_id"],
                "email" => $email
            ]);
            header("Location: /dashboard");
            exit();
        }

        $logger->auth("Falha no login - Credenciais inválidas", ["email" => $email]);
        header("Location: /login?error=1");
        exit();
    }

    public function logout()
    {
        Auth::logout();
        header("Location: /login");
        exit();
    }

    /**
     * Exibe o formulário de solicitação de redefinição de senha (layout público).
     */
    public function showForgotPasswordForm()
    {
        if (Auth::check()) {
            header("Location: /dashboard");
            exit();
        }
        $title = 'Esqueci minha senha';
        $sent = $_GET['sent'] ?? null;
        require dirname(__DIR__) . '/Views/auth/forgot_password.php';
    }

    /**
     * Processa a solicitação: gera token (hash no banco), envia e-mail, mensagem genérica.
     * Nunca informa se o e-mail existe ou não. Nunca registra token em log.
     */
    public function forgotPassword()
    {
        if (Auth::check()) {
            header("Location: /dashboard");
            exit();
        }

        $email = trim($_POST['email'] ?? '');
        if ($email === '') {
            header("Location: /forgot-password?error=1");
            exit();
        }

        $userModel = new User();
        $user = $userModel->findByEmail($email);

        if ($user) {
            $tokenModel = new PasswordResetToken();
            $result = $tokenModel->createForUser((int) $user->id);
            $rawToken = $result['raw'];
            $baseUrl = rtrim($_ENV['APP_URL'] ?? ($_SERVER['REQUEST_SCHEME'] . '://' . ($_SERVER['HTTP_HOST'] ?? '')), '/');
            $resetUrl = $baseUrl . '/reset-password/' . $rawToken;

            AuditLogger::log('forgot_password_requested', ['user_id' => $user->id]);

            Mail::sendPasswordResetLink($user->email, $resetUrl, (int) $user->id);
        }

        header("Location: /forgot-password?sent=1");
        exit();
    }

    /**
     * Exibe o formulário de redefinição de senha (layout público). Token na URL.
     */
    public function showResetPasswordForm(string $token)
    {
        if (Auth::check()) {
            header("Location: /dashboard");
            exit();
        }

        $tokenHash = hash('sha256', $token);
        $tokenModel = new PasswordResetToken();
        $record = $tokenModel->findValidByTokenHash($tokenHash);

        if (!$record) {
            AuditLogger::log('password_reset_failed', ['reason' => 'invalid_or_expired_token']);
            header("Location: /login?reset=invalid");
            exit();
        }

        $title = 'Redefinir senha';
        $tokenValue = $token;
        require dirname(__DIR__) . '/Views/auth/reset_password.php';
    }

    /**
     * Atualiza a senha: valida token, invalida após uso, redireciona para login.
     */
    public function resetPassword(string $token)
    {
        if (Auth::check()) {
            header("Location: /dashboard");
            exit();
        }

        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        $minLength = 8;
        if (strlen($password) < $minLength) {
            header("Location: /reset-password/" . $token . "?error=short");
            exit();
        }
        if ($password !== $passwordConfirm) {
            header("Location: /reset-password/" . $token . "?error=mismatch");
            exit();
        }

        $tokenHash = hash('sha256', $token);
        $tokenModel = new PasswordResetToken();
        $record = $tokenModel->findValidByTokenHash($tokenHash);

        if (!$record) {
            AuditLogger::log('password_reset_failed', ['reason' => 'invalid_or_expired_token']);
            header("Location: /login?reset=invalid");
            exit();
        }

        $tokenModel->markAsUsed((int) $record->id);
        $userModel = new User();
        $hashedPassword = Auth::hashPassword($password);
        $updated = $userModel->updatePassword((int) $record->user_id, $hashedPassword);

        if ($updated) {
            AuditLogger::log('password_reset_success', ['user_id' => $record->user_id]);
            header("Location: /login?reset=success");
            exit();
        }

        AuditLogger::log('password_reset_failed', ['reason' => 'update_failed', 'user_id' => $record->user_id]);
        header("Location: /reset-password/" . $token . "?error=1");
        exit();
    }
}
