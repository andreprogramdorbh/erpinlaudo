<?php
use App\Core\UI;

$actions = [
    ['text' => 'Apuração Prestador', 'link' => '/faturamento/apuracao-prestador', 'icon' => 'fas fa-user-md', 'class' => 'btn-outline-secondary'],
];
UI::sectionHeader('Apuração Cliente', 'Controle de exames realizados e recebimentos de clientes', $actions);
?>

<?php
$success = $_GET['success'] ?? '';
$error   = $_GET['error'] ?? '';
if ($success === 'faturado')  echo '<div class="alert alert-success border-0 shadow-sm"><i class="fas fa-check-circle me-2"></i>Apuração faturada! Conta a receber gerada em Financeiro.</div>';
if ($success === 'deleted')          echo '<div class="alert alert-success border-0 shadow-sm"><i class="fas fa-check-circle me-2"></i>Apuração excluída com sucesso.</div>';
if ($error === 'status_invalido')     echo '<div class="alert alert-warning border-0 shadow-sm"><i class="fas fa-exclamation-triangle me-2"></i>Apenas apurações com status "Concluído" podem ser faturadas.</div>';
if ($error === 'faturamento_falhou')  echo '<div class="alert alert-danger border-0 shadow-sm"><i class="fas fa-times-circle me-2"></i>Erro ao gerar conta a receber. Verifique os logs.</div>';
if ($error === 'exclusao_bloqueada') echo '<div class="alert alert-danger border-0 shadow-sm"><i class="fas fa-lock me-2"></i><strong>Exclusão bloqueada:</strong> Não é possível excluir apurações com status <strong>Concluído</strong> ou <strong>Faturado</strong>. Apenas apurações em rascunho ou com erro podem ser excluídas.</div>';
if ($error === 'not_found')           echo '<div class="alert alert-warning border-0 shadow-sm"><i class="fas fa-search me-2"></i>Apuração não encontrada.</div>';
if ($error === 'db_error')            echo '<div class="alert alert-danger border-0 shadow-sm"><i class="fas fa-times-circle me-2"></i>Erro ao excluir apuração. Tente novamente.</div>';
if ($error === 'sem_permissao')       echo '<div class="alert alert-danger border-0 shadow-sm"><i class="fas fa-ban me-2"></i><strong>Acesso negado:</strong> Esta operação é exclusiva para superadmin.</div>';
?>

<!-- FILTROS -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4">
        <form method="GET" action="/faturamento/apuracao-cliente" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted">Cliente</label>
                <select name="cliente_id" class="form-select">
                    <option value="">Todos os clientes</option>
                    <?php foreach ($clientes ?? [] as $cl): ?>
                        <option value="<?php echo $cl->id; ?>" <?php echo ($filtros['cliente_id'] ?? 0) == $cl->id ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cl->razao_social ?? $cl->nome ?? ''); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted">Status</label>
                <select name="status" class="form-select">
                    <option value="">Todos</option>
                    <option value="rascunho"  <?php echo ($filtros['status'] ?? '') === 'rascunho'  ? 'selected' : ''; ?>>Rascunho</option>
                    <option value="concluido" <?php echo ($filtros['status'] ?? '') === 'concluido' ? 'selected' : ''; ?>>Concluído</option>
                    <option value="faturado"  <?php echo ($filtros['status'] ?? '') === 'faturado'  ? 'selected' : ''; ?>>Faturado</option>
                    <option value="erro"      <?php echo ($filtros['status'] ?? '') === 'erro'      ? 'selected' : ''; ?>>Erro</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted">Período Início</label>
                <input type="date" name="periodo_inicio" class="form-control" value="<?php echo $filtros['periodo_inicio'] ?? ''; ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted">Período Fim</label>
                <input type="date" name="periodo_fim" class="form-control" value="<?php echo $filtros['periodo_fim'] ?? ''; ?>">
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-filter me-1"></i> Filtrar</button>
                <a href="/faturamento/apuracao-cliente" class="btn btn-outline-secondary"><i class="fas fa-times"></i></a>
            </div>
        </form>
    </div>
