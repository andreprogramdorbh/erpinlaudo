<?php
use App\Core\UI;
$isEdit    = !empty($contrato);
$cId       = $isEdit ? $contrato->id : 0;
$activeTab = $active_tab ?? 'dados';

$actions = [
    ['text' => 'Voltar', 'link' => '/contratos', 'icon' => 'fas fa-arrow-left', 'class' => 'btn-outline-secondary'],
];
UI::sectionHeader(
    $isEdit ? 'Editar Contrato' : 'Novo Contrato',
    $isEdit ? 'Número: ' . htmlspecialchars($contrato->numero) : 'Preencha os dados do contrato',
    $actions
);
?>

<?php
$success = $_GET['success'] ?? '';
$error   = $_GET['error'] ?? '';
if ($success === 'created')         echo '<div class="alert alert-success border-0 shadow-sm">Contrato criado! Agora adicione os anexos.</div>';
if ($success === 'updated')         echo '<div class="alert alert-success border-0 shadow-sm">Contrato atualizado com sucesso.</div>';
if ($success === 'upload_ok')       echo '<div class="alert alert-success border-0 shadow-sm">Arquivo(s) anexado(s) com sucesso.</div>';
if ($success === 'anexo_deleted')   echo '<div class="alert alert-success border-0 shadow-sm">Anexo removido.</div>';
if ($success === 'apuracao_criada') echo '<div class="alert alert-success border-0 shadow-sm">Nova apuração criada. Importe o arquivo para iniciar.</div>';
if ($error === 'campos_obrigatorios') echo '<div class="alert alert-danger border-0 shadow-sm">Preencha todos os campos obrigatórios.</div>';
if ($error === 'db_error')          echo '<div class="alert alert-danger border-0 shadow-sm">Erro ao salvar. Tente novamente.</div>';
?>

<!-- ABAS -->
<ul class="nav nav-tabs mb-4" id="contratoTabs">
    <li class="nav-item">
        <a class="nav-link <?php echo $activeTab === 'dados' ? 'active' : ''; ?>"
           href="<?php echo $isEdit ? '/contratos/edit/'.$cId.'?tab=dados' : '#'; ?>"
           <?php if (!$isEdit): ?>onclick="switchTab('dados')" style="cursor:pointer"<?php endif; ?>>
            <i class="fas fa-file-alt me-1"></i> Dados do Contrato
        </a>
    </li>
    <?php if ($isEdit): ?>
    <li class="nav-item">
        <a class="nav-link <?php echo $activeTab === 'anexos' ? 'active' : ''; ?>"
           href="/contratos/edit/<?php echo $cId; ?>?tab=anexos">
            <i class="fas fa-paperclip me-1"></i> Anexos
            <?php if (!empty($anexos)): ?>
                <span class="badge bg-secondary ms-1"><?php echo count($anexos); ?></span>
            <?php endif; ?>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo $activeTab === 'servicos' ? 'active' : ''; ?>"
           href="/contratos/edit/<?php echo $cId; ?>?tab=servicos">
            <i class="fas fa-stethoscope me-1"></i> Serviços/Exames
            <?php if (!empty($contrato_exames)): ?>
                <span class="badge bg-info ms-1"><?php echo count($contrato_exames); ?></span>
            <?php endif; ?>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo $activeTab === 'apuracao' ? 'active' : ''; ?>"
           href="/contratos/edit/<?php echo $cId; ?>?tab=apuracao">
            <i class="fas fa-calculator me-1"></i> Apuração
            <?php if (!empty($apuracoes)): ?>
                <span class="badge bg-primary ms-1"><?php echo count($apuracoes); ?></span>
            <?php endif; ?>
        </a>
    </li>
    <?php endif; ?>
</ul>

