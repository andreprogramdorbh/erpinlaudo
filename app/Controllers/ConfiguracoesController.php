<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\View;
use App\Core\Auth;
use App\Core\Audit\AuditLogger;
use App\Models\User;
use App\Models\PasswordResetToken;
use App\Models\ConfigNfs;
use App\Services\MailService;

class ConfiguracoesController extends Controller
{
    private User $userModel;
    private MailService $mailService;
    private PasswordResetToken $passwordResetModel;
    private ConfigNfs $configNfsModel;

    public function __construct()
    {
        $this->userModel          = new User();
        $this->mailService        = new MailService();
        $this->passwordResetModel = new PasswordResetToken();
        $this->configNfsModel     = new ConfigNfs();
    }

    public function index(): void
    {
        if (!Auth::can('manage_settings')) {
            header("Location: /dashboard?error=unauthorized");
            exit();
        }

        $activeTab  = $_GET['tab'] ?? 'geral';
        $usuarios   = Auth::can('manage_users') ? $this->userModel->findAll() : [];
        $configNfs  = $this->configNfsModel->findByUsuarioId((int) Auth::user()->id);

        View::render('configuracoes/index', [
            'title'       => 'Configurações',
            'activeTab'   => $activeTab,
            'usuarios'    => $usuarios,
            'currentUser' => Auth::user(),
            'configNfs'   => $configNfs,
        ]);
    }

    public function usuariosCreate(): void
    {
        if (!Auth::can('manage_users')) {
            header("Location: /configuracoes?error=unauthorized");
            exit();
        }
        View::render('configuracoes/usuarios/create', [
            'title'       => 'Novo Usuário',
            'currentUser' => Auth::user(),
        ]);
    }

    public function usuariosStore(): void
    {
        if (!Auth::can('manage_users')) {
            header("Location: /configuracoes?error=unauthorized");
            exit();
        }
        $currentUser = Auth::user();
        try {
            $nome        = trim($_POST['name']  ?? '');
            $email       = trim($_POST['email'] ?? '');
            $role        = $_POST['role']   ?? 'user';
            $status      = $_POST['status'] ?? 'ativo';
            $sendWelcome = isset($_POST['send_welcome']);

            if (empty($nome) || empty($email)) {
                header("Location: /configuracoes/usuarios/create?error=missing_fields"); exit();
            }
            if ($currentUser->role === 'admin' && in_array($role, ['admin', 'superadmin'])) {
                header("Location: /configuracoes/usuarios/create?error=invalid_role"); exit();
            }
            if ($this->userModel->findByEmail($email)) {
                header("Location: /configuracoes/usuarios/create?error=email_exists"); exit();
            }

            $tempPassword   = bin2hex(random_bytes(8));
            $hashedPassword = password_hash($tempPassword, PASSWORD_DEFAULT);
            $newId = $this->userModel->create([
                'name' => $nome, 'email' => $email,
                'password' => $hashedPassword, 'role' => $role,
            ]);

            if ($newId) {
                if ($status === 'inativo') {
                    $this->userModel->pdo->prepare("UPDATE users SET status = 'inativo' WHERE id = ?")->execute([$newId]);
                }
                AuditLogger::log('create_user', ['created_by' => $currentUser->id, 'new_user_id' => $newId, 'name' => $nome, 'email' => $email, 'role' => $role]);
                if ($sendWelcome) {
                    $this->passwordResetModel->invalidateUserTokens((int)$newId);
                    $token = bin2hex(random_bytes(32));
                    $this->passwordResetModel->create((int)$newId, $token);
                    $resetLink = "http://" . $_SERVER['HTTP_HOST'] . "/reset-password/{$token}";
                    $this->mailService->sendPasswordResetEmail($email, $nome, $resetLink);
                }
                header("Location: /configuracoes?tab=usuarios&success=user_created");
            } else {
                header("Location: /configuracoes/usuarios/create?error=create_failed");
            }
        } catch (\Exception $e) {
            AuditLogger::log('user_create_exception', ['error' => $e->getMessage()]);
            header("Location: /configuracoes/usuarios/create?error=exception");
        }
        exit();
    }

