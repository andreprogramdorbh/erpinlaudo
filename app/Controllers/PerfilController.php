<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\View;
use App\Core\Auth;
use App\Core\Audit\AuditLogger;
use App\Models\User;
use App\Models\LayoutExame;

class PerfilController extends Controller
{
    private User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    /**
     * Exibe o perfil do usuário logado com abas enterprise
     */
    public function index(): void
    {
        $sessionUser = Auth::user();
         
        if (!$sessionUser) {
            header("Location: /login");
            exit();
        }

        $usuario = $this->userModel->findById((int) $sessionUser->id) ?: $sessionUser;

        $layoutModel = new LayoutExame();
        $layouts_exame = $layoutModel->allByUser((int)($sessionUser->id ?? 0));
        View::render('perfil/index', [
            'title' => 'Meu Perfil',
            'usuario' => $usuario,
            'active_tab' => $_GET['tab'] ?? 'geral',
            'layouts_exame' => $layouts_exame,
            'layout_edicao' => null
        ]);
    }

    /**
     * Atualiza dados básicos do perfil (nome, email)
     */
    public function update(): void
    {
        if (!Auth::check()) {
            header("Location: /login");
            exit();
        }

        $usuarioId = Auth::user()->id;
        $usuarioAtual = $this->userModel->findById($usuarioId);

        if (!$usuarioAtual || $usuarioAtual->id != $usuarioId) {
            AuditLogger::log('permission_denied', [
                'action' => 'update_profile',
                'target_user_id' => $usuarioId,
                'session_user_id' => $usuarioId,
                'error' => 'User ID mismatch'
            ]);
            header("Location: /perfil?error=unauthorized");
            exit();
        }

        try {
            $nome = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');

            // Validações básicas
            if (empty($nome) || empty($email)) {
                header("Location: /perfil?error=missing_fields");
                exit();
            }

            // Verifica se email já existe (exceto o próprio)
            $emailExistente = $this->userModel->findByEmail($email);
            if ($emailExistente && $emailExistente->id != $usuarioId) {
                header("Location: /perfil?error=email_exists");
                exit();
            }

            // Atualiza dados
            $stmt = $this->userModel->pdo->prepare(
                "UPDATE users SET name = :name, email = :email, updated_at = NOW() WHERE id = :id"
            );
            $success = $stmt->execute([
                ':name' => $nome,
                ':email' => $email,
                ':id' => $usuarioId
            ]);

            if ($success) {
                AuditLogger::log('update_profile', [
                    'user_id' => $usuarioId,
                    'old_name' => $usuarioAtual->name,
                    'new_name' => $nome,
                    'old_email' => $usuarioAtual->email,
                    'new_email' => $email
                ]);

                // Atualiza sessão
                $_SESSION['user_name'] = $nome;
                $_SESSION['user_email'] = $email;

                header("Location: /perfil?success=profile_updated");
            } else {
                header("Location: /perfil?error=update_failed");
            }
        } catch (\Exception $e) {
            AuditLogger::log('profile_update_exception', [
                'user_id' => $usuarioId,
                'error' => $e->getMessage()
            ]);
            header("Location: /perfil?error=exception");
        }
        exit();
    }