</div>

<!-- RESUMO -->
<?php if (!empty($apuracoes)): ?>
<?php
$totExames = array_sum(array_column($apuracoes, 'total_exames'));
$totValor  = array_sum(array_column($apuracoes, 'valor_venda_total')) ?: array_sum(array_column($apuracoes, 'valor_total'));
?>
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="fs-3 fw-bold text-primary"><?php echo count($apuracoes); ?></div>
            <small class="text-muted">Total de Apurações</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="fs-3 fw-bold text-info"><?php echo number_format($totExames); ?></div>
            <small class="text-muted">Total de Exames</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="fs-3 fw-bold text-success">R$ <?php echo number_format($totValor, 2, ',', '.'); ?></div>
            <small class="text-muted">Valor a Receber</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="fs-3 fw-bold text-warning"><?php echo count(array_filter($apuracoes, fn($a) => $a->status === 'concluido')); ?></div>
            <small class="text-muted">Aguardando Faturamento</small>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- TABELA -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <?php if (empty($apuracoes)): ?>
            <div class="text-center py-5 text-muted">
                <i class="fas fa-calculator fa-3x mb-3"></i>
                <p>Nenhuma apuração de cliente encontrada.</p>
                <a href="/contratos" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-file-contract me-1"></i> Ir para Contratos
                </a>
            </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Número</th>
                        <th>Cliente</th>
                        <th>Contrato</th>
                        <th>Período</th>
                        <th class="text-center">Exames</th>
                        <th class="text-center">Normal</th>
                        <th class="text-center">Urgência</th>
                        <th class="text-end">Valor (Venda)</th>
                        <th>Status</th>
                        <th class="text-center pe-4">Ações</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($apuracoes as $ap): ?>
                    <tr>
                        <td class="ps-4">
                            <span class="badge bg-secondary font-monospace"><?php echo htmlspecialchars($ap->numero); ?></span>
                        </td>
                        <td>
                            <div class="fw-semibold small"><?php echo htmlspecialchars($ap->cliente_nome ?? '—'); ?></div>
                        </td>
                        <td>
                            <small class="text-muted"><?php echo htmlspecialchars($ap->contrato_nome ?? '—'); ?></small>
                        </td>
                        <td>
                            <small>
                                <?php
                                if ($ap->periodo_inicio && $ap->periodo_fim) {
                                    echo date('d/m/Y', strtotime($ap->periodo_inicio)) . ' → ' . date('d/m/Y', strtotime($ap->periodo_fim));
                                } else {
                                    echo '<span class="text-muted">—</span>';
                                }
                                ?>
                            </small>
                        </td>
                        <td class="text-center fw-bold"><?php echo $ap->total_exames; ?></td>
                        <td class="text-center"><span class="badge bg-success"><?php echo $ap->total_normal; ?></span></td>
                        <td class="text-center"><span class="badge bg-danger"><?php echo $ap->total_urgencia; ?></span></td>
                        <td class="text-end fw-semibold text-success">
                            R$ <?php echo number_format((float)(($ap->valor_venda_total ?? 0) > 0 ? $ap->valor_venda_total : $ap->valor_total), 2, ',', '.'); ?>
                        </td>
                        <td>
                            <?php
                            $stClass = ['rascunho' => 'secondary', 'processando' => 'warning', 'concluido' => 'success', 'faturado' => 'primary', 'erro' => 'danger'];
                            $stLabel = ['rascunho' => 'Rascunho', 'processando' => 'Processando', 'concluido' => 'Concluído', 'faturado' => 'Faturado', 'erro' => 'Erro'];
                            ?>
                            <span class="badge bg-<?php echo $stClass[$ap->status] ?? 'secondary'; ?>">
                                <?php echo $stLabel[$ap->status] ?? ucfirst($ap->status); ?>
                            </span>
                        </td>
                        <td class="text-center pe-4">
                            <div class="btn-group btn-group-sm">
                                <?php if ($ap->status === 'concluido'): ?>
                                <a href="/faturamento/apuracao-cliente/visualizar/<?php echo $ap->id; ?>"
                                   class="btn btn-outline-info" title="Visualizar">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <button type="button" class="btn btn-outline-success"
                                        onclick="confirmarFaturamento('/faturamento/apuracao/faturar/<?php echo $ap->id; ?>', 'Cliente', '<?php echo htmlspecialchars(addslashes($ap->cliente_nome ?? $ap->numero)); ?>', '<?php echo number_format((float)(($ap->valor_venda_total ?? 0) > 0 ? $ap->valor_venda_total : $ap->valor_total), 2, ',', '.'); ?>')"
                                        title="Faturar — gerar conta a receber">
                                    <i class="fas fa-file-invoice-dollar"></i>
                                </button>
                                <?php elseif ($ap->status === 'faturado'): ?>
                                <a href="/faturamento/apuracao-cliente/visualizar/<?php echo $ap->id; ?>"
                                   class="btn btn-outline-primary" title="Visualizar">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php endif; ?>
                                <?php if (in_array($ap->status, ['rascunho', 'erro'])): ?>
                                <a href="/contratos/edit/<?php echo $ap->contrato_id; ?>?tab=apuracao"
                                   class="btn btn-outline-secondary" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button type="button" class="btn btn-outline-danger"
                                        onclick="confirmarExclusao('/faturamento/apuracao/delete/<?php echo $ap->id; ?>', 'Excluir apuração <?php echo htmlspecialchars($ap->numero); ?>?')"
                                        title="Excluir">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <?php endif; ?>
                                <?php if (!empty($isSuperAdmin)): ?>
                                <button type="button" class="btn btn-danger"
                                        onclick="confirmarExclusaoSuperAdmin('/faturamento/apuracao/superadmin-delete/<?php echo $ap->id; ?>', '<?php echo htmlspecialchars($ap->numero, ENT_QUOTES); ?>')"
                                        title="[SUPERADMIN] Excluir em cascata">
                                    <i class="fas fa-skull-crossbones"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 text-muted small border-top">
            <?php echo count($apuracoes); ?> apuração(ões) encontrada(s)
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
// confirmarFaturamento e confirmarExclusaoApuracao são definidos em /assets/js/apuracao.js
// Alias para compatibilidade com botões de exclusão simples
function confirmarExclusao(url, msg) {
    if (window.confirmarExclusaoApuracao) {
        confirmarExclusaoApuracao(url, msg);
    } else if (confirm(msg + '\n\nEsta ação não pode ser desfeita.')) {
        window.location.href = url;
    }
}

// Exclusão em cascata exclusiva do SUPERADMIN
function confirmarExclusaoSuperAdmin(url, numero) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: '⚠️ Exclusão Permanente (SUPERADMIN)',
            html: '<p>Você está prestes a excluir <strong>' + numero + '</strong> e <strong>todas as suas ramificações</strong>:</p>'
                + '<ul class="text-start small mt-2">'
                + '<li>Apuração principal e todos os itens</li>'
                + '<li>Sub-apurações de prestador vinculadas</li>'
                + '<li>Contas a Pagar geradas</li>'
                + '<li>Contas a Receber geradas</li>'
                + '</ul>'
                + '<p class="text-danger fw-bold mt-2">Esta ação é irreversível e não pode ser desfeita!</p>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-skull-crossbones me-1"></i> Excluir Tudo',
            cancelButtonText: 'Cancelar',
            focusCancel: true,
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = url;
            }
        });
    } else {
        if (confirm('[SUPERADMIN] Excluir ' + numero + ' e todas as ramificações (contas a pagar/receber, sub-apurações)?\n\nESTA AÇÃO É IRREVERSÍVEL!')) {
            window.location.href = url;
        }
    }
}
</script>