    public function usuariosEdit(int $id): void
    {
        if (!Auth::can('manage_users')) {
            header("Location: /configuracoes?error=unauthorized"); exit();
        }
        $usuario     = $this->userModel->findById($id);
        $currentUser = Auth::user();
        if (!$usuario || !$this->canManageUser($currentUser, $usuario)) {
            header("Location: /configuracoes?tab=usuarios&error=cannot_edit"); exit();
        }
        View::render('configuracoes/usuarios/edit', [
            'title'       => 'Editar Usuário',
            'usuario'     => $usuario,
            'currentUser' => $currentUser,
        ]);
    }

    public function usuariosUpdate(int $id): void
    {
        if (!Auth::can('manage_users')) {
            header("Location: /configuracoes?error=unauthorized"); exit();
        }
        $usuario     = $this->userModel->findById($id);
        $currentUser = Auth::user();
        if (!$usuario || !$this->canManageUser($currentUser, $usuario)) {
            header("Location: /configuracoes?tab=usuarios&error=cannot_edit"); exit();
        }
        try {
            $nome   = trim($_POST['name']  ?? '');
            $email  = trim($_POST['email'] ?? '');
            $role   = $_POST['role']   ?? $usuario->role;
            $status = $_POST['status'] ?? 'ativo';
            if (empty($nome) || empty($email)) {
                header("Location: /configuracoes/usuarios/edit/{$id}?error=missing_fields"); exit();
            }
            if ($currentUser->role === 'admin' && in_array($role, ['admin', 'superadmin'])) {
                header("Location: /configuracoes/usuarios/edit/{$id}?error=invalid_role"); exit();
            }
            $emailExistente = $this->userModel->findByEmail($email);
            if ($emailExistente && $emailExistente->id != $id) {
                header("Location: /configuracoes/usuarios/edit/{$id}?error=email_exists"); exit();
            }
            $stmt = $this->userModel->pdo->prepare(
                "UPDATE users SET name = :name, email = :email, role = :role, status = :status, updated_at = NOW() WHERE id = :id"
            );
            $success = $stmt->execute([':name' => $nome, ':email' => $email, ':role' => $role, ':status' => $status, ':id' => $id]);
            if ($success) {
                AuditLogger::log('update_user', ['updated_by' => $currentUser->id, 'user_id' => $id, 'old_name' => $usuario->name, 'new_name' => $nome, 'old_role' => $usuario->role, 'new_role' => $role]);
                header("Location: /configuracoes?tab=usuarios&success=user_updated");
            } else {
                header("Location: /configuracoes/usuarios/edit/{$id}?error=update_failed");
            }
        } catch (\Exception $e) {
            AuditLogger::log('user_update_exception', ['error' => $e->getMessage(), 'user_id' => $id]);
            header("Location: /configuracoes/usuarios/edit/{$id}?error=exception");
        }
        exit();
    }

    public function usuariosResetPassword(int $id): void
    {
        if (!Auth::can('manage_users')) {
            header("Location: /configuracoes?error=unauthorized"); exit();
        }
        $usuario     = $this->userModel->findById($id);
        $currentUser = Auth::user();
        if (!$usuario || !$this->canManageUser($currentUser, $usuario)) {
            header("Location: /configuracoes?tab=usuarios&error=cannot_reset"); exit();
        }
        try {
            $this->passwordResetModel->invalidateUserTokens($id);
            $token     = bin2hex(random_bytes(32));
            $this->passwordResetModel->create($id, $token);
            $resetLink = "http://" . $_SERVER['HTTP_HOST'] . "/reset-password/{$token}";
            $emailSent = $this->mailService->sendPasswordResetEmail($usuario->email, $usuario->name, $resetLink);
            AuditLogger::log('reset_user_password', ['reset_by' => $currentUser->id, 'user_id' => $id, 'email_sent' => $emailSent]);
            header("Location: /configuracoes?tab=usuarios&success=password_reset");
        } catch (\Exception $e) {
            AuditLogger::log('password_reset_exception', ['error' => $e->getMessage(), 'user_id' => $id]);
            header("Location: /configuracoes?tab=usuarios&error=reset_failed");
        }
        exit();
    }

