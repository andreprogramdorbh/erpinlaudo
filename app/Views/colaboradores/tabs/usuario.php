<?php
/**
 * Aba: Usuário do Sistema Vinculado ao Colaborador
 */
use App\Core\Auth;
$colaborador = $colaborador ?? null;
$usuarios    = $usuarios    ?? [];
$vinculado   = $colaborador->user_id ?? null;
$canManage   = Auth::can('manage_settings');
?>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom py-3">
        <h5 class="mb-0 fw-bold"><i class="fas fa-user-lock text-primary me-2"></i>Usuário do Sistema</h5>
    </div>
    <div class="card-body">
        <p class="text-muted mb-3">
            Vincule este colaborador a um usuário do sistema para que ele possa acessar o ERP com as permissões do perfil atribuído.
            O colaborador ficará <strong>restrito às permissões do usuário vinculado</strong>.
        </p>

        <?php if (!$canManage): ?>
            <div class="alert alert-warning">
                <i class="fas fa-lock me-2"></i>Apenas administradores podem vincular usuários a colaboradores.
            </div>
        <?php else: ?>

        <!-- Status atual do vínculo -->
        <?php if ($vinculado): ?>
            <div class="alert alert-success d-flex align-items-center gap-3 mb-4">
                <i class="fas fa-check-circle fa-2x"></i>
                <div>
                    <strong>Usuário vinculado:</strong>
                    <?php echo htmlspecialchars($colaborador->usuario_nome ?? 'ID ' . $vinculado); ?><br>
                    <small class="text-muted"><?php echo htmlspecialchars($colaborador->usuario_email ?? ''); ?></small>
                    <?php if ($colaborador->usuario_role ?? null): ?>
                        <br><span class="badge bg-primary mt-1"><?php echo ucfirst($colaborador->usuario_role); ?></span>
                        <span class="badge <?php echo ($colaborador->usuario_status ?? 'ativo') === 'ativo' ? 'bg-success' : 'bg-secondary'; ?> mt-1">
                            <?php echo ucfirst($colaborador->usuario_status ?? 'ativo'); ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-secondary mb-4">
                <i class="fas fa-user-slash me-2"></i>Nenhum usuário vinculado a este colaborador.
            </div>
        <?php endif; ?>

        <!-- Formulário de vínculo -->
        <div class="row g-3 align-items-end">
            <div class="col-md-6">
                <label class="form-label fw-bold">Selecionar Usuário</label>
                <select id="selectUserId" class="form-select">
                    <option value="0">— Nenhum (desvincular) —</option>
                    <?php foreach ($usuarios as $u): ?>
                        <option value="<?php echo (int)$u->id; ?>"
                            <?php echo (int)($vinculado ?? 0) === (int)$u->id ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($u->name); ?> (<?php echo htmlspecialchars($u->email); ?>)
                            — <?php echo ucfirst($u->role ?? 'user'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small class="text-muted">Somente usuários do seu sistema são listados.</small>
            </div>
            <div class="col-md-3 d-grid">
                <button type="button" class="btn btn-primary fw-bold" onclick="salvarVinculo()">
                    <i class="fas fa-link me-1"></i> Salvar Vínculo
                </button>
            </div>
        </div>

        <hr class="my-4">

        <!-- Informativo sobre permissões -->
        <div class="alert alert-info">
            <h6 class="fw-bold"><i class="fas fa-info-circle me-2"></i>Como funciona o vínculo?</h6>
            <ul class="mb-0 small">
                <li>O colaborador acessa o sistema com o <strong>e-mail e senha do usuário vinculado</strong>.</li>
                <li>As permissões são determinadas pelo <strong>perfil (role) do usuário</strong>: Operador, Financeiro, Admin, etc.</li>
                <li>Para criar um novo usuário, acesse <a href="/configuracoes?tab=usuarios" target="_blank">Configurações → Usuários</a>.</li>
                <li>Para alterar as permissões do usuário, edite-o em <a href="/configuracoes?tab=usuarios" target="_blank">Configurações → Usuários</a>.</li>
            </ul>
        </div>

        <?php endif; ?>
    </div>
</div>

<?php if ($canManage): ?>
<script>
function salvarVinculo() {
    const userId = document.getElementById('selectUserId').value;
    const colId  = <?php echo (int)($colaborador->id ?? 0); ?>;
    fetch('/colaboradores/vincular-usuario/' + colId, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'user_id=' + userId
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            alert('Erro: ' + (data.error || 'Erro desconhecido'));
        }
    })
    .catch(() => alert('Erro ao salvar vínculo.'));
}
</script>
<?php endif; ?>
