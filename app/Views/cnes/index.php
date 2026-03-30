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

// Mapeamento código IBGE → sigla UF (campo co_estado_gestor do CSV CNES)
$ibgeParaUf = [
    '11'=>'RO','12'=>'AC','13'=>'AM','14'=>'RR','15'=>'PA','16'=>'AP','17'=>'TO',
    '21'=>'MA','22'=>'PI','23'=>'CE','24'=>'RN','25'=>'PB','26'=>'PE','27'=>'AL','28'=>'SE','29'=>'BA',
    '31'=>'MG','32'=>'ES','33'=>'RJ','35'=>'SP',
    '41'=>'PR','42'=>'SC','43'=>'RS',
    '50'=>'MS','51'=>'MT','52'=>'GO','53'=>'DF',
];

// Natureza jurídica → Público ou Privado
// Códigos IBGE de natureza jurídica: 1xxx = Administração Pública, 2xxx+ = Privado
function cnesNaturezaTipo(?string $co): string {
    if (!$co) return '';
    $n = (int)$co;
    // Faixa 1000-1999 = Administração Pública (Federal, Estadual, Municipal)
    if ($n >= 1000 && $n <= 1999) return 'publico';
    // Faixa 1100-1244 = Federal; 1203-1244 = Estadual; 1244-1260 = Municipal
    // Também: 1023, 1031, 1040, 1058, 1066, 1074, 1082, 1104, 1112, 1120, 1139, 1147, 1155, 1163, 1171, 1180, 1198, 1201, 1210, 1228, 1236, 1244, 1252, 1260, 1279, 1287, 1295
    return 'privado';
}
?>

<?php if (!empty($erro)): ?>
<div class="alert alert-danger d-flex align-items-center mb-4">
    <i class="fas fa-triangle-exclamation me-2"></i>
    <?php echo htmlspecialchars($erro); ?>
</div>
<?php endif; ?>

<?php if (!$baseImportada): ?>
<!-- Estado: base não importada -->
<div class="card border-0 shadow-sm mb-4" id="card-importacao">
    <div class="card-body p-4">

        <!-- Cabeçalho -->
        <div class="d-flex align-items-center mb-4">
            <div class="rounded-3 bg-primary bg-opacity-10 p-3 me-3">
                <i class="fas fa-database text-primary fa-2x"></i>
            </div>
            <div>
                <h5 class="fw-bold mb-1">Importar Base CNES</h5>
                <p class="text-muted small mb-0">Cadastro Nacional de Estabelecimentos de Saúde — DATASUS/CNES</p>
            </div>
        </div>

        <!-- Detecção automática -->
        <div id="bloco-deteccao" class="mb-4">
            <div class="alert alert-info d-flex align-items-center" id="msg-detectando">
                <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                Verificando arquivos CNES no servidor...
            </div>
            <div id="msg-detectado" class="d-none"></div>
        </div>

        <!-- Opções de importação -->
        <div id="bloco-opcoes" class="d-none">
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Filtrar por UF <span class="text-muted fw-normal">(opcional)</span></label>
                    <select id="import-uf" class="form-select">
                        <option value="">Todos os estados</option>
                        <?php foreach ($ufsDisponiveis as $uf): ?>
                            <option value="<?php echo $uf; ?>"><?php echo $uf; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Competência</label>
                    <input type="text" id="import-competencia" class="form-control" value="<?php echo date('Ym'); ?>" placeholder="202602">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="import-apenas-imagem">
                        <label class="form-check-label small" for="import-apenas-imagem">
                            Apenas Diagnóstico por Imagem
                        </label>
                    </div>
                </div>
            </div>

            <!-- Botões de ação -->
            <div class="d-flex gap-2 flex-wrap">
                <button id="btn-importar-servidor" class="btn btn-primary btn-lg">
                    <i class="fas fa-play me-2"></i>Iniciar Importação do Servidor
                </button>
                <a href="/configuracoes?tab=cnes" class="btn btn-outline-secondary btn-lg">
                    <i class="fas fa-upload me-2"></i>Upload de ZIP
                </a>
            </div>
        </div>

        <!-- Sem CSVs encontrados -->
        <div id="bloco-sem-csv" class="d-none">
            <div class="alert alert-warning">
                <i class="fas fa-triangle-exclamation me-2"></i>
                <strong>Nenhum arquivo CSV encontrado no servidor.</strong>
                Os arquivos CNES precisam estar extraídos em <code>/tmp/cnes_base/</code>.
            </div>
            <div class="d-flex gap-2">
                <a href="/configuracoes?tab=cnes" class="btn btn-primary">
                    <i class="fas fa-upload me-2"></i>Fazer Upload do ZIP
                </a>
            </div>
        </div>

        <!-- Progresso da importação -->
        <div id="bloco-progresso" class="d-none mt-4">
            <hr>
            <h6 class="fw-bold mb-3"><i class="fas fa-spinner fa-spin me-2 text-primary"></i>Importação em andamento</h6>
            <div class="mb-2 d-flex justify-content-between small text-muted">
                <span id="prog-etapa">Iniciando...</span>
                <span id="prog-pct">0%</span>
            </div>
            <div class="progress mb-3" style="height:12px">
                <div id="prog-bar" class="progress-bar progress-bar-striped progress-bar-animated bg-primary"
                     role="progressbar" style="width:0%"></div>
            </div>
            <div class="row g-3 text-center">
                <div class="col-4">
                    <div class="card border-0 bg-light p-3">
                        <div class="fw-bold fs-5 text-primary" id="prog-estab">0</div>
                        <div class="small text-muted">Estabelecimentos</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="card border-0 bg-light p-3">
                        <div class="fw-bold fs-5 text-success" id="prog-equip">0</div>
                        <div class="small text-muted">Equipamentos</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="card border-0 bg-light p-3">
                        <div class="fw-bold fs-5 text-info" id="prog-prof">0</div>
                        <div class="small text-muted">Profissionais</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Concluído -->
        <div id="bloco-concluido" class="d-none mt-4">
            <div class="alert alert-success d-flex align-items-center">
                <i class="fas fa-check-circle fa-2x me-3"></i>
                <div>
                    <strong>Importação concluída com sucesso!</strong><br>
                    <span id="msg-concluido"></span>
                </div>
            </div>
            <a href="/cnes" class="btn btn-primary">
                <i class="fas fa-list me-2"></i>Ver Base CNES
            </a>
        </div>

        <!-- Erro -->
        <div id="bloco-erro" class="d-none mt-4">
            <div class="alert alert-danger">
                <i class="fas fa-times-circle me-2"></i>
                <strong>Erro na importação:</strong> <span id="msg-erro"></span>
            </div>
            <button class="btn btn-outline-danger" onclick="location.reload()">
                <i class="fas fa-redo me-2"></i>Tentar novamente
            </button>
        </div>

    </div>
