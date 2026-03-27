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
    <?php if (($contrato->tipo_parte ?? '') === 'medico'): ?>
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
                                            <option value="<?php echo $ex->id; ?>"><?php echo htmlspecialchars($ex->nome); ?></option>
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
                                            <option value="<?php echo $ex->id; ?>" <?php echo ($mc->exame_id ?? 0) == $ex->id ? 'selected' : ''; ?>><?php echo htmlspecialchars($ex->nome); ?></option>
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
<!-- ABA: APURAÇÃO (apenas médico) -->
<!-- ============================================================ -->
<?php if (($contrato->tipo_parte ?? '') === 'medico'): ?>
<div id="tab-apuracao" class="tab-content-section <?php echo $activeTab !== 'apuracao' ? 'd-none' : ''; ?>">

    <!-- Criar nova apuração -->
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
            <?php if (empty($apuracoes)): ?>
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-calculator fa-2x mb-2"></i>
                    <p class="mb-0">Nenhuma apuração criada. Clique em "Nova Apuração" para iniciar.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Número</th>
                                <th>Período</th>
                                <th class="text-center">Total Exames</th>
                                <th class="text-center">Normal</th>
                                <th class="text-center">Urgência</th>
                                <th class="text-end">Valor Total</th>
                                <th>Status</th>
                                <th class="text-center pe-4">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($apuracoes as $ap): ?>
                            <tr class="<?php echo (($_GET['apuracao_id'] ?? 0) == $ap->id) ? 'table-primary' : ''; ?>">
                                <td class="ps-4">
                                    <span class="badge bg-secondary font-monospace"><?php echo htmlspecialchars($ap->numero); ?></span>
                                </td>
                                <td>
                                    <small class="text-muted">
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
                                <td class="text-end fw-semibold">R$ <?php echo number_format((float)$ap->valor_total, 2, ',', '.'); ?></td>
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
                                        <?php if (in_array($ap->status, ['rascunho', 'erro'])): ?>
                                        <button type="button" class="btn btn-outline-primary"
                                                onclick="abrirImportacao(<?php echo $ap->id; ?>, '<?php echo htmlspecialchars($ap->numero); ?>')"
                                                title="Importar arquivo">
                                            <i class="fas fa-file-import"></i>
                                        </button>
                                        <?php endif; ?>
                                        <?php if ($ap->status === 'concluido'): ?>
                                        <a href="/faturamento/apuracao-prestador/visualizar/<?php echo $ap->id; ?>"
                                           class="btn btn-outline-info" title="Visualizar">
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
            $opts .= '<option value="' . $ex->id . '">' . htmlspecialchars($ex->nome) . '</option>';
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
    const modal = new bootstrap.Modal(document.getElementById('modalImportacao'));
    modal.show();
}

function importarArquivo() {
    const fileInput = document.getElementById('arquivo-apuracao');
    if (!fileInput.files.length) { alert('Selecione um arquivo.'); return; }

    const formData = new FormData();
    formData.append('apuracao_id', currentApuracaoId);
    formData.append('layout_id', document.getElementById('select-layout').value);
    formData.append('arquivo_apuracao', fileInput.files[0]);

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
</script>
