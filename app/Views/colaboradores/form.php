<?php
/**
 * ERP InLaudo — Formulário de Colaboradores
 * Abas: Dados Gerais | Anexos | Comissões | Faturamento | Usuário
 */
use App\Core\Auth;

$colaborador = $colaborador ?? null;
$isEdit      = !empty($colaborador);
$activeTab   = $_GET['tab'] ?? 'geral';
$anexos      = $anexos      ?? [];
$comissoes   = $comissoes   ?? [];
$faturamentos = $faturamentos ?? [];
$usuarios    = $usuarios    ?? [];

$success = $_GET['success'] ?? '';
$error   = $_GET['error']   ?? '';
$msgs = [
    'created'  => ['success', 'Colaborador cadastrado com sucesso.'],
    'updated'  => ['success', 'Dados atualizados com sucesso.'],
    'uploaded' => ['success', 'Anexo enviado com sucesso.'],
];
$errs = [
    'cpf_cnpj_exists' => 'CPF/CNPJ já cadastrado para outro colaborador.',
    'missing_fields'  => 'Preencha todos os campos obrigatórios.',
    'upload'          => 'Erro ao enviar o arquivo. Verifique o tamanho (máx. 10 MB) e tente novamente.',
    'update_failed'   => 'Falha ao atualizar. Tente novamente.',
    'create_failed'   => 'Falha ao criar colaborador. Tente novamente.',
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<!-- O layout ERP já é incluído pelo View::render; este arquivo é o conteúdo da página -->
<div class="container-fluid px-0">

<?php if ($success && isset($msgs[$success])): [$type, $msg] = $msgs[$success]; ?>
    <div class="alert alert-<?php echo $type; ?> alert-dismissible fade show mb-3" role="alert">
        <i class="fas fa-check-circle me-2"></i><?php echo $msg; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if ($error && isset($errs[$error])): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i><?php echo $errs[$error]; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Cabeçalho da página -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h4 fw-bold mb-0">
            <i class="fas fa-users-gear text-primary me-2"></i>
            <?php echo $isEdit ? 'Editar Colaborador' : 'Novo Colaborador'; ?>
        </h1>
        <?php if ($isEdit): ?>
            <small class="text-muted"><?php echo htmlspecialchars($colaborador->nome); ?></small>
        <?php endif; ?>
    </div>
    <a href="/colaboradores" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-arrow-left me-1"></i> Voltar
    </a>
</div>

<!-- Abas de navegação -->
<ul class="nav nav-tabs mb-4" id="colabTabs" role="tablist">
    <li class="nav-item">
        <a class="nav-link <?php echo $activeTab === 'geral' ? 'active' : ''; ?>"
           href="<?php echo $isEdit ? '/colaboradores/edit/' . (int)$colaborador->id . '?tab=geral' : '#'; ?>"
           onclick="<?php echo !$isEdit ? 'return false;' : ''; ?>">
            <i class="fas fa-id-card me-1"></i> Dados Gerais
        </a>
    </li>
    <?php if ($isEdit): ?>
    <li class="nav-item">
        <a class="nav-link <?php echo $activeTab === 'anexos' ? 'active' : ''; ?>"
           href="/colaboradores/edit/<?php echo (int)$colaborador->id; ?>?tab=anexos">
            <i class="fas fa-paperclip me-1"></i> Anexos
            <?php if (count($anexos) > 0): ?>
                <span class="badge bg-secondary ms-1"><?php echo count($anexos); ?></span>
            <?php endif; ?>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo $activeTab === 'comissoes' ? 'active' : ''; ?>"
           href="/colaboradores/edit/<?php echo (int)$colaborador->id; ?>?tab=comissoes">
            <i class="fas fa-percent me-1"></i> Comissões
            <?php if (count($comissoes) > 0): ?>
                <span class="badge bg-secondary ms-1"><?php echo count($comissoes); ?></span>
            <?php endif; ?>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo $activeTab === 'faturamento' ? 'active' : ''; ?>"
           href="/colaboradores/edit/<?php echo (int)$colaborador->id; ?>?tab=faturamento">
            <i class="fas fa-file-invoice-dollar me-1"></i> Faturamento
            <?php if (count($faturamentos) > 0): ?>
                <span class="badge bg-secondary ms-1"><?php echo count($faturamentos); ?></span>
            <?php endif; ?>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo $activeTab === 'usuario' ? 'active' : ''; ?>"
           href="/colaboradores/edit/<?php echo (int)$colaborador->id; ?>?tab=usuario">
            <i class="fas fa-user-lock me-1"></i> Usuário
        </a>
    </li>
    <?php endif; ?>
</ul>

<!-- Conteúdo das abas -->
<?php if ($activeTab === 'geral'): ?>
    <?php include __DIR__ . '/tabs/geral.php'; ?>
<?php elseif ($activeTab === 'anexos' && $isEdit): ?>
    <?php include __DIR__ . '/tabs/anexos.php'; ?>
<?php elseif ($activeTab === 'comissoes' && $isEdit): ?>
    <?php include __DIR__ . '/tabs/comissoes.php'; ?>
<?php elseif ($activeTab === 'faturamento' && $isEdit): ?>
    <?php include __DIR__ . '/tabs/faturamento.php'; ?>
<?php elseif ($activeTab === 'usuario' && $isEdit): ?>
    <?php include __DIR__ . '/tabs/usuario.php'; ?>
<?php endif; ?>

</div>