</div>

<script>
(function() {
    var pollingInterval = null;

    // Detectar CSVs no servidor ao carregar a página
    fetch('/cnes/importar/detectar')
        .then(function(r) { return r.json(); })
        .then(function(data) {
            document.getElementById('msg-detectando').classList.add('d-none');
            var encontrado = null;
            if (data.candidatos) {
                for (var i = 0; i < data.candidatos.length; i++) {
                    if (data.candidatos[i].existe && data.candidatos[i].total_csv > 0) {
                        encontrado = data.candidatos[i];
                        break;
                    }
                }
            }
            if (encontrado) {
                var html = '<div class="alert alert-success d-flex align-items-center">' +
                    '<i class="fas fa-check-circle fa-lg me-3 text-success"></i>' +
                    '<div><strong>' + encontrado.total_csv + ' arquivos CSV encontrados</strong><br>' +
                    '<code class="small">' + encontrado.dir + '</code>' +
                    (encontrado.tem_estab ? ' &nbsp;<span class="badge bg-success">tbEstabelecimento ✓</span>' : '') +
                    '</div></div>';
                document.getElementById('msg-detectado').innerHTML = html;
                document.getElementById('msg-detectado').classList.remove('d-none');
                document.getElementById('bloco-opcoes').classList.remove('d-none');
            } else {
                document.getElementById('bloco-sem-csv').classList.remove('d-none');
            }
        })
        .catch(function() {
            document.getElementById('msg-detectando').classList.add('d-none');
            document.getElementById('bloco-sem-csv').classList.remove('d-none');
        });

    // Botão de importação do servidor
    document.getElementById('btn-importar-servidor').addEventListener('click', function() {
        var btn = this;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Iniciando...';

        var uf           = document.getElementById('import-uf').value;
        var competencia  = document.getElementById('import-competencia').value;
        var apenasImagem = document.getElementById('import-apenas-imagem').checked;

        fetch('/cnes/importar/servidor', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ uf: uf, competencia: competencia, apenas_imagem: apenasImagem })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                document.getElementById('bloco-opcoes').classList.add('d-none');
                document.getElementById('bloco-deteccao').classList.add('d-none');
                document.getElementById('bloco-progresso').classList.remove('d-none');
                iniciarPolling();
            } else {
                document.getElementById('msg-erro').textContent = data.error || 'Erro desconhecido';
                document.getElementById('bloco-erro').classList.remove('d-none');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-play me-2"></i>Iniciar Importação do Servidor';
            }
        })
        .catch(function(err) {
            document.getElementById('msg-erro').textContent = err.message;
            document.getElementById('bloco-erro').classList.remove('d-none');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-play me-2"></i>Iniciar Importação do Servidor';
        });
    });

    function iniciarPolling() {
        pollingInterval = setInterval(function() {
            fetch('/cnes/importar/status')
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    var pct = data.pct || 0;
                    document.getElementById('prog-bar').style.width = pct + '%';
                    document.getElementById('prog-pct').textContent = pct + '%';
                    document.getElementById('prog-etapa').textContent = data.etapa || '';
                    document.getElementById('prog-estab').textContent = (data.estab || data.db_estab || 0).toLocaleString('pt-BR');
                    document.getElementById('prog-equip').textContent = (data.equip || data.db_equip || 0).toLocaleString('pt-BR');
                    document.getElementById('prog-prof').textContent  = (data.prof  || data.db_prof  || 0).toLocaleString('pt-BR');

                    if (data.status === 'concluido') {
                        clearInterval(pollingInterval);
                        document.getElementById('bloco-progresso').classList.add('d-none');
                        document.getElementById('bloco-concluido').classList.remove('d-none');
                        document.getElementById('msg-concluido').textContent =
                            (data.estab || 0).toLocaleString('pt-BR') + ' estabelecimentos, ' +
                            (data.equip || 0).toLocaleString('pt-BR') + ' equipamentos, ' +
                            (data.prof  || 0).toLocaleString('pt-BR') + ' profissionais importados.';
                    } else if (data.status === 'erro') {
                        clearInterval(pollingInterval);
                        document.getElementById('bloco-progresso').classList.add('d-none');
                        document.getElementById('msg-erro').textContent = data.etapa || 'Erro desconhecido';
                        document.getElementById('bloco-erro').classList.remove('d-none');
                    }
                })
                .catch(function() {});
        }, 5000);
    }
})();
</script>
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
                    <?php
                    // O banco armazena código IBGE (ex: 33) ou sigla (ex: RJ) dependendo da versão do CSV
                    // Montamos opções com ambos os formatos para compatibilidade
                    $ufAtual = $filtros['uf'] ?? '';
                    $ibgeInverso = array_flip($ibgeParaUf); // sigla => codigo
                    foreach ($ufsDisponiveis as $uf):
                        $codigoIbge = $ibgeInverso[$uf] ?? $uf;
                        $selecionado = ($ufAtual === $uf || $ufAtual === $codigoIbge) ? 'selected' : '';
                    ?>
                        <option value="<?php echo $uf; ?>" <?php echo $selecionado; ?>>
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
                        <th class="ps-3" style="width:70px">CNES</th>
                        <th>Estabelecimento</th>
                        <th>CNPJ</th>
                        <th style="width:55px">UF</th>
                        <th style="width:80px">Tipo</th>
                        <th>Telefone</th>
                        <th class="text-center" style="width:90px">Status</th>
                        <th class="text-center" style="width:90px">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($resultado['registros'] as $estab):
                        // Converter código IBGE para sigla UF
                        $coEstado = trim((string)($estab->co_estado_gestor ?? ''));
                        $sgUf = $ibgeParaUf[$coEstado] ?? ($coEstado ?: null);

                        // Aplicar máscara no CNPJ: XX.XXX.XXX/XXXX-XX
                        $cnpjRaw = preg_replace('/\D/', '', (string)($estab->nu_cnpj ?? ''));
                        $cnpjFormatado = strlen($cnpjRaw) === 14
                            ? substr($cnpjRaw,0,2).'.'.substr($cnpjRaw,2,3).'.'.substr($cnpjRaw,5,3).'/'.substr($cnpjRaw,8,4).'-'.substr($cnpjRaw,12,2)
                            : ($estab->nu_cnpj ? htmlspecialchars($estab->nu_cnpj) : '—');

                        // Determinar Público / Privado
                        $coNat = trim((string)($estab->co_natureza_jur ?? $estab->co_natureza_juridica ?? ''));
                        $tipoNat = cnesNaturezaTipo($coNat);
                    ?>
                    <tr>
                        <td class="ps-3">
                            <span class="badge bg-primary-subtle text-primary fw-semibold">
                                <?php echo htmlspecialchars($estab->co_cnes); ?>
                            </span>
                        </td>
                        <td>
                            <div class="fw-semibold text-dark">
                                <?php echo htmlspecialchars($estab->no_razao_social); ?>
                            </div>
                            <?php if (!empty($estab->no_fantasia)): ?>
                            <small class="text-muted">
                                <?php echo htmlspecialchars($estab->no_fantasia); ?>
                            </small>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted small font-monospace">
                            <?php echo $cnpjFormatado; ?>
                        </td>
                        <td>
                            <?php if ($sgUf): ?>
                            <span class="badge bg-secondary-subtle text-secondary fw-semibold">
                                <?php echo htmlspecialchars($sgUf); ?>
                            </span>
                            <?php else: ?>
                            <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($tipoNat === 'publico'): ?>
                            <span class="badge bg-info-subtle text-info" title="Código: <?php echo htmlspecialchars($coNat); ?>">
                                <i class="fas fa-landmark me-1"></i>Público
                            </span>
                            <?php elseif ($tipoNat === 'privado'): ?>
                            <span class="badge bg-warning-subtle text-warning" title="Código: <?php echo htmlspecialchars($coNat); ?>">
                                <i class="fas fa-building me-1"></i>Privado
                            </span>
                            <?php else: ?>
                            <span class="text-muted small">—</span>
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
