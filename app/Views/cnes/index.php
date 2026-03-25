<?php
use App\Core\UI;
use App\Core\Auth;

$actions = [];
UI::sectionHeader(
    'CNES Global',
    'Base Nacional de Estabelecimentos de Saúde — DATASUS/CNES',
    $actions
);

$ufsDisponiveis = ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'];
?>

<?php if (!empty($erro)): ?>
<div class="alert alert-danger d-flex align-items-center mb-4">
    <i class="fas fa-triangle-exclamation me-2"></i>
    <?php echo htmlspecialchars($erro); ?>
</div>
<?php endif; ?>

<?php if (!$baseImportada): ?>
<!-- Estado: base não importada -->
<div class="card border-0 shadow-sm">
    <div class="card-body text-center py-5">
        <div class="mb-4">
            <i class="fas fa-hospital fa-4x text-muted opacity-50"></i>
        </div>
        <h4 class="fw-bold text-dark mb-2">Base CNES não importada</h4>
        <p class="text-muted mb-4 mx-auto" style="max-width:520px">
            A base de dados CNES (Cadastro Nacional de Estabelecimentos de Saúde) ainda não foi
            importada para este sistema. Siga as instruções abaixo para realizar a importação.
        </p>
        <div class="card border-0 bg-light text-start mx-auto" style="max-width:600px">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3"><i class="fas fa-terminal me-2 text-primary"></i>Como importar</h6>
                <ol class="mb-0 small text-muted">
                    <li class="mb-2">Acesse o servidor via SSH ou Terminal do hPanel</li>
                    <li class="mb-2">Execute a migration SQL:<br>
                        <code class="d-block bg-white border rounded p-2 mt-1">mysql -u usuario -p banco &lt; database/migrations/2026-03-25_cnes_global.sql</code>
                    </li>
                    <li class="mb-2">Extraia o ZIP da base CNES para um diretório (ex: <code>/tmp/cnes_base/</code>)</li>
                    <li class="mb-2">Execute o script de importação:<br>
                        <code class="d-block bg-white border rounded p-2 mt-1">php database/importar_cnes.php --dir=/tmp/cnes_base --uf=MG</code>
                    </li>
                    <li>Recarregue esta página após a importação.</li>
                </ol>
            </div>
        </div>
    </div>
</div>
<?php else: ?>

