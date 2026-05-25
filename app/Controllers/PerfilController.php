<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\View;
use App\Core\Auth;
use App\Core\Audit\AuditLogger;
use App\Models\User;
use App\Models\LayoutExame;
use App\Models\EmpresaConfig;

class PerfilController extends Controller
{
    private User $userModel;
    private EmpresaConfig $empresaModel;

    public function __construct()
    {
        $this->userModel    = new User();
        $this->empresaModel = new EmpresaConfig();
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

        $usuario       = $this->userModel->findById((int) $sessionUser->id) ?: $sessionUser;
        $layoutModel   = new LayoutExame();
        $layouts_exame = $layoutModel->allByUser((int)($sessionUser->id ?? 0));
        $empresa       = $this->empresaModel->findByUsuarioId((int) $sessionUser->id);

        View::render('perfil/index', [
            'title'         => 'Meu Perfil',
            'usuario'       => $usuario,
            'active_tab'    => $_GET['tab'] ?? 'geral',
            'layouts_exame' => $layouts_exame,
            'layout_edicao' => null,
            'empresa'       => $empresa ?: null,
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

        $usuarioId    = Auth::user()->id;
        $usuarioAtual = $this->userModel->findById($usuarioId);

        if (!$usuarioAtual || $usuarioAtual->id != $usuarioId) {
            AuditLogger::log('permission_denied', [
                'action'          => 'update_profile',
                'target_user_id'  => $usuarioId,
                'session_user_id' => $usuarioId,
                'error'           => 'User ID mismatch',
            ]);
            header("Location: /perfil?error=unauthorized");
            exit();
        }

        try {
            $nome  = trim($_POST['name']  ?? '');
            $email = trim($_POST['email'] ?? '');

            if (empty($nome) || empty($email)) {
                header("Location: /perfil?error=missing_fields");
                exit();
            }

            $emailExistente = $this->userModel->findByEmail($email);
            if ($emailExistente && $emailExistente->id != $usuarioId) {
                header("Location: /perfil?error=email_exists");
                exit();
            }

            $stmt = $this->userModel->pdo->prepare(
                "UPDATE users SET name = :name, email = :email, updated_at = NOW() WHERE id = :id"
            );
            $success = $stmt->execute([':name' => $nome, ':email' => $email, ':id' => $usuarioId]);

            if ($success) {
                AuditLogger::log('update_profile', [
                    'user_id'   => $usuarioId,
                    'old_name'  => $usuarioAtual->name,
                    'new_name'  => $nome,
                    'old_email' => $usuarioAtual->email,
                    'new_email' => $email,
                ]);
                $_SESSION['user_name']  = $nome;
                $_SESSION['user_email'] = $email;
                header("Location: /perfil?success=profile_updated");
            } else {
                header("Location: /perfil?error=update_failed");
            }
        } catch (\Exception $e) {
            AuditLogger::log('profile_update_exception', [
                'user_id' => $usuarioId,
                'error'   => $e->getMessage(),
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

        $usuarioId    = Auth::user()->id;
        $usuarioAtual = $this->userModel->findById($usuarioId);

        if (!$usuarioAtual || $usuarioAtual->id != $usuarioId) {
            AuditLogger::log('permission_denied', [
                'action'          => 'change_password',
                'target_user_id'  => $usuarioId,
                'session_user_id' => $usuarioId,
                'error'           => 'User ID mismatch',
            ]);
            header("Location: /perfil?tab=seguranca&error=unauthorized");
            exit();
        }

        try {
            $senhaAtual     = $_POST['current_password'] ?? '';
            $novaSenha      = $_POST['new_password']     ?? '';
            $confirmarSenha = $_POST['confirm_password'] ?? '';

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
            if (!password_verify($senhaAtual, $usuarioAtual->password)) {
                AuditLogger::log('password_change_failed', [
                    'user_id' => $usuarioId,
                    'reason'  => 'incorrect_current_password',
                ]);
                header("Location: /perfil?tab=seguranca&error=wrong_current_password");
                exit();
            }

            $novaSenhaHash = password_hash($novaSenha, PASSWORD_DEFAULT);
            $success       = $this->userModel->updatePassword($usuarioId, $novaSenhaHash);

            if ($success) {
                AuditLogger::log('change_password', [
                    'user_id'   => $usuarioId,
                    'timestamp' => date('Y-m-d H:i:s'),
                ]);
                header("Location: /perfil?tab=seguranca&success=password_changed");
            } else {
                header("Location: /perfil?tab=seguranca&error=update_failed");
            }
        } catch (\Exception $e) {
            AuditLogger::log('password_change_exception', [
                'user_id' => $usuarioId,
                'error'   => $e->getMessage(),
            ]);
            header("Location: /perfil?tab=seguranca&error=exception");
        }
        exit();
    }

    // ---------------------------------------------------------------
    // POST /perfil/empresa/update — salva dados da empresa
    // ---------------------------------------------------------------
    public function empresaUpdate(): void
    {
        if (!Auth::check()) { header('Location: /login'); exit(); }

        $usuarioId = (int) Auth::user()->id;

        // Upload do logo (opcional)
        $logoPath = '';
        $empresaAtual = $this->empresaModel->findByUsuarioId($usuarioId);
        $logoPath = $empresaAtual ? ($empresaAtual->logo_path ?? '') : '';

        if (!empty($_FILES['logo']['name']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $file    = $_FILES['logo'];
            $maxSize = 2 * 1024 * 1024; // 2 MB
            $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
            $finfo   = new \finfo(FILEINFO_MIME_TYPE);
            $mime    = $finfo->file($file['tmp_name']) ?: '';
            $ext     = $allowed[$mime] ?? null;

            if ($ext && $file['size'] <= $maxSize) {
                $dir = BASE_PATH . '/storage/uploads/empresa/' . $usuarioId;
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                // Remove logo antigo
                if ($logoPath && file_exists(BASE_PATH . '/' . $logoPath)) {
                    @unlink(BASE_PATH . '/' . $logoPath);
                }
                $fileName = 'logo_' . bin2hex(random_bytes(8)) . '.' . $ext;
                $destPath = $dir . '/' . $fileName;
                if (move_uploaded_file($file['tmp_name'], $destPath)) {
                    $logoPath = 'storage/uploads/empresa/' . $usuarioId . '/' . $fileName;
                }
            }
        }

        $financeiroMesmo = isset($_POST['financeiro_mesmo_responsavel']) ? 1 : 0;
        $emailFinanceiro = $financeiroMesmo
            ? trim($_POST['email_responsavel'] ?? '')
            : trim($_POST['email_financeiro']  ?? '');

        $data = [
            'tipo_pessoa'                    => in_array($_POST['tipo_pessoa'] ?? 'pj', ['pf','pj']) ? $_POST['tipo_pessoa'] : 'pj',
            'razao_social'                   => trim($_POST['razao_social']                ?? ''),
            'nome_fantasia'                  => trim($_POST['nome_fantasia']               ?? ''),
            'cpf_cnpj'                       => preg_replace('/\D/', '', $_POST['cpf_cnpj'] ?? ''),
            'inscricao_estadual'             => trim($_POST['inscricao_estadual']          ?? ''),
            'inscricao_municipal'            => trim($_POST['inscricao_municipal']         ?? ''),
            'email_responsavel'              => trim($_POST['email_responsavel']           ?? ''),
            'email_financeiro'               => $emailFinanceiro,
            'financeiro_mesmo_responsavel'   => $financeiroMesmo,
            'telefone'                       => trim($_POST['telefone']                    ?? ''),
            'site'                           => trim($_POST['site']                        ?? ''),
            'cep'                            => preg_replace('/\D/', '', $_POST['cep']     ?? ''),
            'logradouro'                     => trim($_POST['logradouro']                  ?? ''),
            'numero'                         => trim($_POST['numero']                      ?? ''),
            'complemento'                    => trim($_POST['complemento']                 ?? ''),
            'bairro'                         => trim($_POST['bairro']                      ?? ''),
            'cidade'                         => trim($_POST['cidade']                      ?? ''),
            'estado'                         => strtoupper(substr(trim($_POST['estado']    ?? ''), 0, 2)),
        ];

        if ($logoPath !== '') {
            $data['logo_path'] = $logoPath;
        }

        // ── Assinatura ──────────────────────────────────────────────
        $data['assinatura_nome']        = trim($_POST['assinatura_nome']  ?? '');
        $data['assinatura_cargo']       = trim($_POST['assinatura_cargo'] ?? '');
        $data['assinatura_rubrica']     = trim($_POST['assinatura_rubrica'] ?? '');
        $data['usar_assinatura_imagem'] = isset($_POST['usar_assinatura_imagem']) ? 1 : 0;
        $data['autenticacao_texto']     = trim($_POST['autenticacao_texto'] ?? '');
        $data['autenticacao_ativa']     = isset($_POST['autenticacao_ativa']) ? 1 : 0;

        // Upload da imagem de assinatura (PNG transparente)
        if (!empty($_FILES['assinatura_imagem']['name']) && $_FILES['assinatura_imagem']['error'] === UPLOAD_ERR_OK) {
            $sig     = $_FILES['assinatura_imagem'];
            $finfo   = new \finfo(FILEINFO_MIME_TYPE);
            $mime    = $finfo->file($sig['tmp_name']) ?: '';
            $allowed = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/gif' => 'gif', 'image/webp' => 'webp'];
            $ext     = $allowed[$mime] ?? null;
            if ($ext && $sig['size'] <= 1 * 1024 * 1024) {
                $dir = BASE_PATH . '/storage/uploads/empresa/' . $usuarioId;
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                // Remove imagem antiga
                $empresaAtualSig = $this->empresaModel->findByUsuarioId($usuarioId);
                if ($empresaAtualSig && !empty($empresaAtualSig->assinatura_imagem_path)) {
                    @unlink(BASE_PATH . '/' . $empresaAtualSig->assinatura_imagem_path);
                }
                $sigName = 'assinatura_' . bin2hex(random_bytes(8)) . '.' . $ext;
                if (move_uploaded_file($sig['tmp_name'], $dir . '/' . $sigName)) {
                    $data['assinatura_imagem_path'] = 'storage/uploads/empresa/' . $usuarioId . '/' . $sigName;
                }
            }
        }

        try {
            $ok = $this->empresaModel->upsert($usuarioId, $data);
            AuditLogger::log('empresa_config_salva', ['usuario_id' => $usuarioId, 'ok' => $ok]);
            header('Location: /perfil?tab=empresa&success=empresa_salva');
        } catch (\Exception $e) {
            error_log('[PerfilController::empresaUpdate] ' . $e->getMessage());
            header('Location: /perfil?tab=empresa&error=exception');
        }
        exit();
    }

    /**
     * Salva ou atualiza um layout de importacao de exames
     */
    public function layoutExameStore(): void
    {
        if (!Auth::check()) { header('Location: /login'); exit(); }
        $userId      = (int)(Auth::user()->id ?? 0);
        $layoutModel = new LayoutExame();
        $id          = (int)($_POST['layout_id'] ?? 0);
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
