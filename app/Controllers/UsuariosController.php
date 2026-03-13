<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\View;
use App\Core\Auth;
use App\Core\Audit\AuditLogger;
use App\Models\User;
use App\Models\PasswordResetToken;
use App\Services\MailService;

class UsuariosController extends Controller
{
    private User $userModel;
    private PasswordResetToken $passwordResetModel;
    private MailService $mailService;

    public function __construct()
    {
        $this->userModel = new User();
        $this->passwordResetModel = new PasswordResetToken();
        $this->mailService = new MailService();
    }

    /**
     * Lista todos os usuários (apenas superadmin/admin)
     */
    public function index(): void
    {
        if (!Auth::can('manage_users')) {
            AuditLogger::log('permission_denied', [
                'action' => 'access_users_list',
                'user_id' => Auth::user()->id,
                'user_role' => Auth::user()->role,
                'error' => 'Insufficient permissions'
            ]);
            header("Location: /dashboard?error=unauthorized");
            exit();
        }

        $currentUser = Auth::user();
        $usuarios = $this->userModel->findAll();

        // Filtra usuários que podem ser gerenciados pelo usuário atual
        $usuariosGerenciaveis = array_filter($usuarios, function($usuario) use ($currentUser) {
            if ($currentUser->role === 'superadmin') {
                return true; // Superadmin pode gerenciar todos
            } elseif ($currentUser->role === 'admin') {
                // Admin não pode gerenciar outros admins ou superadmins
                return in_array($usuario->role, ['user']);
            }
            return false;
        });

        View::render('usuarios/index', [
            'title' => 'Gestão de Usuários',
            'usuarios' => $usuariosGerenciaveis,
            'current_user' => $currentUser
        ]);
    }

    /**
     * Exibe formulário de criação de usuário
     */
    public function create(): void
    {
        if (!Auth::can('manage_users')) {
            AuditLogger::log('permission_denied', [
                'action' => 'create_user_form',
                'user_id' => Auth::user()->id,
                'error' => 'Insufficient permissions'
            ]);
            header("Location: /dashboard?error=unauthorized");
            exit();
        }

        View::render('usuarios/create', [
            'title' => 'Novo Usuário',
            'current_user' => Auth::user()
        ]);
    }

    /**
     * Armazena novo usuário e envia e-mail de boas-vindas
     */
    public function store(): void
    {
        if (!Auth::can('manage_users')) {
            header("Location: /dashboard?error=unauthorized");
            exit();
        }

        try {
            $nome = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $role = $_POST['role'] ?? 'user';
            $currentUser = Auth::user();

            // Validações
            if (empty($nome) || empty($email)) {
                header("Location: /configuracoes/usuarios/create?error=missing_fields");
                exit();
            }

            // Validação de permissão de role
            if ($currentUser->role === 'admin' && in_array($role, ['admin', 'superadmin'])) {
                AuditLogger::log('permission_denied', [
                    'action' => 'assign_role',
                    'target_role' => $role,
                    'user_id' => $currentUser->id,
                    'error' => 'Admin cannot assign admin or superadmin roles'
                ]);
                header("Location: /configuracoes/usuarios/create?error=invalid_role");
                exit();
            }

            // Verifica se email já existe
            if ($this->userModel->findByEmail($email)) {
                header("Location: /configuracoes/usuarios/create?error=email_exists");
                exit();
            }

            // Cria usuário com senha temporária
            $senhaTemporaria = bin2hex(random_bytes(4)); // 8 caracteres
            $senhaHash = password_hash($senhaTemporaria, PASSWORD_DEFAULT);

            $userId = $this->userModel->create([
                'name' => $nome,
                'email' => $email,
                'password' => $senhaHash,
                'role' => $role
            ]);

            if ($userId) {
                // Gera token de reset para primeiro acesso
                $token = bin2hex(random_bytes(32));
                $this->passwordResetModel->create($userId, $token);

                // Envia e-mail de boas-vindas
                $resetLink = "http://" . $_SERVER['HTTP_HOST'] . "/reset-password?token=" . $token;
                $emailSent = $this->mailService->sendWelcomeEmail($email, $nome, $resetLink, $senhaTemporaria);

                AuditLogger::log('create_user', [
                    'created_by' => $currentUser->id,
                    'user_id' => $userId,
                    'name' => $nome,
                    'email' => $email,
                    'role' => $role,
                    'email_sent' => $emailSent
                ]);

                header("Location: /configuracoes/usuarios?success=user_created");
            } else {
                header("Location: /configuracoes/usuarios/create?error=create_failed");
            }
        } catch (\Exception $e) {
            AuditLogger::log('user_creation_exception', [
                'error' => $e->getMessage(),
                'user_id' => Auth::user()->id
            ]);
            header("Location: /configuracoes/usuarios/create?error=exception");
        }
        exit();
    }