    /**
     * Altera senha do usuário (requer senha atual)
     */
    public function changePassword(): void
    {
        if (!Auth::check()) {
            header("Location: /login");
            exit();
        }

        $usuarioId = Auth::user()->id;
        $usuarioAtual = $this->userModel->findById($usuarioId);

        if (!$usuarioAtual || $usuarioAtual->id != $usuarioId) {
            AuditLogger::log('permission_denied', [
                'action' => 'change_password',
                'target_user_id' => $usuarioId,
                'session_user_id' => $usuarioId,
                'error' => 'User ID mismatch'
            ]);
            header("Location: /perfil?tab=seguranca&error=unauthorized");
            exit();
        }

        try {
            $senhaAtual = $_POST['current_password'] ?? '';
            $novaSenha = $_POST['new_password'] ?? '';
            $confirmarSenha = $_POST['confirm_password'] ?? '';

            // Validações
            if (empty($senhaAtual) || empty($novaSenha) || empty($confirmarSenha)) {
                header("Location: /perfil?tab=seguranca&error=missing_fields");
                exit();
            }

            if ($novaSenha !== $confirmarSenha) {
                header("Location: /perfil?tab=seguranca&error=password_mismatch");
                exit();
            }

            if (strlen($novaSenha) < 6) {
                header("Location: /perfil?tab=seguranca&error=password_too_short");
                exit();
            }

            // Verifica senha atual
            if (!password_verify($senhaAtual, $usuarioAtual->password)) {
                AuditLogger::log('password_change_failed', [
                    'user_id' => $usuarioId,
                    'reason' => 'incorrect_current_password'
                ]);
                header("Location: /perfil?tab=seguranca&error=wrong_current_password");
                exit();
            }

            // Atualiza senha
            $novaSenhaHash = password_hash($novaSenha, PASSWORD_DEFAULT);
            $success = $this->userModel->updatePassword($usuarioId, $novaSenhaHash);

            if ($success) {
                AuditLogger::log('change_password', [
                    'user_id' => $usuarioId,
                    'timestamp' => date('Y-m-d H:i:s')
                ]);

                header("Location: /perfil?tab=seguranca&success=password_changed");
            } else {
                header("Location: /perfil?tab=seguranca&error=update_failed");
            }
        } catch (\Exception $e) {
            AuditLogger::log('password_change_exception', [
                'user_id' => $usuarioId,
                'error' => $e->getMessage()
            ]);
            header("Location: /perfil?tab=seguranca&error=exception");
        }
        exit();
    }

    /**
     * Salva ou atualiza um layout de importacao de exames
     */
    public function layoutExameStore(): void
    {
        if (!Auth::check()) { header('Location: /login'); exit(); }
        $userId = (int)(Auth::user()->id ?? 0);
        $layoutModel = new LayoutExame();
        $id = (int)($_POST['layout_id'] ?? 0);
        $data = [
            'usuario_id'            => $userId,
            'nome'                  => trim($_POST['nome'] ?? ''),
            'separador'             => $_POST['separador'] ?? ';',
            'linha_cabecalho'       => (int)($_POST['linha_cabecalho'] ?? 1),
            'ativo'                 => (int)($_POST['ativo'] ?? 1),
            'col_medico'            => trim($_POST['col_medico'] ?? ''),
            'col_crm'               => trim($_POST['col_crm'] ?? ''),
            'col_modalidade'        => trim($_POST['col_modalidade'] ?? ''),
            'col_study_description' => trim($_POST['col_study_description'] ?? ''),
            'col_prioridade'        => trim($_POST['col_prioridade'] ?? ''),
            'col_data_conclusao'    => trim($_POST['col_data_conclusao'] ?? ''),
            'col_paciente'          => trim($_POST['col_paciente'] ?? ''),
            'col_paciente_id'       => trim($_POST['col_paciente_id'] ?? ''),
            'col_unidade'           => trim($_POST['col_unidade'] ?? ''),
            'col_accession'         => trim($_POST['col_accession'] ?? ''),
            'col_convenio'          => trim($_POST['col_convenio'] ?? ''),
            'col_valor_exame'       => trim($_POST['col_valor_exame'] ?? ''),
            'col_revisor'           => trim($_POST['col_revisor'] ?? ''),
            'col_data_revisao'      => trim($_POST['col_data_revisao'] ?? ''),
            'valores_urgencia'      => trim($_POST['valores_urgencia'] ?? 'URGENTE,U,URGENT'),
            'formato_data'          => $_POST['formato_data'] ?? 'd/m/Y H:i',
        ];
        if (empty($data['nome'])) {
            header('Location: /perfil?tab=layout_exames&error=nome_obrigatorio');
            exit();
        }
        try {
            if ($id > 0) {
                $layoutModel->update($id, $userId, $data);
            } else {
                $layoutModel->insert($data);
            }
            header('Location: /perfil?tab=layout_exames&success=layout_salvo');
        } catch (\Exception $e) {
            error_log('[PerfilController::layoutExameStore] ' . $e->getMessage());
            header('Location: /perfil?tab=layout_exames&error=exception');
        }
        exit();
    }

    /**
     * Exclui um layout de importacao de exames
     */
    public function layoutExameDelete(): void
    {
        if (!Auth::check()) { header('Location: /login'); exit(); }
        $userId = (int)(Auth::user()->id ?? 0);
        $id     = (int)($this->param('id') ?? 0);
        if ($id > 0) {
            $layoutModel = new LayoutExame();
            $layoutModel->delete($id, $userId);
        }
        header('Location: /perfil?tab=layout_exames&success=layout_excluido');
        exit();
    }

}