<!-- Filtros -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4">
        <form method="GET" action="/cnes" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label small fw-bold text-muted">Pesquisar</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" name="q" class="form-control border-start-0"
                        placeholder="Nome, CNES, CNPJ..."
                        value="<?php echo htmlspecialchars($filtros['q'] ?? ''); ?>">
                </div>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted">Estado (UF)</label>
                <select name="uf" class="form-select">
                    <option value="">Todos</option>
                    <?php foreach ($ufsDisponiveis as $uf): ?>
                        <option value="<?php echo $uf; ?>" <?php echo ($filtros['uf'] ?? '') === $uf ? 'selected' : ''; ?>>
                            <?php echo $uf; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted">Importado</label>
                <select name="importado" class="form-select">
                    <option value="">Todos</option>
                    <option value="1" <?php echo ($filtros['importado'] ?? '') === '1' ? 'selected' : ''; ?>>Sim (cliente)</option>
                    <option value="0" <?php echo ($filtros['importado'] ?? '') === '0' ? 'selected' : ''; ?>>Não importado</option>
                </select>
            </div>
            <div class="col-md-2 d-grid">
                <button type="submit" class="btn btn-primary fw-bold">
                    <i class="fas fa-filter me-1"></i> Filtrar
                </button>
            </div>
            <div class="col-md-2 d-grid">
                <a href="/cnes" class="btn btn-outline-secondary">
                    <i class="fas fa-times me-1"></i> Limpar
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Contador -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="text-muted small">
        Exibindo <strong><?php echo count($resultado['registros']); ?></strong>
        de <strong><?php echo number_format($resultado['total'], 0, ',', '.'); ?></strong> estabelecimentos
    </div>
    <!-- Paginação superior -->
    <?php if ($resultado['total_paginas'] > 1): ?>
    <nav>
        <ul class="pagination pagination-sm mb-0">
            <?php if ($resultado['pagina'] > 1): ?>
            <li class="page-item">
                <a class="page-link" href="?<?php echo http_build_query(array_merge($filtros, ['pagina' => $resultado['pagina'] - 1])); ?>">
                    <i class="fas fa-chevron-left"></i>
                </a>
            </li>
            <?php endif; ?>
            <?php
            $inicio = max(1, $resultado['pagina'] - 2);
            $fim    = min($resultado['total_paginas'], $resultado['pagina'] + 2);
            for ($p = $inicio; $p <= $fim; $p++): ?>
            <li class="page-item <?php echo $p === $resultado['pagina'] ? 'active' : ''; ?>">
                <a class="page-link" href="?<?php echo http_build_query(array_merge($filtros, ['pagina' => $p])); ?>"><?php echo $p; ?></a>
            </li>
            <?php endfor; ?>
            <?php if ($resultado['pagina'] < $resultado['total_paginas']): ?>
            <li class="page-item">
                <a class="page-link" href="?<?php echo http_build_query(array_merge($filtros, ['pagina' => $resultado['pagina'] + 1])); ?>">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<!-- Tabela de estabelecimentos -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <?php if (empty($resultado['registros'])): ?>
        <div class="text-center py-5">
            <i class="fas fa-hospital-slash fa-3x text-muted opacity-50 mb-3 d-block"></i>
            <p class="text-muted">Nenhum estabelecimento encontrado com os filtros aplicados.</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Estabelecimento</th>
                        <th>CNES</th>
                        <th>CNPJ</th>
                        <th>UF</th>
                        <th>Telefone</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($resultado['registros'] as $estab): ?>
                    <tr>
                        <td class="ps-4">
                            <div class="fw-semibold text-dark">
                                <?php echo htmlspecialchars($estab->no_razao_social); ?>
                            </div>
                            <?php if (!empty($estab->no_fantasia)): ?>
                            <small class="text-muted">
                                <?php echo htmlspecialchars($estab->no_fantasia); ?>
                            </small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-primary-subtle text-primary fw-semibold">
                                <?php echo htmlspecialchars($estab->co_cnes); ?>
                            </span>
                        </td>
                        <td class="text-muted small">
                            <?php echo htmlspecialchars($estab->nu_cnpj ?? '—'); ?>
                        </td>
                        <td>
                            <?php if ($estab->co_estado_gestor): ?>
                            <span class="badge bg-secondary-subtle text-secondary">
                                <?php echo htmlspecialchars($estab->co_estado_gestor); ?>
                            </span>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted small">
                            <?php echo htmlspecialchars($estab->nu_telefone ?? '—'); ?>
                        </td>
                        <td class="text-center">
                            <?php if ($estab->cliente_id): ?>
                            <span class="badge bg-success-subtle text-success">
                                <i class="fas fa-check me-1"></i>Cliente
                            </span>
                            <?php else: ?>
                            <span class="badge bg-light text-muted border">CNES</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <a href="/cnes/<?php echo urlencode($estab->co_cnes); ?>"
                               class="btn btn-sm btn-outline-primary me-1"
                               title="Ver detalhes">
                                <i class="fas fa-eye"></i>
                            </a>
                            <?php if ($estab->cliente_id): ?>
                            <a href="/clientes/<?php echo (int)$estab->cliente_id; ?>"
                               class="btn btn-sm btn-outline-success"
                               title="Ver cliente">
                                <i class="fas fa-user"></i>
                            </a>
                            <?php else: ?>
                            <button class="btn btn-sm btn-outline-secondary btn-importar"
                                    data-cnes="<?php echo htmlspecialchars($estab->co_cnes); ?>"
                                    data-nome="<?php echo htmlspecialchars($estab->no_razao_social); ?>"
                                    title="Importar como cliente">
                                <i class="fas fa-file-import"></i>
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php endif; // fim $baseImportada ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Importar como cliente
    document.querySelectorAll('.btn-importar').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const cnes = this.dataset.cnes;
            const nome = this.dataset.nome;
            Swal.fire({
                title: 'Importar como Cliente?',
                html: `<p>Deseja importar o estabelecimento <strong>${nome}</strong> (CNES: ${cnes}) como cliente no ERP?</p>`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Importar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#0d6efd',
            }).then(function (result) {
                if (!result.isConfirmed) return;
                Swal.fire({ title: 'Importando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
                fetch('/cnes/' + encodeURIComponent(cnes) + '/importar-cliente', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'cnes=' + encodeURIComponent(cnes)
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Importado!',
                            text: data.message,
                            showCancelButton: true,
                            confirmButtonText: 'Ver Cliente',
                            cancelButtonText: 'Fechar',
                        }).then(res => {
                            if (res.isConfirmed && data.redirect) {
                                window.location.href = data.redirect;
                            } else {
                                window.location.reload();
                            }
                        });
                    } else {
                        Swal.fire('Erro', data.error || 'Não foi possível importar.', 'error');
                    }
                })
                .catch(() => Swal.fire('Erro', 'Falha na comunicação com o servidor.', 'error'));
            });
        });
    });
});
</script>
