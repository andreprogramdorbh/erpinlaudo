<?php
use App\Core\UI;
use App\Core\Auth;
require_once dirname(__DIR__) . '/layout/erp_header.php';

// Header da Seção com Ação (Novo Usuário)
$actions = [];
if (Auth::can('manage_users')) {
    $actions[] = [
        'text' => 'Novo Usuário',
        'link' => '/configuracoes/usuarios/create',
        'icon' => 'fas fa-plus',
        'class' => 'btn-primary'
    ];
}

UI::sectionHeader('Gestão de Usuários', 'Gerencie usuários e permissões de acesso ao sistema', $actions);
?>

<!-- Alertas de Feedback -->
<?php
$error = $_GET['error'] ?? '';
$success = $_GET['success'] ?? '';

if ($error || $success) {
    $alertType = $error ? 'danger' : 'success';
    $alertIcon = $error ? 'exclamation-triangle' : 'check-circle';
    $alertMessage = '';

    if ($error) {
        $messages = [
            'unauthorized' => 'Você não tem permissão para acessar esta área.',
            'cannot_edit' => 'Você não pode gerenciar este usuário.',
            'cannot_reset' => 'Você não pode resetar a senha deste usuário.',
            'exception' => 'Ocorreu um erro inesperado. Tente novamente.'
        ];
        $alertMessage = $messages[$error] ?? 'Erro desconhecido.';
    } else {
        $messages = [
            'user_created' => 'Usuário criado com sucesso! E-mail de boas-vindas enviado.',
            'user_updated' => 'Usuário atualizado com sucesso!',
            'password_reset' => 'E-mail de redefinição de senha enviado com sucesso!'
        ];
        $alertMessage = $messages[$success] ?? 'Operação concluída.';
    }

    echo "<div class='alert alert-{$alertType} alert-dismissible fade show mb-4'>
            <i class='fas fa-{$alertIcon} me-2'></i>
            {$alertMessage}
            <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
          </div>";
}
?>

<!-- Listagem de Usuários -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <?php
        $headers = ['Nome', 'E-mail', 'Cargo', 'Status', 'Data de Criação', 'Ações'];

        $rowRenderer = function ($usuario) use ($current_user) {
            $acoes = '';

            // Editar
            if (Auth::can('manage_users') && $current_user->id != $usuario->id) {
                if ($current_user->role === 'superadmin' || 
                    ($current_user->role === 'admin' && $usuario->role === 'user')) {
                    $acoes .= '<a href="/configuracoes/usuarios/edit/' . (int) $usuario->id . '" 
                                   class="text-primary me-2" title="Editar">
                                   <i class="fas fa-edit"></i>
                               </a>';
                }
            }

            // Resetar Senha
            if (Auth::can('manage_users') && $current_user->id != $usuario->id) {
                if ($current_user->role === 'superadmin' || 
                    ($current_user->role === 'admin' && $usuario->role === 'user')) {
                    $acoes .= '<a href="#" onclick="confirmResetPassword(' . (int) $usuario->id . ', \'' . htmlspecialchars($usuario->name) . '\'); return false;" 
                                   class="text-warning me-2" title="Resetar Senha">
                                   <i class="fas fa-key"></i>
                               </a>';
                }
            }

            // Status Badge
            $status = $usuario->status ?? 'ativo';
            $badgeClass = $status === 'ativo' ? 'bg-success' : 'bg-secondary';
            $badgeText = $status === 'ativo' ? 'Ativo' : 'Inativo';
            $badge = "<span class='badge {$badgeClass}'>{$badgeText}</span>";

            // Role Badge
            $roleClass = $usuario->role === 'superadmin' ? 'bg-danger' : 
                        ($usuario->role === 'admin' ? 'bg-warning' : 'bg-info');
            $roleText = ucfirst($usuario->role);
            $roleBadge = "<span class='badge {$roleClass}'>{$roleText}</span>";

            // Data de criação
            $createdDate = date('d/m/Y', strtotime($usuario->created_at ?? 'now'));

            // Nome com indicador se for o usuário atual
            $nome = htmlspecialchars($usuario->name);
            if ($current_user->id == $usuario->id) {
                $nome .= ' <small class="text-muted">(Você)</small>';
            }

            return '<tr>'
                . '<td>' . $nome . '</td>'
                . '<td>' . htmlspecialchars($usuario->email) . '</td>'
                . '<td>' . $roleBadge . '</td>'
                . '<td>' . $badge . '</td>'
                . '<td>' . $createdDate . '</td>'
                . '<td>' . $acoes . '</td>'
                . '</tr>';
        };

        UI::render('table', [
            'headers' => $headers,
            'items' => $usuarios ?? [],
            'rowRenderer' => $rowRenderer,
            'emptyMessage' => 'Nenhum usuário encontrado.',
            'emptyIcon' => 'fas fa-users-slash'
        ]);
        ?>
    </div>
</div>

<!-- Legendas -->
<div class="card border-0 shadow-sm mt-4">
    <div class="card-body">
        <h6 class="card-title mb-3">
            <i class="fas fa-info-circle me-2"></i>
            Legendas
        </h6>
        <div class="row">
            <div class="col-md-6">
                <h6 class="text-muted small mb-2">Cargos/Níveis de Acesso:</h6>
                <div class="d-flex flex-wrap gap-2">
                    <span class="badge bg-danger">Superadmin</span>
                    <small class="text-muted">Acesso total ao sistema</small>
                    <span class="badge bg-warning">Admin</span>
                    <small class="text-muted">Acesso operacional</small>
                    <span class="badge bg-info">User</span>
                    <small class="text-muted">Acesso básico</small>
                </div>
            </div>
            <div class="col-md-6">
                <h6 class="text-muted small mb-2">Permissões:</h6>
                <ul class="small text-muted mb-0">
                    <li><strong>Superadmin:</strong> Pode gerenciar todos os usuários</li>
                    <li><strong>Admin:</strong> Pode gerenciar apenas usuários do tipo "User"</li>
                    <li><strong>User:</strong> Não acessa esta área</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
function confirmResetPassword(userId, userName) {
    if (confirm('Deseja realmente resetar a senha do usuário "' + userName + '"?\n\nUm e-mail com link de redefinição será enviado.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/configuracoes/usuarios/reset-password/' + userId;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php require_once dirname(__DIR__) . '/layout/erp_footer.php'; ?>
