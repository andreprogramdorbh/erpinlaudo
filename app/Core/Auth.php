<?php

namespace App\Core;

use App\Core\Audit\AuditLogger;
use App\Core\Permission;

class Auth
{
    /**
     * Gera um hash de senha seguro.
     *
     * @param string $password A senha em texto plano.
     * @return string O hash da senha.
     */
    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_ARGON2ID);
    }

    /**
     * Verifica se uma senha corresponde a um hash.
     *
     * @param string $password A senha em texto plano.
     * @param string $hash O hash da senha armazenado.
     * @return bool True se a senha for válida, false caso contrário.
     */
    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Tenta realizar o login do usuário.
     */
    public static function login(string $email, string $password): bool
    {
        $userModel = new \App\Models\User();
        $user = $userModel->findByEmail($email);

        if ($user && self::verifyPassword($password, $user->password)) {
            // SUCESSO
            self::regenerateSession();
            $_SESSION['user_id'] = $user->id;
            $_SESSION['user_name'] = $user->name;
            $_SESSION['user_email'] = $user->email;
            $_SESSION['user_role'] = $user->role ?? 'user'; // Buscar role real do banco
            $_SESSION['login_time'] = time();

            AuditLogger::log('login_success', ['user_id' => $user->id, 'email' => $email, 'role' => $_SESSION['user_role']]);

            return true;
        }

        AuditLogger::log('login_failed', ['email' => $email]);
        return false;
    }

    /**
     * Verifica se o usuário está autenticado.
     */
    public static function check(): bool
    {
        return isset($_SESSION['user_id']);
    }

    /**
     * Retorna os dados do usuário logado.
     */
    public static function user(): ?object
    {
        if (!self::check())
            return null;

        return (object) [
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'],
            'email' => $_SESSION['user_email'] ?? null,
            'role' => $_SESSION['user_role']
        ];
    }

    /**
     * Finaliza a sessão do usuário.
     */
    public static function logout(): void
    {
        AuditLogger::log('logout');
        session_unset();
        session_destroy();

        // Limpa cookie de sessão se existir
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
    }

    /**
     * Regenera o ID da sessão para prevenir ataques de fixação de sessão.
     */
    private static function regenerateSession(): void
    {
        session_regenerate_id(true);
    }

    /**
     * Verifica se o usuário autenticado tem permissão para uma ação.
     */
    public static function can(string $permission): bool
    {
        if (!self::check()) {
            return false;
        }

        $role = $_SESSION['user_role'] ?? '';
        $provider = new Permission();
        $permissions = $provider->getPermissionsForRole($role);

        return in_array($permission, $permissions);
    }

    /**
     * Verifica se o usuário tem um papel específico.
     */
    public static function hasRole(string $role): bool
    {
        return ($_SESSION['user_role'] ?? '') === strtolower($role);
    }
}