    /**
     * Exibe formulário de edição de usuário
     */
    public function edit(int $id): void
    {
        if (!Auth::can('manage_users')) {
            header("Location: /dashboard?error=unauthorized");
            exit();
        }

        $usuario = $this->userModel->findById($id);
        $currentUser = Auth::user();

        if (!$usuario || !$this->canManageUser($currentUser, $usuario)) {
            AuditLogger::log('permission_denied', [
                'action' => 'edit_user',
                'target_user_id' => $id,
                'user_id' => $currentUser->id,
                'error' => 'Cannot manage this user'
            ]);
            header("Location: /configuracoes/usuarios?error=cannot_edit");
            exit();
        }

        View::render('usuarios/edit', [
            'title' => 'Editar Usuário',
            'usuario' => $usuario,
            'current_user' => $currentUser
        ]);
    }

    /**
     * Atualiza dados do usuário
     */
    public function update(int $id): void
    {
        if (!Auth::can('manage_users')) {
            header("Location: /dashboard?error=unauthorized");
            exit();
        }

        $usuario = $this->userModel->findById($id);
        $currentUser = Auth::user();

        if (!$usuario || !$this->canManageUser($currentUser, $usuario)) {
            header("Location: /configuracoes/usuarios?error=cannot_edit");
            exit();
        }

        try {
            $nome = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $role = $_POST['role'] ?? $usuario->role;
            $status = $_POST['status'] ?? 'ativo';

            // Validações
            if (empty($nome) || empty($email)) {
                header("Location: /configuracoes/usuarios/edit/{$id}?error=missing_fields");
                exit();
            }

            // Validação de permissão de role
            if ($currentUser->role === 'admin' && in_array($role, ['admin', 'superadmin'])) {
                header("Location: /configuracoes/usuarios/edit/{$id}?error=invalid_role");
                exit();
            }

            // Verifica se email já existe (exceto o próprio)
            $emailExistente = $this->userModel->findByEmail($email);
            if ($emailExistente && $emailExistente->id != $id) {
                header("Location: /configuracoes/usuarios/edit/{$id}?error=email_exists");
                exit();
            }

            // Atualiza dados via método encapsulado no modelo
            $success = $this->userModel->update($id, [
                'name'   => $nome,
                'email'  => $email,
                'role'   => $role,
                'status' => $status,
            ]);

            if ($success) {
                AuditLogger::log('update_user', [
                    'updated_by' => $currentUser->id,
                    'user_id' => $id,
                    'old_name' => $usuario->name,
                    'new_name' => $nome,
                    'old_email' => $usuario->email,
                    'new_email' => $email,
                    'old_role' => $usuario->role,
                    'new_role' => $role,
                    'old_status' => $usuario->status ?? 'ativo',
                    'new_status' => $status
                ]);

                header("Location: /configuracoes/usuarios?success=user_updated");
            } else {
                header("Location: /configuracoes/usuarios/edit/{$id}?error=update_failed");
            }
        } catch (\Exception $e) {
            AuditLogger::log('user_update_exception', [
                'error' => $e->getMessage(),
                'target_user_id' => $id,
                'user_id' => $currentUser->id
            ]);
            header("Location: /configuracoes/usuarios/edit/{$id}?error=exception");
        }
        exit();
    }

    /**
     * Reseta a senha de um usuário (envia e-mail de reset)
     */
    public function resetPassword(int $id): void
    {
        if (!Auth::can('manage_users')) {
            header("Location: /dashboard?error=unauthorized");
            exit();
        }

        $usuario = $this->userModel->findById($id);
        $currentUser = Auth::user();

        if (!$usuario || !$this->canManageUser($currentUser, $usuario)) {
            header("Location: /configuracoes/usuarios?error=cannot_reset");
            exit();
        }

        try {
            // Invalida tokens anteriores
            $this->passwordResetModel->invalidateUserTokens($id);

            // Gera novo token
            $token = bin2hex(random_bytes(32));
            $this->passwordResetModel->create($id, $token);

            // Envia e-mail de reset
            $resetLink = "http://" . $_SERVER['HTTP_HOST'] . "/reset-password?token=" . $token;
            $emailSent = $this->mailService->sendPasswordResetEmail($usuario->email, $usuario->name, $resetLink);

            AuditLogger::log('reset_user_password', [
                'reset_by' => $currentUser->id,
                'user_id' => $id,
                'email' => $usuario->email,
                'email_sent' => $emailSent
            ]);

            header("Location: /configuracoes/usuarios?success=password_reset");
        } catch (\Exception $e) {
            AuditLogger::log('password_reset_exception', [
                'error' => $e->getMessage(),
                'target_user_id' => $id,
                'user_id' => $currentUser->id
            ]);
            header("Location: /configuracoes/usuarios?error=reset_failed");
        }
        exit();
    }

    /**
     * Verifica se o usuário atual pode gerenciar o usuário alvo
     */
    private function canManageUser($currentUser, $targetUser): bool
    {
        if ($currentUser->role === 'superadmin') {
            return true; // Superadmin pode gerenciar todos
        } elseif ($currentUser->role === 'admin') {
            // Admin não pode gerenciar outros admins ou superadmins
            return in_array($targetUser->role, ['user']);
        }
        return false;
    }
}
