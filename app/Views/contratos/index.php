<?php
use App\Core\Auth;
use App\Core\UI;

$actions = [];
if (Auth::check()) {
    $actions[] = [
        'text'  => 'Novo Contrato',
        'link'  => '/contratos/create',
        'icon'  => 'fas fa-file-contract',
        'class' => 'btn-primary',
    ];
}
UI::sectionHeader('Contratos', 'Gerencie contratos com médicos e clientes', $actions);
?>

<?php
$success = $_GET['success'] ?? '';
$error   = $_GET['error'] ?? '';
if ($success === 'deleted') echo '<div class="alert alert-success border-0 shadow-sm">Contrato excluído com sucesso.</div>';
if ($success === 'faturado') echo '<div class="alert alert-success border-0 shadow-sm">Apuração faturada com sucesso.</div>';
if ($error === 'not_found')  echo '<div class="alert alert-warning border-0 shadow-sm">Contrato não encontrado.</div>';
?>

<!-- FILTROS -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4">
        <form method="GET" action="/contratos" class="row g-3 align-items-end">
            <div class="col-md-5">
                <label class="form-label small fw-bold text-muted">Pesquisar</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" name="q" class="form-control border-start-0"
                           placeholder="Nome do contrato, número, médico ou cliente"
                           value="<?php echo htmlspecialchars($filtros['q'] ?? ''); ?>">
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted">Tipo</label>
                <select name="tipo_parte" class="form-select">
                    <option value="">Todos</option>
                    <option value="medico"  <?php echo ($filtros['tipo_parte'] ?? '') === 'medico'  ? 'selected' : ''; ?>>Médico (Pagamento)</option>
                    <option value="cliente" <?php echo ($filtros['tipo_parte'] ?? '') === 'cliente' ? 'selected' : ''; ?>>Cliente (Recebimento)</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted">Status</label>
                <select name="status" class="form-select">
                    <option value="">Todos</option>
                    <option value="ativo"      <?php echo ($filtros['status'] ?? '') === 'ativo'      ? 'selected' : ''; ?>>Ativo</option>
                    <option value="encerrado"  <?php echo ($filtros['status'] ?? '') === 'encerrado'  ? 'selected' : ''; ?>>Encerrado</option>
                    <option value="suspenso"   <?php echo ($filtros['status'] ?? '') === 'suspenso'   ? 'selected' : ''; ?>>Suspenso</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-filter me-1"></i> Filtrar</button>
                <a href="/contratos" class="btn btn-outline-secondary"><i class="fas fa-times"></i></a>
            </div>
        </form>
    </div>
</div>

<!-- TABELA -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <?php if (empty($contratos)): ?>
            <div class="text-center py-5">
                <i class="fas fa-file-contract fa-3x text-muted mb-3"></i>
                <p class="text-muted">Nenhum contrato encontrado.</p>
                <a href="/contratos/create" class="btn btn-primary"><i class="fas fa-plus me-1"></i> Novo Contrato</a>
            </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Número</th>
                        <th>Nome do Contrato</th>
                        <th>Tipo</th>
                        <th>Parte</th>
                        <th>Vigência</th>
                        <th>Recorrência</th>
                        <th class="text-end">Valor</th>
                        <th>Status</th>
                        <th class="text-center pe-4">Ações</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($contratos as $c): ?>
                    <tr>
                        <td class="ps-4">
                            <span class="badge bg-secondary font-monospace"><?php echo htmlspecialchars($c->numero); ?></span>
                        </td>
                        <td>
                            <div class="fw-semibold"><?php echo htmlspecialchars($c->nome); ?></div>
                            <small class="text-muted">
                                <?php if ($c->tipo_parte === 'medico' && $c->medico_nome): ?>
                                    <i class="fas fa-user-md me-1"></i><?php echo htmlspecialchars($c->medico_nome); ?>
                                <?php elseif ($c->tipo_parte === 'cliente' && $c->cliente_nome): ?>
                                    <i class="fas fa-building me-1"></i><?php echo htmlspecialchars($c->cliente_nome); ?>
                                <?php endif; ?>
                            </small>
                        </td>
                        <td>
                            <?php if ($c->tipo_parte === 'medico'): ?>
                                <span class="badge bg-info text-dark"><i class="fas fa-user-md me-1"></i>Médico</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark"><i class="fas fa-building me-1"></i>Cliente</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <small class="text-muted">
                                <?php echo $c->tipo_parte === 'medico' ? 'Pagamento' : 'Recebimento'; ?>
                            </small>
                        </td>
                        <td>
                            <small>
                                <?php echo date('d/m/Y', strtotime($c->data_inicio)); ?>
                                <?php if ($c->data_fim): ?>
                                    → <?php echo date('d/m/Y', strtotime($c->data_fim)); ?>
                                <?php else: ?>
                                    <span class="text-muted">→ Indeterminado</span>
                                <?php endif; ?>
                            </small>
                        </td>
                        <td>
                            <?php
                            $recLabel = ['diario' => 'Diário', 'semanal' => 'Semanal', 'mensal' => 'Mensal', 'anual' => 'Anual'];
                            echo $recLabel[$c->recorrencia] ?? ucfirst($c->recorrencia);
                            ?>
                        </td>
                        <td class="text-end fw-semibold">
                            R$ <?php echo number_format((float)$c->valor, 2, ',', '.'); ?>
                        </td>
                        <td>
                            <?php
                            $statusClass = ['ativo' => 'success', 'encerrado' => 'secondary', 'suspenso' => 'warning'];
                            $statusLabel = ['ativo' => 'Ativo', 'encerrado' => 'Encerrado', 'suspenso' => 'Suspenso'];
                            $sc = $statusClass[$c->status] ?? 'secondary';
                            $sl = $statusLabel[$c->status] ?? ucfirst($c->status);
                            ?>
                            <span class="badge bg-<?php echo $sc; ?>"><?php echo $sl; ?></span>
                        </td>
                        <td class="text-center pe-4">
                            <div class="btn-group btn-group-sm">
                                <a href="/contratos/edit/<?php echo $c->id; ?>" class="btn btn-outline-primary" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="/contratos/edit/<?php echo $c->id; ?>?tab=apuracao" class="btn btn-outline-info" title="Apurações">
                                    <i class="fas fa-calculator"></i>
                                </a>
                                <button type="button" class="btn btn-outline-danger"
                                        onclick="confirmarExclusao('/contratos/delete/<?php echo $c->id; ?>', 'Excluir o contrato \'<?php echo addslashes($c->nome); ?>\'?')"
                                        title="Excluir">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 text-muted small border-top">
            <?php echo count($contratos); ?> contrato(s) encontrado(s)
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function confirmarExclusao(url, msg) {
    if (confirm(msg + '\n\nEsta ação não pode ser desfeita.')) {
        window.location.href = url;
    }
}
</script>