<!-- ============================================================ -->
<!-- ABA: DADOS -->
<!-- ============================================================ -->
<div id="tab-dados" class="tab-content-section <?php echo $activeTab !== 'dados' ? 'd-none' : ''; ?>">
<form method="POST" action="<?php echo $isEdit ? '/contratos/update/'.$cId : '/contratos/store'; ?>">
    <input type="hidden" name="active_tab" value="dados">

    <div class="row g-4">
        <!-- Coluna principal -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-bold"><i class="fas fa-info-circle me-2 text-primary"></i>Informações do Contrato</h6>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Nome do Contrato <span class="text-danger">*</span></label>
                            <input type="text" name="nome" class="form-control"
                                   placeholder="Ex: Contrato de Teleradiologia — Dr. João Silva"
                                   value="<?php echo htmlspecialchars($contrato->nome ?? ''); ?>" required>
                        </div>

                        <!-- Tipo de parte -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tipo de Contrato <span class="text-danger">*</span></label>
                            <select name="tipo_parte" id="tipo_parte" class="form-select" onchange="toggleParte(this.value)">
                                <option value="medico"  <?php echo ($contrato->tipo_parte ?? 'medico') === 'medico'  ? 'selected' : ''; ?>>
                                    Médico (Pagamento)
                                </option>
                                <option value="cliente" <?php echo ($contrato->tipo_parte ?? '') === 'cliente' ? 'selected' : ''; ?>>
                                    Cliente (Recebimento)
                                </option>
                            </select>
                            <small class="text-muted">Médico = contrato de pagamento de honorários. Cliente = contrato de recebimento.</small>
                        </div>

                        <!-- Médico -->
                        <div class="col-md-6" id="campo-medico" <?php echo ($contrato->tipo_parte ?? 'medico') === 'cliente' ? 'style="display:none"' : ''; ?>>
                            <label class="form-label fw-semibold">Médico</label>
                            <select name="medico_id" class="form-select">
                                <option value="">Selecione o médico</option>
                                <?php foreach ($medicos ?? [] as $m): ?>
                                    <option value="<?php echo $m->id; ?>"
                                        <?php echo ($contrato->medico_id ?? 0) == $m->id ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($m->nome); ?> — CRM <?php echo htmlspecialchars($m->crm); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Cliente -->
                        <div class="col-md-6" id="campo-cliente" <?php echo ($contrato->tipo_parte ?? 'medico') === 'medico' ? 'style="display:none"' : ''; ?>>
                            <label class="form-label fw-semibold">Cliente</label>
                            <select name="cliente_id" class="form-select">
                                <option value="">Selecione o cliente</option>
                                <?php foreach ($clientes ?? [] as $cl): ?>
                                    <option value="<?php echo $cl->id; ?>"
                                        <?php echo ($contrato->cliente_id ?? 0) == $cl->id ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cl->razao_social ?? $cl->nome ?? ''); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Datas -->
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Data de Início <span class="text-danger">*</span></label>
                            <input type="date" name="data_inicio" class="form-control"
                                   value="<?php echo $contrato->data_inicio ?? date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Data de Fim</label>
                            <input type="date" name="data_fim" class="form-control" id="campo-data-fim"
                                   value="<?php echo $contrato->data_fim ?? ''; ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Vigência</label>
                            <select name="vigencia_tipo" class="form-select" onchange="toggleVigencia(this.value)">
                                <option value="determinado"    <?php echo ($contrato->vigencia_tipo ?? 'determinado') === 'determinado'    ? 'selected' : ''; ?>>Prazo Determinado</option>
                                <option value="indeterminado"  <?php echo ($contrato->vigencia_tipo ?? '') === 'indeterminado' ? 'selected' : ''; ?>>Prazo Indeterminado</option>
                            </select>
                        </div>

                        <!-- Recorrência e Valor -->
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Recorrência</label>
                            <select name="recorrencia" class="form-select">
                                <option value="diario"   <?php echo ($contrato->recorrencia ?? '') === 'diario'   ? 'selected' : ''; ?>>Diário</option>
                                <option value="semanal"  <?php echo ($contrato->recorrencia ?? '') === 'semanal'  ? 'selected' : ''; ?>>Semanal</option>
                                <option value="mensal"   <?php echo ($contrato->recorrencia ?? 'mensal') === 'mensal'   ? 'selected' : ''; ?>>Mensal</option>
                                <option value="anual"    <?php echo ($contrato->recorrencia ?? '') === 'anual'    ? 'selected' : ''; ?>>Anual</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Valor do Contrato (R$)</label>
                            <input type="text" name="valor" class="form-control money-mask"
                                   placeholder="0,00"
                                   value="<?php echo $contrato ? number_format((float)$contrato->valor, 2, ',', '.') : '0,00'; ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Status</label>
                            <select name="status" class="form-select">
                                <option value="ativo"     <?php echo ($contrato->status ?? 'ativo') === 'ativo'     ? 'selected' : ''; ?>>Ativo</option>
                                <option value="suspenso"  <?php echo ($contrato->status ?? '') === 'suspenso'  ? 'selected' : ''; ?>>Suspenso</option>
                                <option value="encerrado" <?php echo ($contrato->status ?? '') === 'encerrado' ? 'selected' : ''; ?>>Encerrado</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Observações</label>
                            <textarea name="observacoes" class="form-control" rows="3"
                                      placeholder="Observações gerais sobre o contrato..."><?php echo htmlspecialchars($contrato->observacoes ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Coluna lateral: Modalidades (apenas médico) -->
        <div class="col-lg-4" id="painel-modalidades" <?php echo ($contrato->tipo_parte ?? 'medico') === 'cliente' ? 'style="display:none"' : ''; ?>>
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold"><i class="fas fa-stethoscope me-2 text-info"></i>Modalidades / Exames</h6>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="adicionarModalidade()">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
                <div class="card-body p-3">
                    <p class="text-muted small mb-3">Selecione as modalidades e exames que este médico realiza neste contrato.</p>
                    <div id="lista-modalidades">
                        <?php
                        $modsContrato = $modalidades_contrato ?? [];
                        if (empty($modsContrato)):
                        ?>
                            <div class="modalidade-row row g-2 mb-2" id="mod-row-0">
                                <div class="col-5">
                                    <select name="modalidades[]" class="form-select form-select-sm">
                                        <option value="">Modalidade</option>
                                        <?php foreach (['TC','RM','RX','US','DX','CR','MG','NM','PT','RF','XA','OT'] as $mod): ?>
                                            <option value="<?php echo $mod; ?>"><?php echo $mod; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <select name="modalidade_exame_id[]" class="form-select form-select-sm">
                                        <option value="">Qualquer exame</option>
                                        <?php foreach ($exames ?? [] as $ex): ?>
                                            <option value="<?php echo $ex->id; ?>"><?php echo htmlspecialchars($ex->nome_exame); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-1">
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="removerModalidade(0)"><i class="fas fa-times"></i></button>
                                </div>
                            </div>
                        <?php else: ?>
                            <?php foreach ($modsContrato as $i => $mc): ?>
                            <div class="modalidade-row row g-2 mb-2" id="mod-row-<?php echo $i; ?>">
                                <div class="col-5">
                                    <select name="modalidades[]" class="form-select form-select-sm">
                                        <option value="">Modalidade</option>
                                        <?php foreach (['TC','RM','RX','US','DX','CR','MG','NM','PT','RF','XA','OT'] as $mod): ?>
                                            <option value="<?php echo $mod; ?>" <?php echo $mc->modalidade === $mod ? 'selected' : ''; ?>><?php echo $mod; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <select name="modalidade_exame_id[]" class="form-select form-select-sm">
                                        <option value="">Qualquer exame</option>
                                        <?php foreach ($exames ?? [] as $ex): ?>
                                            <option value="<?php echo $ex->id; ?>" <?php echo ($mc->exame_id ?? 0) == $ex->id ? 'selected' : ''; ?>><?php echo htmlspecialchars($ex->nome_exame); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-1">
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="removerModalidade(<?php echo $i; ?>)"><i class="fas fa-times"></i></button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn btn-primary px-4">
            <i class="fas fa-save me-2"></i><?php echo $isEdit ? 'Salvar Alterações' : 'Criar Contrato'; ?>
        </button>
        <a href="/contratos" class="btn btn-outline-secondary">Cancelar</a>
    </div>
</form>
</div>

<!-- ============================================================ -->
<!-- ABA: ANEXOS -->
<!-- ============================================================ -->
<?php if ($isEdit): ?>
<div id="tab-anexos" class="tab-content-section <?php echo $activeTab !== 'anexos' ? 'd-none' : ''; ?>">
    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-bold"><i class="fas fa-upload me-2 text-primary"></i>Adicionar Anexo</h6>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="/contratos/upload-anexo" enctype="multipart/form-data">
                        <input type="hidden" name="contrato_id" value="<?php echo $cId; ?>">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Arquivo(s)</label>
                            <input type="file" name="anexo[]" class="form-control" multiple
                                   accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.xlsx,.xls">
                            <small class="text-muted">PDF, Word, Excel, Imagens. Máx. 10MB por arquivo.</small>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-upload me-2"></i>Enviar Arquivo(s)
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-bold"><i class="fas fa-paperclip me-2 text-secondary"></i>Arquivos Anexados</h6>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($anexos)): ?>
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-folder-open fa-2x mb-2"></i>
                            <p class="mb-0">Nenhum arquivo anexado.</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                        <?php foreach ($anexos as $a): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                                <div class="d-flex align-items-center gap-3">
                                    <?php
                                    $ext = strtolower(pathinfo($a->original_name, PATHINFO_EXTENSION));
                                    $iconClass = match($ext) {
                                        'pdf'  => 'fas fa-file-pdf text-danger',
                                        'doc','docx' => 'fas fa-file-word text-primary',
                                        'xls','xlsx' => 'fas fa-file-excel text-success',
                                        'jpg','jpeg','png' => 'fas fa-file-image text-warning',
                                        default => 'fas fa-file text-secondary',
                                    };
                                    ?>
                                    <i class="<?php echo $iconClass; ?> fa-lg"></i>
                                    <div>
                                        <div class="fw-semibold small"><?php echo htmlspecialchars($a->original_name); ?></div>
                                        <small class="text-muted">
                                            <?php echo $a->file_size ? number_format($a->file_size / 1024, 1) . ' KB' : ''; ?>
                                            · <?php echo date('d/m/Y H:i', strtotime($a->created_at)); ?>
                                        </small>
                                    </div>
                                </div>
                                <div class="d-flex gap-2">
                                    <a href="/<?php echo $a->file_path; ?>" target="_blank" class="btn btn-sm btn-outline-secondary" title="Visualizar">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="/contratos/delete-anexo/<?php echo $a->id; ?>?contrato_id=<?php echo $cId; ?>"
                                       class="btn btn-sm btn-outline-danger"
                                       onclick="return confirm('Remover este anexo?')" title="Remover">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- ABA: SERVIÇOS/EXAMES -->
<!-- ============================================================ -->
<?php
$tipoParte = $contrato->tipo_parte ?? 'medico';
$ehMedico  = $tipoParte === 'medico';
?>
<div id="tab-servicos" class="tab-content-section <?php echo $activeTab !== 'servicos' ? 'd-none' : ''; ?>">

    <!-- Formulário para adicionar exame -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom py-3">
            <h6 class="mb-0"><i class="fas fa-plus-circle text-primary me-2"></i>Adicionar Exame ao Contrato</h6>
            <small class="text-muted">
                <?php if ($ehMedico): ?>Selecione o exame e ajuste os valores de repasse ao médico (Rotina/Urgência).
                <?php else: ?>Selecione o exame e ajuste os valores de venda ao cliente (Rotina/Urgência).
                <?php endif; ?>
                Se os valores forem alterados e salvos, passam a ser a <strong>base contábil</strong> deste contrato.
            </small>
        </div>
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <!-- Seletor de exame -->
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Exame / Serviço <span class="text-danger">*</span></label>
                    <select id="ce-exame-select" class="form-select" onchange="carregarValoresExame(this.value)">
                        <option value="">Selecione o exame...</option>
                        <?php foreach ($exames as $ex): ?>
                        <option value="<?php echo $ex->id; ?>"
                                data-rotina="<?php echo number_format((float)($ex->valor_rotina ?? 0), 2, '.', ''); ?>"
                                data-urgencia="<?php echo number_format((float)($ex->valor_urgencia ?? 0), 2, '.', ''); ?>"
                                data-venda-rotina="<?php echo number_format((float)($ex->valor_venda_rotina ?? 0), 2, '.', ''); ?>"
                                data-venda-urgencia="<?php echo number_format((float)($ex->valor_venda_urgencia ?? 0), 2, '.', ''); ?>">
                            <?php echo htmlspecialchars($ex->nome_exame); ?>
                            <?php if ($ex->modalidade): ?>(<?php echo htmlspecialchars($ex->modalidade); ?>)<?php endif; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <?php if ($ehMedico): ?>
                <!-- Valores para médico (prestador) -->
                <div class="col-md-2">
                    <label class="form-label fw-semibold text-success"><i class="fas fa-tag me-1"></i>Rotina (Médico)</label>
                    <div class="input-group">
                        <span class="input-group-text">R$</span>
                        <input type="number" id="ce-valor-rotina" class="form-control" step="0.01" min="0" placeholder="0,00">
                    </div>
                    <small class="text-muted" id="ce-hint-rotina"></small>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold text-warning"><i class="fas fa-bolt me-1"></i>Urgência (Médico)</label>
                    <div class="input-group">
                        <span class="input-group-text">R$</span>
                        <input type="number" id="ce-valor-urgencia" class="form-control" step="0.01" min="0" placeholder="0,00">
                    </div>
                    <small class="text-muted" id="ce-hint-urgencia"></small>
                </div>
                <?php else: ?>
                <!-- Valores para cliente (venda) -->
                <div class="col-md-2">
                    <label class="form-label fw-semibold text-success"><i class="fas fa-tag me-1"></i>Venda Rotina</label>
                    <div class="input-group">
                        <span class="input-group-text">R$</span>
                        <input type="number" id="ce-valor-venda-rotina" class="form-control" step="0.01" min="0" placeholder="0,00">
                    </div>
                    <small class="text-muted" id="ce-hint-venda-rotina"></small>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold text-warning"><i class="fas fa-bolt me-1"></i>Venda Urgência</label>
                    <div class="input-group">
                        <span class="input-group-text">R$</span>
                        <input type="number" id="ce-valor-venda-urgencia" class="form-control" step="0.01" min="0" placeholder="0,00">
                    </div>
                    <small class="text-muted" id="ce-hint-venda-urgencia"></small>
                </div>
                <?php endif; ?>

                <div class="col-md-2">
                    <div class="form-check mt-4">
                        <input class="form-check-input" type="checkbox" id="ce-usa-custom" checked>
                        <label class="form-check-label" for="ce-usa-custom">
                            Usar valores do contrato
                        </label>
                    </div>
                </div>

                <div class="col-md-2">
                    <button type="button" class="btn btn-primary w-100" onclick="salvarExameContrato()">
                        <i class="fas fa-plus me-1"></i> Adicionar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabela de exames vinculados -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="fas fa-list text-primary me-2"></i>Exames Vinculados ao Contrato</h6>
            <span class="badge bg-secondary"><?php echo count($contrato_exames ?? []); ?> exame(s)</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tabela-contrato-exames">
                <thead class="table-light">
                    <tr>
                        <th>Exame / Serviço</th>
                        <th>Modalidade</th>
                        <?php if ($ehMedico): ?>
                        <th class="text-success">Rotina (Médico)</th>
                        <th class="text-warning">Urgência (Médico)</th>
                        <th>Tabela Rotina</th>
                        <th>Tabela Urgência</th>
                        <?php else: ?>
                        <th class="text-success">Venda Rotina</th>
                        <th class="text-warning">Venda Urgência</th>
                        <th>Tabela Venda Rotina</th>
                        <th>Tabela Venda Urgência</th>
                        <?php endif; ?>
                        <th>Base Contábil</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($contrato_exames)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            <i class="fas fa-stethoscope fa-2x mb-2 d-block opacity-25"></i>
                            Nenhum exame vinculado. Adicione exames acima.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($contrato_exames as $ce): ?>
                    <tr id="ce-row-<?php echo $ce->tabela_exame_id; ?>">
                        <td><strong><?php echo htmlspecialchars($ce->nome_exame); ?></strong></td>
                        <td><span class="badge bg-secondary"><?php echo htmlspecialchars($ce->modalidade ?? '-'); ?></span></td>
                        <?php if ($ehMedico): ?>
                        <td class="text-success fw-semibold">R$ <?php echo number_format((float)$ce->valor_rotina, 2, ',', '.'); ?></td>
                        <td class="text-warning fw-semibold">R$ <?php echo number_format((float)$ce->valor_urgencia, 2, ',', '.'); ?></td>
                        <td class="text-muted small">R$ <?php echo number_format((float)$ce->tabela_valor_rotina, 2, ',', '.'); ?></td>
                        <td class="text-muted small">R$ <?php echo number_format((float)$ce->tabela_valor_urgencia, 2, ',', '.'); ?></td>
                        <?php else: ?>
                        <td class="text-success fw-semibold">R$ <?php echo number_format((float)$ce->valor_venda_rotina, 2, ',', '.'); ?></td>
                        <td class="text-warning fw-semibold">R$ <?php echo number_format((float)$ce->valor_venda_urgencia, 2, ',', '.'); ?></td>
                        <td class="text-muted small">R$ <?php echo number_format((float)$ce->tabela_valor_venda_rotina, 2, ',', '.'); ?></td>
                        <td class="text-muted small">R$ <?php echo number_format((float)$ce->tabela_valor_venda_urgencia, 2, ',', '.'); ?></td>
                        <?php endif; ?>
                        <td>
                            <?php if ($ce->usa_valor_custom): ?>
                                <span class="badge bg-success"><i class="fas fa-check me-1"></i>Contrato</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Tabela</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-outline-danger" title="Remover"
                                    onclick="removerExameContrato(<?php echo $ce->tabela_exame_id; ?>, '<?php echo htmlspecialchars(addslashes($ce->nome_exame)); ?>')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Alerta informativo -->
    <div class="alert alert-info border-0 mt-3 shadow-sm">
        <i class="fas fa-info-circle me-2"></i>
        <strong>Regra de prioridade nas apurações:</strong>
        <?php if ($ehMedico): ?>
        Os valores definidos aqui (marcados como <em>Base Contábil: Contrato</em>) terão <strong>prioridade máxima</strong> sobre a tabela de exames ao calcular a apuração do médico.
        <?php else: ?>
        Os valores de venda definidos aqui terão <strong>prioridade máxima</strong> sobre a tabela de exames ao calcular a apuração do cliente.
        <?php endif; ?>
    </div>

</div>
<!-- FIM ABA SERVIÇOS/EXAMES -->

<!-- ============================================================ -->
<!-- ABA: APURAÇÃO (médico e cliente) -->
<!-- ============================================================ -->
<div id="tab-apuracao" class="tab-content-section <?php echo $activeTab !== 'apuracao' ? 'd-none' : ''; ?>">

    <!-- Filtros de apuração -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-3 px-4">
            <form method="GET" action="/contratos/edit/<?php echo $cId; ?>" class="row g-2 align-items-end">
                <input type="hidden" name="tab" value="apuracao">
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted mb-1"><i class="fas fa-calendar-alt me-1"></i>Período De</label>
                    <input type="date" name="filtro_periodo_inicio" class="form-control form-control-sm"
                           value="<?php echo htmlspecialchars($_GET['filtro_periodo_inicio'] ?? ''); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted mb-1"><i class="fas fa-calendar-alt me-1"></i>Período Até</label>
                    <input type="date" name="filtro_periodo_fim" class="form-control form-control-sm"
                           value="<?php echo htmlspecialchars($_GET['filtro_periodo_fim'] ?? ''); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted mb-1"><i class="fas fa-tag me-1"></i>Status</label>
                    <select name="filtro_status" class="form-select form-select-sm">
                        <option value="">Todos os status</option>
                        <option value="rascunho"   <?php echo ($_GET['filtro_status'] ?? '') === 'rascunho'   ? 'selected' : ''; ?>>Rascunho</option>
                        <option value="concluido"  <?php echo ($_GET['filtro_status'] ?? '') === 'concluido'  ? 'selected' : ''; ?>>Concluído</option>
                        <option value="faturado"   <?php echo ($_GET['filtro_status'] ?? '') === 'faturado'   ? 'selected' : ''; ?>>Faturado</option>
                        <option value="erro"       <?php echo ($_GET['filtro_status'] ?? '') === 'erro'       ? 'selected' : ''; ?>>Erro</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="fas fa-filter me-1"></i> Filtrar
                    </button>
                    <a href="/contratos/edit/<?php echo $cId; ?>?tab=apuracao" class="btn btn-outline-secondary btn-sm" title="Limpar filtros">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Lista de apurações -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold"><i class="fas fa-calculator me-2 text-primary"></i>Apurações do Contrato</h6>
            <form method="POST" action="/contratos/nova-apuracao" class="d-inline">
                <input type="hidden" name="contrato_id" value="<?php echo $cId; ?>">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus me-1"></i> Nova Apuração
                </button>
            </form>
        </div>
        <div class="card-body p-0">
            <?php
            // Aplicar filtros localmente
            $apFiltradas    = $apuracoes ?? [];
            $apFiltroStatus = $_GET['filtro_status'] ?? '';
            $apFiltroInicio = $_GET['filtro_periodo_inicio'] ?? '';
            $apFiltroFim    = $_GET['filtro_periodo_fim'] ?? '';
            if ($apFiltroStatus) {
                $apFiltradas = array_filter($apFiltradas, fn($a) => $a->status === $apFiltroStatus);
            }
            if ($apFiltroInicio) {
                $apFiltradas = array_filter($apFiltradas, fn($a) => !empty($a->periodo_inicio) && $a->periodo_inicio >= $apFiltroInicio);
            }
            if ($apFiltroFim) {
                $apFiltradas = array_filter($apFiltradas, fn($a) => !empty($a->periodo_fim) && $a->periodo_fim <= $apFiltroFim);
            }
            $temFiltro = $apFiltroStatus || $apFiltroInicio || $apFiltroFim;
            ?>
            <?php if (empty($apFiltradas)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-calculator fa-2x mb-2 d-block"></i>
                    <?php if (empty($apuracoes)): ?>
                        <p class="mb-0">Nenhuma apuração criada. Clique em "Nova Apuração" para iniciar.</p>
                    <?php else: ?>
                        <p class="mb-0">Nenhuma apuração encontrada com os filtros aplicados.</p>
                        <a href="/contratos/edit/<?php echo $cId; ?>?tab=apuracao" class="btn btn-sm btn-outline-secondary mt-2">
                            <i class="fas fa-times me-1"></i>Limpar Filtros
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Número</th>
                                <th>Período / Parte</th>
                                <th class="text-center">Total Exames</th>
                                <th class="text-center">Normal</th>
                                <th class="text-center">Urgência</th>
                                <th class="text-end">Valor Total</th>
                                <th>Status</th>
                                <th class="text-center pe-4">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($apFiltradas as $ap): ?>
                            <?php
                            $stClass = ['rascunho' => 'secondary', 'processando' => 'warning', 'concluido' => 'success', 'faturado' => 'primary', 'erro' => 'danger'];
                            $stLabel = ['rascunho' => 'Rascunho', 'processando' => 'Processando', 'concluido' => 'Concluído', 'faturado' => 'Faturado', 'erro' => 'Erro'];
                            ?>
                            <tr class="<?php echo (($_GET['apuracao_id'] ?? 0) == $ap->id) ? 'table-primary' : ''; ?>">
                                <td class="ps-4">
                                    <span class="badge bg-secondary font-monospace"><?php echo htmlspecialchars($ap->numero); ?></span>
                                </td>
                                <td>
                                    <small class="text-muted d-block">
                                        <?php
                                        if ($ap->periodo_inicio && $ap->periodo_fim) {
                                            echo date('d/m/Y', strtotime($ap->periodo_inicio)) . ' &rarr; ' . date('d/m/Y', strtotime($ap->periodo_fim));
                                        } else {
                                            echo '<span class="text-muted">&mdash;</span>';
                                        }
                                        ?>
                                    </small>
                                    <?php if (!empty($ap->cliente_nome)): ?>
                                    <small class="text-primary d-block mt-1" title="Cliente vinculado">
                                        <i class="fas fa-building me-1"></i><?php echo htmlspecialchars($ap->cliente_nome); ?>
                                    </small>
                                    <?php elseif (!empty($ap->medico_nome)): ?>
                                    <small class="text-secondary d-block mt-1" title="Médico vinculado">
                                        <i class="fas fa-user-md me-1"></i><?php echo htmlspecialchars($ap->medico_nome); ?>
                                    </small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center fw-bold"><?php echo number_format((int)$ap->total_exames); ?></td>
                                <td class="text-center"><span class="badge bg-success"><?php echo number_format((int)$ap->total_normal); ?></span></td>
                                <td class="text-center"><span class="badge bg-danger"><?php echo number_format((int)$ap->total_urgencia); ?></span></td>
                                <td class="text-end fw-semibold">R$&nbsp;<?php echo number_format((float)$ap->valor_total, 2, ',', '.'); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $stClass[$ap->status] ?? 'secondary'; ?>">
                                        <?php echo $stLabel[$ap->status] ?? ucfirst($ap->status); ?>
                                    </span>
                                </td>
                                <td class="text-center pe-4">
                                    <div class="btn-group btn-group-sm">
                                        <?php if (in_array($ap->status, ['rascunho', 'erro'])): ?>
                                        <button type="button" class="btn btn-outline-primary"
                                                onclick="abrirImportacao(<?php echo $ap->id; ?>, '<?php echo htmlspecialchars($ap->numero); ?>')"
                                                title="Importar arquivo">
                                            <i class="fas fa-file-import"></i>
                                        </button>
                        <a href="/faturamento/apuracao/delete/<?php echo $ap->id; ?>"
                           class="btn btn-outline-danger"
                           title="Excluir apuração"
                           onclick="return confirm('Excluir esta apuração?\n\nEsta ação não pode ser desfeita e removerá todos os itens importados.')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                        <?php endif; ?>
                                        <?php if (in_array($ap->status, ['concluido', 'faturado'])): ?>
                                        <a href="/faturamento/apuracao-prestador/visualizar/<?php echo $ap->id; ?>"
                                           class="btn btn-outline-<?php echo $ap->status === 'faturado' ? 'success' : 'info'; ?>"
                                           title="Visualizar apuração">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <!-- Modal de Importação -->
    <div class="modal fade" id="modalImportacao" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-file-import me-2"></i>Importar Arquivo de Apuração</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info border-0 small">
                        <i class="fas fa-info-circle me-1"></i>
                        Importe o relatório exportado do PACS/RIS. O sistema irá processar e calcular os valores conforme a tabela de exames cadastrada.
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Layout de Importação</label>
                            <select id="select-layout" class="form-select">
                                <option value="">Layout Padrão InLaudo</option>
                                <?php foreach ($layouts ?? [] as $lay): ?>
                                    <option value="<?php echo $lay->id; ?>" <?php echo $lay->ativo ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($lay->nome); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Arquivo (XLSX, XLS ou CSV)</label>
                            <input type="file" id="arquivo-apuracao" class="form-control"
                                   accept=".xlsx,.xls,.csv">
                        </div>
                        <?php if (($contrato->tipo_parte ?? '') === 'medico'): ?>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-building me-1 text-muted"></i>Cliente / Unidade <span class="text-danger">*</span>
                            </label>
                            <select id="select-cliente-import" class="form-select">
                                <option value="">-- Selecione o cliente/unidade --</option>
                                <?php foreach ($clientes ?? [] as $cl): ?>
                                    <option value="<?php echo $cl->id; ?>"><?php echo htmlspecialchars($cl->razao_social); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Selecione o cliente/unidade para o qual esta apuração será vinculada.</small>
                        </div>
                        <?php endif; ?>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-calendar-alt me-1 text-muted"></i>Período Inicial <span class="text-danger">*</span>
                            </label>
                            <input type="date" id="periodo-inicio-import" class="form-control">
                            <small class="text-muted">Data de início do período apurado</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-calendar-alt me-1 text-muted"></i>Período Final <span class="text-danger">*</span>
                            </label>
                            <input type="date" id="periodo-fim-import" class="form-control">
                            <small class="text-muted">Data de fim do período apurado</small>
                        </div>
                    </div>

                    <!-- Preview -->
                    <div id="preview-container" class="mt-3 d-none">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0 fw-bold">Pré-visualização (5 primeiras linhas)</h6>
                            <span id="total-linhas" class="badge bg-primary"></span>
                        </div>
                        <div class="table-responsive" style="max-height:200px;overflow-y:auto;">
                            <table class="table table-sm table-bordered small" id="preview-table">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th><th>Médico</th><th>Modalidade</th><th>Exame</th><th>Prioridade</th><th>Data Conclusão</th>
                                    </tr>
                                </thead>
                                <tbody id="preview-tbody"></tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Status de execução -->
                    <div id="exec-status" class="mt-3 d-none">
                        <div class="progress mb-2" style="height:8px">
                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" style="width:100%"></div>
                        </div>
                        <p class="text-muted small mb-0" id="exec-msg">Processando...</p>
                    </div>

                    <!-- Resultado -->
                    <div id="exec-resultado" class="mt-3 d-none">
                        <div class="alert alert-success border-0">
                            <h6 class="fw-bold mb-2"><i class="fas fa-check-circle me-1"></i>Apuração Concluída!</h6>
                            <div class="row g-2 text-center">
                                <div class="col-3">
                                    <div class="fw-bold fs-5" id="res-total">0</div>
                                    <small class="text-muted">Total Exames</small>
                                </div>
                                <div class="col-3">
                                    <div class="fw-bold fs-5 text-success" id="res-normal">0</div>
                                    <small class="text-muted">Normal</small>
                                </div>
                                <div class="col-3">
                                    <div class="fw-bold fs-5 text-danger" id="res-urgencia">0</div>
                                    <small class="text-muted">Urgência</small>
                                </div>
                                <div class="col-3">
                                    <div class="fw-bold fs-5 text-primary" id="res-valor">R$ 0,00</div>
                                    <small class="text-muted">Valor Total</small>
                                </div>
                            </div>
                            <div id="res-sematch" class="mt-2 d-none">
                                <small class="text-warning"><i class="fas fa-exclamation-triangle me-1"></i><span id="res-sematch-count">0</span> exame(s) sem correspondência na tabela.</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fechar</button>
                    <button type="button" id="btn-importar" class="btn btn-primary" onclick="importarArquivo()">
                        <i class="fas fa-upload me-1"></i> Importar
                    </button>
                    <button type="button" id="btn-executar" class="btn btn-success d-none" onclick="executarApuracao()">
                        <i class="fas fa-play me-1"></i> Executar Apuração
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
// Controle de abas
function switchTab(tab) {
    document.querySelectorAll('.tab-content-section').forEach(el => el.classList.add('d-none'));
    document.getElementById('tab-' + tab)?.classList.remove('d-none');
}

// Toggle médico/cliente
function toggleParte(tipo) {
    document.getElementById('campo-medico').style.display = tipo === 'medico' ? '' : 'none';
    document.getElementById('campo-cliente').style.display = tipo === 'cliente' ? '' : 'none';
    document.getElementById('painel-modalidades').style.display = tipo === 'medico' ? '' : 'none';
}

// Toggle data fim
function toggleVigencia(tipo) {
    document.getElementById('campo-data-fim').disabled = tipo === 'indeterminado';
    if (tipo === 'indeterminado') document.getElementById('campo-data-fim').value = '';
}

// Adicionar linha de modalidade
let modRowCount = <?php echo max(count($modalidades_contrato ?? []), 1); ?>;
function adicionarModalidade() {
    const idx = modRowCount++;
    const examesOpts = <?php
        $opts = '<option value="">Qualquer exame</option>';
        foreach ($exames ?? [] as $ex) {
            $opts .= '<option value="' . $ex->id . '">' . htmlspecialchars($ex->nome_exame) . '</option>';
        }
        echo json_encode($opts);
    ?>;
    const modOpts = ['TC','RM','RX','US','DX','CR','MG','NM','PT','RF','XA','OT']
        .map(m => `<option value="${m}">${m}</option>`).join('');

    const row = document.createElement('div');
    row.className = 'modalidade-row row g-2 mb-2';
    row.id = 'mod-row-' + idx;
    row.innerHTML = `
        <div class="col-5"><select name="modalidades[]" class="form-select form-select-sm"><option value="">Modalidade</option>${modOpts}</select></div>
        <div class="col-6"><select name="modalidade_exame_id[]" class="form-select form-select-sm">${examesOpts}</select></div>
        <div class="col-1"><button type="button" class="btn btn-sm btn-outline-danger" onclick="removerModalidade(${idx})"><i class="fas fa-times"></i></button></div>
    `;
    document.getElementById('lista-modalidades').appendChild(row);
}
function removerModalidade(idx) {
    document.getElementById('mod-row-' + idx)?.remove();
}

// Máscara de moeda
document.querySelectorAll('.money-mask').forEach(el => {
    el.addEventListener('input', function() {
        let v = this.value.replace(/\D/g, '');
        v = (parseInt(v) / 100).toFixed(2);
        this.value = isNaN(v) ? '0,00' : v.replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    });
});

// ============================================================
// APURAÇÃO: Importação e Execução
// ============================================================
let currentApuracaoId = null;

function abrirImportacao(apuracaoId, numero) {
    currentApuracaoId = apuracaoId;
    document.getElementById('preview-container')?.classList.add('d-none');
    document.getElementById('exec-status')?.classList.add('d-none');
    document.getElementById('exec-resultado')?.classList.add('d-none');
    document.getElementById('btn-executar')?.classList.add('d-none');
    document.getElementById('btn-importar')?.classList.remove('d-none');
    document.getElementById('arquivo-apuracao').value = '';
    // Sugerir período padrão: mês anterior
    const hoje = new Date();
    const primeiroDia = new Date(hoje.getFullYear(), hoje.getMonth() - 1, 1);
    const ultimoDia   = new Date(hoje.getFullYear(), hoje.getMonth(), 0);
    const fmt = d => d.toISOString().split('T')[0];
    document.getElementById('periodo-inicio-import').value = fmt(primeiroDia);
    document.getElementById('periodo-fim-import').value    = fmt(ultimoDia);
    document.querySelector('#modalImportacao .modal-title').innerHTML =
        '<i class="fas fa-file-import me-2"></i>Importar Apuração <span class="badge bg-secondary font-monospace ms-1">' + numero + '</span>';
    const modal = new bootstrap.Modal(document.getElementById('modalImportacao'));
    modal.show();
}

function importarArquivo() {
    const fileInput = document.getElementById('arquivo-apuracao');
    if (!fileInput.files.length) { alert('Selecione um arquivo.'); return; }

    const periodoInicio = document.getElementById('periodo-inicio-import').value;
    const periodoFim    = document.getElementById('periodo-fim-import').value;
    if (!periodoInicio || !periodoFim) {
        alert('Informe o Período Inicial e Final antes de importar.');
        return;
    }
    if (periodoFim < periodoInicio) {
        alert('O Período Final não pode ser anterior ao Período Inicial.');
        return;
    }

    // Validar seleção de cliente (apenas para contratos de médico/prestador)
    const clienteSelect = document.getElementById('select-cliente-import');
    if (clienteSelect && !clienteSelect.value) {
        alert('Selecione o cliente/unidade antes de importar.');
        return;
    }

    const formData = new FormData();
    formData.append('apuracao_id', currentApuracaoId);
    formData.append('layout_id', document.getElementById('select-layout').value);
    formData.append('arquivo_apuracao', fileInput.files[0]);
    formData.append('periodo_inicio', periodoInicio);
    formData.append('periodo_fim', periodoFim);
    if (clienteSelect) formData.append('cliente_id', clienteSelect.value);

    document.getElementById('btn-importar').disabled = true;
    document.getElementById('btn-importar').innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Importando...';

    fetch('/contratos/importar-apuracao', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            document.getElementById('btn-importar').disabled = false;
            document.getElementById('btn-importar').innerHTML = '<i class="fas fa-upload me-1"></i> Importar';

            if (data.success) {
                // Mostrar preview
                const tbody = document.getElementById('preview-tbody');
                tbody.innerHTML = '';
                (data.preview || []).forEach(row => {
                    tbody.innerHTML += `<tr>
                        <td>${row.linha_original || ''}</td>
                        <td>${row.medico || ''}</td>
                        <td><span class="badge bg-secondary">${row.modalidade || ''}</span></td>
                        <td>${row.study_description || ''}</td>
                        <td><span class="badge bg-${row.prioridade?.toLowerCase().includes('urgent') ? 'danger' : 'success'}">${row.prioridade || 'Normal'}</span></td>
                        <td>${row.data_conclusao || ''}</td>
                    </tr>`;
                });
                document.getElementById('total-linhas').textContent = data.total_linhas + ' linha(s)';
                document.getElementById('preview-container').classList.remove('d-none');
                document.getElementById('btn-executar').classList.remove('d-none');
            } else {
                alert('Erro: ' + (data.message || 'Falha na importação'));
            }
        })
        .catch(e => {
            document.getElementById('btn-importar').disabled = false;
            document.getElementById('btn-importar').innerHTML = '<i class="fas fa-upload me-1"></i> Importar';
            alert('Erro de conexão: ' + e.message);
        });
}

function executarApuracao() {
    document.getElementById('exec-status').classList.remove('d-none');
    document.getElementById('exec-msg').textContent = 'Processando exames e calculando valores...';
    document.getElementById('btn-executar').disabled = true;
    document.getElementById('btn-importar').classList.add('d-none');

    const formData = new FormData();
    formData.append('apuracao_id', currentApuracaoId);
    formData.append('layout_id', document.getElementById('select-layout').value);

    fetch('/contratos/executar-apuracao', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            document.getElementById('exec-status').classList.add('d-none');
            document.getElementById('btn-executar').disabled = false;

            if (data.success) {
                document.getElementById('res-total').textContent    = data.total_exames;
                document.getElementById('res-normal').textContent   = data.total_normal;
                document.getElementById('res-urgencia').textContent = data.total_urgencia;
                document.getElementById('res-valor').textContent    = data.valor_total;
                if (data.sem_match > 0) {
                    document.getElementById('res-sematch').classList.remove('d-none');
                    document.getElementById('res-sematch-count').textContent = data.sem_match;
                }
                document.getElementById('exec-resultado').classList.remove('d-none');
                document.getElementById('btn-executar').classList.add('d-none');
                // Recarregar a página após 3s para atualizar a lista
                setTimeout(() => location.reload(), 3000);
            } else {
                alert('Erro: ' + (data.message || 'Falha na execução'));
            }
        })
        .catch(e => {
            document.getElementById('exec-status').classList.add('d-none');
            alert('Erro de conexão: ' + e.message);
        });
}

