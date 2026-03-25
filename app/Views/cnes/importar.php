<?php
/** @var array $historico */
/** @var int   $totalEstab */
?>
<style>
/* ── CNES Importar ─────────────────────────────────────────────────────── */
.import-hero {
    background: linear-gradient(135deg, #1a6b3c 0%, #2d9b5e 100%);
    border-radius: 16px;
    padding: 2.5rem;
    color: #fff;
    margin-bottom: 2rem;
    position: relative;
    overflow: hidden;
}
.import-hero::before {
    content: '🏥';
    position: absolute;
    right: 2rem;
    top: 50%;
    transform: translateY(-50%);
    font-size: 6rem;
    opacity: .12;
}
.import-hero h1 { font-size: 1.8rem; font-weight: 700; margin: 0 0 .5rem; }
.import-hero p  { margin: 0; opacity: .85; font-size: .95rem; }

.upload-zone {
    border: 2.5px dashed #c8e6c9;
    border-radius: 12px;
    background: #f9fffe;
    padding: 3rem 2rem;
    text-align: center;
    cursor: pointer;
    transition: all .2s;
}
.upload-zone:hover, .upload-zone.drag-over {
    border-color: #2d9b5e;
    background: #e8f5e9;
}
.upload-zone .icon { font-size: 3rem; margin-bottom: 1rem; }
.upload-zone h3    { color: #1a6b3c; margin: 0 0 .5rem; }
.upload-zone p     { color: #666; font-size: .9rem; margin: 0; }

.progress-card {
    display: none;
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 12px;
    padding: 1.5rem;
    margin-top: 1.5rem;
}
.progress-bar-wrap {
    background: #e8f5e9;
    border-radius: 50px;
    height: 12px;
    overflow: hidden;
    margin: 1rem 0;
}
.progress-bar-fill {
    height: 100%;
    background: linear-gradient(90deg, #2d9b5e, #4caf50);
    border-radius: 50px;
    transition: width .5s;
    width: 0%;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    padding: .3rem .8rem;
    border-radius: 50px;
    font-size: .8rem;
    font-weight: 600;
}
.status-badge.processando { background: #fff3e0; color: #e65100; }
.status-badge.concluido   { background: #e8f5e9; color: #1b5e20; }
.status-badge.erro        { background: #ffebee; color: #b71c1c; }

.hist-table th { background: #f5f5f5; font-size: .8rem; text-transform: uppercase; letter-spacing: .05em; }
.hist-table td { vertical-align: middle; font-size: .88rem; }

.info-box {
    background: #e3f2fd;
    border-left: 4px solid #1976d2;
    border-radius: 0 8px 8px 0;
    padding: 1rem 1.2rem;
    margin-bottom: 1.5rem;
}
.info-box h5 { color: #1565c0; margin: 0 0 .5rem; font-size: .95rem; }
.info-box p  { margin: 0; font-size: .88rem; color: #333; }
.info-box code { background: #bbdefb; padding: .1rem .4rem; border-radius: 4px; font-size: .82rem; }
</style>

<!-- Hero -->
<div class="import-hero">
    <h1>Importar Base CNES</h1>
    <p>Cadastro Nacional de Estabelecimentos de Saúde — DATASUS/CNES<br>
       Faça upload do arquivo ZIP mensal para importar ou atualizar a base completa.</p>
</div>

<div class="row g-4">

    <!-- Coluna principal: upload -->
    <div class="col-lg-8">

        <!-- Info box -->
        <div class="info-box">
            <h5>📥 Como obter o arquivo ZIP</h5>
            <p>Acesse <a href="https://cnes.datasus.gov.br/pages/downloads/arquivosBaseDados.jsp" target="_blank">cnes.datasus.gov.br → Downloads → Base de Dados</a> e baixe o arquivo <code>BASE_DE_DADOS_CNES_AAAAMM.ZIP</code>. A base é atualizada mensalmente pelo DATASUS.</p>
        </div>

        <!-- Upload form -->
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-3">
                    <i class="bi bi-cloud-upload text-success me-2"></i>Upload da Base CNES
                </h5>

                <form id="formImportar" enctype="multipart/form-data">
                    <!-- Drop zone -->
                    <div class="upload-zone" id="dropZone" onclick="document.getElementById('arquivoZip').click()">
                        <div class="icon">📦</div>
                        <h3>Arraste o arquivo ZIP aqui</h3>
                        <p>ou clique para selecionar — BASE_DE_DADOS_CNES_AAAAMM.ZIP</p>
                        <p class="mt-2 text-muted" style="font-size:.8rem">Tamanho máximo: 2 GB</p>
                    </div>
                    <input type="file" id="arquivoZip" name="arquivo_zip" accept=".zip" style="display:none">

                    <!-- Arquivo selecionado -->
                    <div id="arquivoSelecionado" class="alert alert-success mt-3" style="display:none">
                        <i class="bi bi-file-zip me-2"></i>
                        <span id="nomeArquivo"></span>
                        <span class="text-muted ms-2" id="tamanhoArquivo"></span>
                    </div>

                    <!-- Opções -->
                    <div class="row g-3 mt-2">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Filtrar por UF <span class="text-muted">(opcional)</span></label>
                            <select name="uf" class="form-select">
                                <option value="">Todos os estados</option>
                                <?php foreach (['AC','AL','AM','AP','BA','CE','DF','ES','GO','MA','MG','MS','MT','PA','PB','PE','PI','PR','RJ','RN','RO','RR','RS','SC','SE','SP','TO'] as $uf): ?>
                                <option value="<?= $uf ?>"><?= $uf ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Importar apenas um estado acelera o processo.</div>
                        </div>
                        <div class="col-md-8 d-flex align-items-end">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="apenas_imagem" id="apenasImagem" value="1">
                                <label class="form-check-label" for="apenasImagem">
                                    <strong>Apenas Diagnóstico por Imagem</strong>
                                    <div class="form-text">Importa somente estabelecimentos com equipamentos de imagem (Raio-X, TC, RM, US, etc.)</div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Botão -->
                    <div class="mt-4">
                        <button type="submit" class="btn btn-success btn-lg px-5" id="btnImportar" disabled>
                            <i class="bi bi-cloud-upload me-2"></i>Iniciar Importação
                        </button>
                        <span class="text-muted ms-3" style="font-size:.85rem">
                            A importação é executada em segundo plano e pode levar de 5 a 30 minutos.
                        </span>
                    </div>
                </form>

                <!-- Progresso -->
                <div class="progress-card" id="progressCard">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0" id="progressTitle">Importando base CNES...</h6>
                        <span class="status-badge processando" id="progressBadge">
                            <span class="spinner-border spinner-border-sm"></span> Processando
                        </span>
                    </div>
                    <div class="progress-bar-wrap">
                        <div class="progress-bar-fill" id="progressBar"></div>
                    </div>
                    <div class="row text-center g-2 mt-1">
                        <div class="col-4">
                            <div class="fw-bold text-success fs-5" id="cntEstab">0</div>
                            <div class="text-muted" style="font-size:.8rem">Estabelecimentos</div>
                        </div>
                        <div class="col-4">
                            <div class="fw-bold text-primary fs-5" id="cntEquip">0</div>
                            <div class="text-muted" style="font-size:.8rem">Equipamentos</div>
                        </div>
                        <div class="col-4">
                            <div class="fw-bold text-warning fs-5" id="cntProf">0</div>
                            <div class="text-muted" style="font-size:.8rem">Profissionais</div>
                        </div>
                    </div>
                    <div class="mt-2 text-muted" style="font-size:.85rem" id="progressMsg"></div>
                </div>
            </div>
        </div>

        <!-- Histórico de importações -->
        <?php if (!empty($historico)): ?>
        <div class="card shadow-sm border-0 mt-4">
            <div class="card-header bg-white border-bottom">
                <h6 class="fw-bold mb-0"><i class="bi bi-clock-history me-2 text-secondary"></i>Histórico de Importações</h6>
            </div>
            <div class="card-body p-0">
                <table class="table hist-table mb-0">
                    <thead>
                        <tr>
                            <th class="ps-3">Competência</th>
                            <th>Status</th>
                            <th>Estabelecimentos</th>
                            <th>Equipamentos</th>
                            <th>Profissionais</th>
                            <th>Iniciado em</th>
                            <th>Concluído em</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historico as $h): ?>
                        <tr>
                            <td class="ps-3 fw-semibold">
                                <?php
                                $comp = $h->competencia ?? '';
                                if (strlen($comp) === 6) {
                                    $meses = ['01'=>'Jan','02'=>'Fev','03'=>'Mar','04'=>'Abr','05'=>'Mai','06'=>'Jun',
                                              '07'=>'Jul','08'=>'Ago','09'=>'Set','10'=>'Out','11'=>'Nov','12'=>'Dez'];
                                    $m = substr($comp, 4, 2);
                                    $a = substr($comp, 0, 4);
                                    echo ($meses[$m] ?? $m) . '/' . $a;
                                } else {
                                    echo htmlspecialchars($comp);
                                }
                                ?>
                            </td>
                            <td>
                                <?php if ($h->status === 'concluido'): ?>
                                    <span class="status-badge concluido"><i class="bi bi-check-circle"></i> Concluído</span>
                                <?php elseif ($h->status === 'processando'): ?>
                                    <span class="status-badge processando"><span class="spinner-border spinner-border-sm"></span> Processando</span>
                                <?php else: ?>
                                    <span class="status-badge erro"><i class="bi bi-x-circle"></i> Erro</span>
                                <?php endif; ?>
                            </td>
                            <td><?= number_format((int)($h->total_estab ?? 0)) ?></td>
                            <td><?= number_format((int)($h->total_equip ?? 0)) ?></td>
                            <td><?= number_format((int)($h->total_prof ?? 0)) ?></td>
                            <td><?= $h->iniciado_em ? date('d/m/Y H:i', strtotime($h->iniciado_em)) : '—' ?></td>
                            <td><?= $h->concluido_em ? date('d/m/Y H:i', strtotime($h->concluido_em)) : '—' ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <!-- Coluna lateral: instruções e stats -->
    <div class="col-lg-4">

        <!-- Stats atuais -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body text-center p-4">
                <div style="font-size:3rem">🏥</div>
                <div class="fw-bold fs-3 text-success mt-2"><?= number_format($totalEstab) ?></div>
                <div class="text-muted">Estabelecimentos na base atual</div>
                <?php if ($totalEstab > 0): ?>
                <a href="/cnes" class="btn btn-outline-success btn-sm mt-3">
                    <i class="bi bi-list me-1"></i>Ver listagem
                </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Instruções alternativas -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-bottom">
                <h6 class="fw-bold mb-0"><i class="bi bi-terminal me-2 text-secondary"></i>Importação via SSH</h6>
            </div>
            <div class="card-body">
                <p class="text-muted" style="font-size:.88rem">Para servidores com limite de upload, use o SSH:</p>
                <ol style="font-size:.85rem; padding-left:1.2rem;">
                    <li class="mb-2">Execute a migration SQL:<br>
                        <code style="font-size:.78rem; background:#f5f5f5; padding:.2rem .4rem; border-radius:4px; display:block; margin-top:.3rem;">mysql -u user -p banco &lt; database/migrations/2026-03-25_cnes_global.sql</code>
                    </li>
                    <li class="mb-2">Extraia o ZIP:<br>
                        <code style="font-size:.78rem; background:#f5f5f5; padding:.2rem .4rem; border-radius:4px; display:block; margin-top:.3rem;">unzip BASE_CNES.ZIP -d /tmp/cnes_base/</code>
                    </li>
                    <li class="mb-2">Execute o script:<br>
                        <code style="font-size:.78rem; background:#f5f5f5; padding:.2rem .4rem; border-radius:4px; display:block; margin-top:.3rem;">php database/importar_cnes.php --dir=/tmp/cnes_base --uf=MG</code>
                    </li>
                </ol>
                <p class="text-muted mt-2" style="font-size:.82rem">
                    <i class="bi bi-info-circle me-1"></i>
                    Omita <code>--uf</code> para importar todos os estados (~605 mil estabelecimentos).
                </p>
            </div>
        </div>

        <!-- Agendamento mensal -->
        <div class="card shadow-sm border-0 mt-4">
            <div class="card-header bg-white border-bottom">
                <h6 class="fw-bold mb-0"><i class="bi bi-calendar-check me-2 text-secondary"></i>Atualização Mensal</h6>
            </div>
            <div class="card-body">
                <p style="font-size:.88rem; color:#555;">O DATASUS publica a base CNES toda <strong>primeira semana de cada mês</strong>. Para automatizar a atualização via cron:</p>
                <code style="font-size:.75rem; background:#f5f5f5; padding:.5rem; border-radius:6px; display:block; line-height:1.6;">
                    # Cron: todo dia 10 às 3h<br>
                    0 3 10 * * php /caminho/database/importar_cnes.php --dir=/tmp/cnes_base --uf=MG
                </code>
                <p class="mt-2 text-muted" style="font-size:.82rem">
                    O script usa <code>INSERT ... ON DUPLICATE KEY UPDATE</code>, então re-executar é seguro — apenas atualiza registros existentes.
                </p>
            </div>
        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const dropZone   = document.getElementById('dropZone');
    const fileInput  = document.getElementById('arquivoZip');
    const btnImportar = document.getElementById('btnImportar');
    const arquivoSel = document.getElementById('arquivoSelecionado');
    const nomeArq    = document.getElementById('nomeArquivo');
    const tamanhoArq = document.getElementById('tamanhoArquivo');
    const form       = document.getElementById('formImportar');
    const progressCard = document.getElementById('progressCard');
    let pollingInterval = null;
    let competenciaAtual = null;

    // Drag & Drop
    ['dragenter','dragover'].forEach(e => dropZone.addEventListener(e, ev => {
        ev.preventDefault();
        dropZone.classList.add('drag-over');
    }));
    ['dragleave','drop'].forEach(e => dropZone.addEventListener(e, ev => {
        ev.preventDefault();
        dropZone.classList.remove('drag-over');
    }));
    dropZone.addEventListener('drop', ev => {
        const files = ev.dataTransfer.files;
        if (files.length) {
            fileInput.files = files;
            mostrarArquivo(files[0]);
        }
    });

    // Seleção de arquivo
    fileInput.addEventListener('change', function () {
        if (this.files.length) mostrarArquivo(this.files[0]);
    });

    function mostrarArquivo(file) {
        nomeArq.textContent    = file.name;
        tamanhoArq.textContent = '(' + formatBytes(file.size) + ')';
        arquivoSel.style.display = 'block';
        btnImportar.disabled   = false;
    }

    function formatBytes(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
        if (bytes < 1073741824) return (bytes / 1048576).toFixed(1) + ' MB';
        return (bytes / 1073741824).toFixed(2) + ' GB';
    }

    // Submit
    form.addEventListener('submit', function (e) {
        e.preventDefault();

        if (!fileInput.files.length) {
            Swal.fire('Atenção', 'Selecione o arquivo ZIP da base CNES.', 'warning');
            return;
        }

        btnImportar.disabled = true;
        btnImportar.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Enviando...';

        const fd = new FormData(form);

        // Mostrar progresso
        progressCard.style.display = 'block';
        progressCard.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

        fetch('/cnes/importar/upload', {
            method: 'POST',
            body: fd,
        })
        .then(r => r.json())
        .then(data => {
            if (!data.success) {
                Swal.fire('Erro', data.error || 'Erro ao iniciar importação.', 'error');
                btnImportar.disabled = false;
                btnImportar.innerHTML = '<i class="bi bi-cloud-upload me-2"></i>Iniciar Importação';
                return;
            }

            competenciaAtual = data.competencia;
            document.getElementById('progressMsg').textContent = data.message;
            btnImportar.innerHTML = '<i class="bi bi-cloud-upload me-2"></i>Importando...';

            // Iniciar polling
            pollingInterval = setInterval(verificarStatus, 5000);
            verificarStatus();
        })
        .catch(err => {
            Swal.fire('Erro', 'Falha na comunicação com o servidor: ' + err.message, 'error');
            btnImportar.disabled = false;
            btnImportar.innerHTML = '<i class="bi bi-cloud-upload me-2"></i>Iniciar Importação';
        });
    });

    function verificarStatus() {
        fetch('/cnes/importar/status', { cache: 'no-store' })
        .then(r => r.json())
        .then(data => {
            // Atualizar contadores com campos do CnesImportService
            const estab = data.estab || data.db_estab || data.total_estab || 0;
            const equip = data.equip || data.db_equip || data.total_equip || 0;
            const prof  = data.prof  || data.db_prof  || data.total_prof  || 0;

            document.getElementById('cntEstab').textContent = Number(estab).toLocaleString('pt-BR');
            document.getElementById('cntEquip').textContent = Number(equip).toLocaleString('pt-BR');
            document.getElementById('cntProf').textContent  = Number(prof).toLocaleString('pt-BR');

            // Mensagem da etapa atual
            const msg = data.etapa || data.message || '';
            document.getElementById('progressMsg').textContent = msg;

            // Barra de progresso com percentual real
            const pct = data.pct || 0;
            if (pct > 0) {
                document.getElementById('progressBar').style.width = Math.min(pct, 99) + '%';
            } else {
                // Animação indeterminada
                const bar = document.getElementById('progressBar');
                const w = parseFloat(bar.style.width || '0');
                bar.style.width = Math.min(w + 1.5, 90) + '%';
            }

            const badge = document.getElementById('progressBadge');

            if (data.status === 'concluido') {
                clearInterval(pollingInterval);
                badge.className = 'status-badge concluido';
                badge.innerHTML = '<i class="bi bi-check-circle"></i> Concluído';
                document.getElementById('progressBar').style.width = '100%';
                document.getElementById('progressTitle').textContent = 'Importação concluída!';
                btnImportar.innerHTML = '<i class="bi bi-cloud-upload me-2"></i>Iniciar Nova Importação';
                btnImportar.disabled = false;
                Swal.fire({
                    icon: 'success',
                    title: 'Importação concluída!',
                    html: `<strong>${Number(estab).toLocaleString('pt-BR')}</strong> estabelecimentos, <strong>${Number(equip).toLocaleString('pt-BR')}</strong> equipamentos e <strong>${Number(prof).toLocaleString('pt-BR')}</strong> profissionais importados.`,
                    confirmButtonText: 'Ver listagem CNES',
                }).then(() => window.location.href = '/cnes');
            } else if (data.status === 'erro') {
                clearInterval(pollingInterval);
                badge.className = 'status-badge erro';
                badge.innerHTML = '<i class="bi bi-x-circle"></i> Erro';
                document.getElementById('progressTitle').textContent = 'Erro na importação';
                btnImportar.innerHTML = '<i class="bi bi-cloud-upload me-2"></i>Tentar Novamente';
                btnImportar.disabled = false;
                const erros = data.erros ? data.erros.join('<br>') : (data.etapa || 'Verifique o log do servidor.');
                Swal.fire('Erro na importação', erros, 'error');
            }
        })
        .catch(() => {
            // Silencioso — pode ser timeout temporário durante processamento
        });
    }

    // Verificar se há importação em andamento ao carregar
    <?php foreach ($historico as $h): if ($h->status === 'processando'): ?>
    competenciaAtual = '<?= htmlspecialchars($h->competencia) ?>';
    progressCard.style.display = 'block';
    pollingInterval = setInterval(verificarStatus, 5000);
    verificarStatus();
    <?php break; endif; endforeach; ?>
});
</script>