    public function usuariosToggleStatus(int $id): void
    {
        header('Content-Type: application/json');
        if (!Auth::can('manage_users')) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']); exit();
        }
        $usuario     = $this->userModel->findById($id);
        $currentUser = Auth::user();
        if (!$usuario || !$this->canManageUser($currentUser, $usuario) || $usuario->id == $currentUser->id) {
            echo json_encode(['success' => false, 'error' => 'Cannot toggle this user']); exit();
        }
        $novoStatus = ($usuario->status ?? 'ativo') === 'ativo' ? 'inativo' : 'ativo';
        $stmt = $this->userModel->pdo->prepare("UPDATE users SET status = ?, updated_at = NOW() WHERE id = ?");
        $ok   = $stmt->execute([$novoStatus, $id]);
        AuditLogger::log('toggle_user_status', ['toggled_by' => $currentUser->id, 'user_id' => $id, 'new_status' => $novoStatus]);
        echo json_encode(['success' => $ok, 'status' => $novoStatus]);
        exit();
    }

    private function canManageUser($currentUser, $targetUser): bool
    {
        if ($currentUser->role === 'superadmin') return true;
        if ($currentUser->role === 'admin') return !in_array($targetUser->role, ['admin', 'superadmin']);
        return false;
    }

    // ================================================================
    // CONFIGURAÇÕES DE NOTAS FISCAIS (NFS-e Nacional)
    // ================================================================

    /**
     * POST /configuracoes/nfs/salvar
     * Salva as configurações de emissão de NFS-e (Layout Padrão ou Personalizado).
     */
    public function nfsSalvar(): void
    {
        if (!Auth::can('manage_settings')) {
            header('Location: /configuracoes?error=unauthorized');
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /configuracoes?tab=notas-fiscais');
            exit();
        }

        $usuarioId = (int) Auth::user()->id;

        $data = [
            'layout_tipo'            => in_array($_POST['layout_tipo'] ?? '', ['padrao', 'personalizado'])
                                        ? $_POST['layout_tipo']
                                        : 'padrao',
            'service_description'    => trim($_POST['service_description'] ?? 'SERVIÇOS DE LAUDO'),
            'observations'           => trim($_POST['observations'] ?? ''),
            'municipal_service_name' => trim($_POST['municipal_service_name'] ?? 'Serviços de Saúde / Radiologia'),
            'municipal_service_code' => trim($_POST['municipal_service_code'] ?? ''),
            'municipal_service_id'   => trim($_POST['municipal_service_id'] ?? ''),
            'cnae'                   => preg_replace('/\D/', '', $_POST['cnae'] ?? '8640205'),
            'deductions'             => (float) ($_POST['deductions'] ?? 0),
            'retain_iss'             => isset($_POST['retain_iss']) ? 1 : 0,
            'iss_aliquota'           => (float) ($_POST['iss_aliquota'] ?? 0),
            'pis_aliquota'           => (float) ($_POST['pis_aliquota'] ?? 0),
            'cofins_aliquota'        => (float) ($_POST['cofins_aliquota'] ?? 0),
            'csll_aliquota'          => (float) ($_POST['csll_aliquota'] ?? 0),
            'inss_aliquota'          => (float) ($_POST['inss_aliquota'] ?? 0),
            'ir_aliquota'            => (float) ($_POST['ir_aliquota'] ?? 0),
            'json_template'          => trim($_POST['json_template'] ?? ''),
            'emite_portal_nacional'  => 1,
            'serie_nf'               => trim($_POST['serie_nf'] ?? ''),
        ];

        // Validar JSON template se layout personalizado
        if ($data['layout_tipo'] === 'personalizado' && !empty($data['json_template'])) {
            $decoded = json_decode($data['json_template'], true);
            if (!is_array($decoded)) {
                header('Location: /configuracoes?tab=notas-fiscais&error=json_invalido');
                exit();
            }
        }

        $ok = $this->configNfsModel->upsert($usuarioId, $data);

        if ($ok) {
            header('Location: /configuracoes?tab=notas-fiscais&success=nfs_salvo');
        } else {
            header('Location: /configuracoes?tab=notas-fiscais&error=save_failed');
        }
        exit();
    }
}