// ============================================================
// SERVIÇOS/EXAMES DO CONTRATO
// ============================================================
const CONTRATO_ID   = <?php echo $cId; ?>;
const TIPO_PARTE    = '<?php echo $tipoParte ?? 'medico'; ?>';
const EH_MEDICO     = TIPO_PARTE === 'medico';

/**
 * Carrega os valores da tabela de exames ao selecionar um exame.
 */
function carregarValoresExame(exameId) {
    if (!exameId) return;
    const opt = document.querySelector('#ce-exame-select option[value="' + exameId + '"]');
    if (!opt) return;

    if (EH_MEDICO) {
        const rotina   = parseFloat(opt.dataset.rotina || 0).toFixed(2);
        const urgencia = parseFloat(opt.dataset.urgencia || 0).toFixed(2);
        const r = document.getElementById('ce-valor-rotina');
        const u = document.getElementById('ce-valor-urgencia');
        if (r) { r.value = rotina; document.getElementById('ce-hint-rotina').textContent = 'Tabela: R$ ' + rotina; }
        if (u) { u.value = urgencia; document.getElementById('ce-hint-urgencia').textContent = 'Tabela: R$ ' + urgencia; }
    } else {
        const vRotina   = parseFloat(opt.dataset.vendaRotina || 0).toFixed(2);
        const vUrgencia = parseFloat(opt.dataset.vendaUrgencia || 0).toFixed(2);
        const r = document.getElementById('ce-valor-venda-rotina');
        const u = document.getElementById('ce-valor-venda-urgencia');
        if (r) { r.value = vRotina; document.getElementById('ce-hint-venda-rotina').textContent = 'Tabela: R$ ' + vRotina; }
        if (u) { u.value = vUrgencia; document.getElementById('ce-hint-venda-urgencia').textContent = 'Tabela: R$ ' + vUrgencia; }
    }
}

/**
 * Salva (upsert) um exame no contrato via AJAX.
 */
function salvarExameContrato() {
    const select = document.getElementById('ce-exame-select');
    const exameId = select ? select.value : '';
    if (!exameId) {
        alert('Selecione um exame.');
        return;
    }

    const usaCustom = document.getElementById('ce-usa-custom')?.checked ? 1 : 0;
    const formData  = new FormData();
    formData.append('contrato_id',    CONTRATO_ID);
    formData.append('tabela_exame_id', exameId);
    formData.append('usa_valor_custom', usaCustom);

    // Converte valor para float.
    // Se contiver vírgula = formato BR (1.350,00): remove pontos de milhar, troca vírgula por ponto.
    // Se não contiver vírgula = campo type="number" com ponto decimal (9.50): usa parseFloat direto.
    function parseMoedaBR(val) {
        if (!val || String(val).trim() === '') return 0;
        const s = String(val).trim();
        if (s.includes(',')) {
            return parseFloat(s.replace(/\./g, '').replace(',', '.')) || 0;
        }
        return parseFloat(s) || 0;
    }

    if (EH_MEDICO) {
        formData.append('valor_rotina',         parseMoedaBR(document.getElementById('ce-valor-rotina')?.value));
        formData.append('valor_urgencia',       parseMoedaBR(document.getElementById('ce-valor-urgencia')?.value));
        formData.append('valor_venda_rotina',   0);
        formData.append('valor_venda_urgencia', 0);
    } else {
        formData.append('valor_rotina',         0);
        formData.append('valor_urgencia',       0);
        formData.append('valor_venda_rotina',   parseMoedaBR(document.getElementById('ce-valor-venda-rotina')?.value));
        formData.append('valor_venda_urgencia', parseMoedaBR(document.getElementById('ce-valor-venda-urgencia')?.value));
    }

    fetch('/contratos/exames/salvar', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                // Recarregar a aba de serviços
                window.location.href = '/contratos/edit/' + CONTRATO_ID + '?tab=servicos';
            } else {
                alert('Erro: ' + (data.message || 'Falha ao salvar exame.'));
            }
        })
        .catch(e => alert('Erro de conexão: ' + e.message));
}

/**
 * Remove um exame vinculado ao contrato.
 */
function removerExameContrato(tabelaExameId, nomeExame) {
    if (!confirm('Remover o exame "' + nomeExame + '" deste contrato?')) return;

    const formData = new FormData();
    formData.append('contrato_id',    CONTRATO_ID);
    formData.append('tabela_exame_id', tabelaExameId);

    fetch('/contratos/exames/remover', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const row = document.getElementById('ce-row-' + tabelaExameId);
                if (row) row.remove();
            } else {
                alert('Erro: ' + (data.message || 'Falha ao remover exame.'));
            }
        })
        .catch(e => alert('Erro de conexão: ' + e.message));
}
</script>
