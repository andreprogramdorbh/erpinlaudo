<?php

use App\Core\UI;
use App\Core\View;

UI::sectionHeader('Tabela de Exames / Serviços', 'Gerencie os exames, valores e configurações de precificação');

$success = $_GET['success'] ?? '';
$error   = $_GET['error'] ?? '';

if ($success === 'created') {
    echo '<div class="alert alert-success border-0 shadow-sm"><i class="fas fa-check-circle me-2"></i>Exame cadastrado com sucesso.</div>';
}

$erros = [
    'missing_fields'     => 'Preencha nome do exame, modalidade e valor padrão.',
    'invalid_modalidade' => 'Selecione uma modalidade válida.',
    'invalid_valor'      => 'Informe um valor padrão válido.',
    'db_failure'         => 'Não foi possível salvar o exame.',
    'fatal'              => 'Erro inesperado. Tente novamente.',
];
if (isset($erros[$error])) {
    echo '<div class="alert alert-danger border-0 shadow-sm"><i class="fas fa-exclamation-circle me-2"></i>' . $erros[$error] . '</div>';
}
?>

<!-- Formulário de Cadastro -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom d-flex align-items-center gap-2 py-3">
        <i class="fas fa-plus-circle text-primary"></i>
        <h5 class="mb-0 fw-semibold">Novo Exame / Serviço</h5>
    </div>
    <div class="card-body p-4">
        <form method="POST" action="/exames-tabela/store" class="row g-3 align-items-end">
            <?php echo View::csrfField(); ?>

            <div class="col-md-5">
                <label for="nome_exame" class="form-label fw-semibold small text-muted">Nome do Exame</label>
                <input type="text" class="form-control" id="nome_exame" name="nome_exame"
                       placeholder="Ex: Tomografia de Crânio" required>
            </div>

            <div class="col-md-3">
                <label for="modalidade" class="form-label fw-semibold small text-muted">Modalidade</label>
                <select class="form-select" id="modalidade" name="modalidade" required>
                    <option value="">Selecione</option>
                    <option value="TC">TC — Tomografia Computadorizada</option>
                    <option value="RM">RM — Ressonância Magnética</option>
                    <option value="RX">RX — Raio-X</option>
                    <option value="US">US — Ultrassonografia</option>
                    <option value="MG">MG — Mamografia</option>
                    <option value="PET">PET — PET-CT</option>
                    <option value="NM">NM — Medicina Nuclear</option>
                    <option value="OUT">OUT — Outros</option>
                </select>
            </div>

            <div class="col-md-2">
                <label for="valor_padrao" class="form-label fw-semibold small text-muted">Valor Padrão (R$)</label>
                <input type="text" class="form-control" id="valor_padrao" name="valor_padrao"
                       placeholder="0,00" required>
            </div>

            <div class="col-md-2 d-grid">
                <button type="submit" class="btn btn-primary fw-bold">
                    <i class="fas fa-save me-1"></i> Salvar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Filtros -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <form method="GET" action="/exames-tabela" class="row g-2 align-items-end">
            <div class="col-md-7">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" name="q" class="form-control border-start-0"
                           placeholder="Pesquisar por nome do exame..."
                           value="<?php echo htmlspecialchars($filtros['pesquisa'] ?? ''); ?>">
                </div>
            </div>
            <div class="col-md-3">
                <select name="modalidade" class="form-select">
                    <option value="">Todas as modalidades</option>
                    <?php foreach (['TC','RM','RX','US','MG','PET','NM','OUT'] as $m): ?>
                        <option value="<?php echo $m; ?>" <?php echo ($filtros['modalidade'] ?? '') === $m ? 'selected' : ''; ?>>
                            <?php echo $m; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-grid">
                <button type="submit" class="btn btn-outline-primary">
                    <i class="fas fa-filter me-1"></i> Filtrar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Tabela de Exames -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between py-3">
        <div class="d-flex align-items-center gap-2">
            <i class="fas fa-vial text-primary"></i>
            <h5 class="mb-0 fw-semibold">Exames Cadastrados</h5>
        </div>
        <span class="badge bg-primary rounded-pill"><?php echo count($exames ?? []); ?> registros</span>
    </div>
    <div class="card-body p-0">
        <?php if (!empty($exames ?? [])): ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Nome do Exame</th>
                        <th>Modalidade</th>
                        <th class="text-end">Valor Padrão</th>
                        <th class="text-end">Valor Rotina</th>
                        <th class="text-end">Valor Urgência</th>
                        <th class="text-end">Preço de Venda</th>
                        <th class="text-center pe-4">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($exames as $exame): ?>
                    <tr id="row-exame-<?php echo $exame->id; ?>">
                        <td class="ps-4">
                            <div class="fw-semibold"><?php echo htmlspecialchars($exame->nome_exame); ?></div>
                            <?php if (!empty($exame->nivel)): ?>
                                <small class="text-muted">Nível <?php echo (int)$exame->nivel; ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border fw-semibold">
                                <?php echo htmlspecialchars($exame->modalidade); ?>
                            </span>
                        </td>
                        <td class="text-end">
                            <span class="fw-semibold">R$ <?php echo number_format((float)($exame->valor_padrao ?? 0), 2, ',', '.'); ?></span>
                        </td>
                        <td class="text-end">
                            <?php if (!empty($exame->perc_rotina)): ?>
                                <span class="text-success fw-semibold">
                                    R$ <?php echo number_format((float)($exame->valor_rotina ?? 0), 2, ',', '.'); ?>
                                </span>
                                <br><small class="text-muted">+<?php echo number_format((float)$exame->perc_rotina, 1); ?>%</small>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <?php if (!empty($exame->perc_urgencia)): ?>
                                <span class="text-warning fw-semibold">
                                    R$ <?php echo number_format((float)($exame->valor_urgencia ?? 0), 2, ',', '.'); ?>
                                </span>
                                <br><small class="text-muted">+<?php echo number_format((float)$exame->perc_urgencia, 1); ?>%</small>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <?php if (!empty($exame->preco_venda)): ?>
                                <span class="text-primary fw-bold">
                                    R$ <?php echo number_format((float)($exame->preco_venda ?? 0), 2, ',', '.'); ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center pe-4">
                            <div class="btn-group btn-group-sm" role="group">
                                <!-- Editar -->
                                <button type="button"
                                    class="btn btn-outline-primary btn-editar-exame"
                                    title="Editar"
                                    data-id="<?php echo $exame->id; ?>"
                                    data-nome="<?php echo htmlspecialchars($exame->nome_exame, ENT_QUOTES); ?>"
                                    data-modalidade="<?php echo htmlspecialchars($exame->modalidade, ENT_QUOTES); ?>"
                                    data-valor="<?php echo number_format((float)$exame->valor_padrao, 2, ',', '.'); ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <!-- Configuração -->
                                <button type="button"
                                    class="btn btn-outline-secondary btn-config-exame"
                                    title="Configuração"
                                    data-id="<?php echo $exame->id; ?>">
                                    <i class="fas fa-cog"></i>
                                </button>
                                <!-- Excluir -->
                                <button type="button"
                                    class="btn btn-outline-danger btn-excluir-exame"
                                    title="Excluir"
                                    data-id="<?php echo $exame->id; ?>"
                                    data-nome="<?php echo htmlspecialchars($exame->nome_exame, ENT_QUOTES); ?>">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="text-center py-5">
            <i class="fas fa-vial fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">Nenhum exame cadastrado</h5>
            <p class="text-muted small">Utilize o formulário acima para cadastrar o primeiro exame.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- =====================================================
     MODAL: Editar Exame (dados básicos)
====================================================== -->
<div class="modal fade" id="modalEditarExame" tabindex="-1" aria-labelledby="modalEditarExameLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-semibold" id="modalEditarExameLabel">
                    <i class="fas fa-edit me-2 text-primary"></i>Editar Exame
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="edit_exame_id">
                <div class="mb-3">
                    <label class="form-label fw-semibold small text-muted">Nome do Exame</label>
                    <input type="text" class="form-control" id="edit_nome_exame" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold small text-muted">Modalidade</label>
                    <select class="form-select" id="edit_modalidade">
                        <option value="TC">TC — Tomografia Computadorizada</option>
                        <option value="RM">RM — Ressonância Magnética</option>
                        <option value="RX">RX — Raio-X</option>
                        <option value="US">US — Ultrassonografia</option>
                        <option value="MG">MG — Mamografia</option>
                        <option value="PET">PET — PET-CT</option>
                        <option value="NM">NM — Medicina Nuclear</option>
                        <option value="OUT">OUT — Outros</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold small text-muted">Valor Padrão (R$)</label>
                    <input type="text" class="form-control" id="edit_valor_padrao" placeholder="0,00">
                </div>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary fw-bold" id="btnSalvarEdicao">
                    <i class="fas fa-save me-1"></i> Salvar Alterações
                </button>
            </div>
        </div>
    </div>
</div>

<!-- =====================================================
     MODAL: Configuração (3 abas)
====================================================== -->
<div class="modal fade" id="modalConfigExame" tabindex="-1" aria-labelledby="modalConfigExameLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-semibold" id="modalConfigExameLabel">
                    <i class="fas fa-cog me-2 text-secondary"></i>Configuração do Exame
                    <small class="text-muted ms-2 fw-normal" id="configExameNome"></small>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <input type="hidden" id="config_exame_id">

                <!-- Abas -->
                <ul class="nav nav-tabs nav-fill border-bottom px-3 pt-3" id="configTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-semibold" id="tab-precos-btn"
                                data-bs-toggle="tab" data-bs-target="#tab-precos" type="button" role="tab">
                            <i class="fas fa-tags me-1"></i> Preços
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-semibold" id="tab-secao-btn"
                                data-bs-toggle="tab" data-bs-target="#tab-secao" type="button" role="tab">
                            <i class="fas fa-calculator me-1"></i> Seção
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-semibold" id="tab-dicom-btn"
                                data-bs-toggle="tab" data-bs-target="#tab-dicom" type="button" role="tab">
                            <i class="fas fa-tag me-1"></i> TAG DICOM
                        </button>
                    </li>
                </ul>

                <div class="tab-content p-4" id="configTabsContent">

                    <!-- ==================== ABA PREÇOS ==================== -->
                    <div class="tab-pane fade show active" id="tab-precos" role="tabpanel">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold small text-muted">Nível</label>
                                <input type="number" class="form-control" id="preco_nivel" min="1" max="10"
                                       placeholder="Ex: 1, 2, 3...">
                                <div class="form-text">Nível do exame para regras de laudo.</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold small text-muted">% Rotina</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="preco_perc_rotina" placeholder="0,00">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold small text-muted">% Urgência</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="preco_perc_urgencia" placeholder="0,00">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                        </div>

                        <!-- Preview de cálculo em tempo real -->
                        <div class="card bg-light border-0 mt-4">
                            <div class="card-body py-3">
                                <div class="row text-center">
                                    <div class="col-4 border-end">
                                        <div class="small text-muted mb-1">Valor Padrão</div>
                                        <div class="fw-bold fs-5" id="preview_valor_padrao">R$ 0,00</div>
                                    </div>
                                    <div class="col-4 border-end">
                                        <div class="small text-muted mb-1">
                                            <i class="fas fa-circle text-success me-1" style="font-size:8px"></i>Valor Rotina
                                        </div>
                                        <div class="fw-bold fs-5 text-success" id="preview_valor_rotina">R$ 0,00</div>
                                    </div>
                                    <div class="col-4">
                                        <div class="small text-muted mb-1">
                                            <i class="fas fa-circle text-warning me-1" style="font-size:8px"></i>Valor Urgência
                                        </div>
                                        <div class="fw-bold fs-5 text-warning" id="preview_valor_urgencia">R$ 0,00</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-3">
                            <button type="button" class="btn btn-primary fw-bold" id="btnSalvarPrecos">
                                <i class="fas fa-save me-1"></i> Salvar Preços
                            </button>
                        </div>
                    </div>

                    <!-- ==================== ABA SEÇÃO ==================== -->
                    <div class="tab-pane fade" id="tab-secao" role="tabpanel">

                        <!-- Encargos e Impostos -->
                        <h6 class="fw-bold text-uppercase text-muted small mb-3 border-bottom pb-2">
                            <i class="fas fa-file-invoice-dollar me-1"></i> Encargos e Impostos
                        </h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold text-muted">ICMS (%)</label>
                                <div class="input-group input-group-sm">
                                    <input type="text" class="form-control secao-input" id="sec_icms" placeholder="0,00">
                                    <span class="input-group-text">%</span>
                                </div>
                                <div class="form-text">Serv. Teleradiologia: isento em alguns estados</div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold text-muted">IPI (%)</label>
                                <div class="input-group input-group-sm">
                                    <input type="text" class="form-control secao-input" id="sec_ipi" placeholder="0,00">
                                    <span class="input-group-text">%</span>
                                </div>
                                <div class="form-text">Geralmente isento para serviços</div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold text-muted">PIS/COFINS (%)</label>
                                <div class="input-group input-group-sm">
                                    <input type="text" class="form-control secao-input" id="sec_pis_cofins" placeholder="0,00">
                                    <span class="input-group-text">%</span>
                                </div>
                                <div class="form-text">Lucro Presumido: ~3,65%</div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold text-muted">Simples Nacional (%)</label>
                                <div class="input-group input-group-sm">
                                    <input type="text" class="form-control secao-input" id="sec_simples" placeholder="0,00">
                                    <span class="input-group-text">%</span>
                                </div>
                                <div class="form-text">Anexo III: 6% a 33%</div>
                            </div>
                        </div>

                        <!-- Custos Operacionais -->
                        <h6 class="fw-bold text-uppercase text-muted small mb-3 border-bottom pb-2">
                            <i class="fas fa-cogs me-1"></i> Custos Operacionais
                        </h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold text-muted">Comissão (%)</label>
                                <div class="input-group input-group-sm">
                                    <input type="text" class="form-control secao-input" id="sec_comissao" placeholder="0,00">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold text-muted">Mão de Obra Direta (%)</label>
                                <div class="input-group input-group-sm">
                                    <input type="text" class="form-control secao-input" id="sec_mo_direta" placeholder="0,00">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold text-muted">Mão de Obra Indireta (%)</label>
                                <div class="input-group input-group-sm">
                                    <input type="text" class="form-control secao-input" id="sec_mo_indireta" placeholder="0,00">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold text-muted">Margem de Lucro (%)</label>
                                <div class="input-group input-group-sm">
                                    <input type="text" class="form-control secao-input" id="sec_margem" placeholder="0,00">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                        </div>

                        <!-- Resumo de Preços -->
                        <div class="card border-primary border-opacity-25 bg-primary bg-opacity-10 mt-2">
                            <div class="card-body py-3">
                                <div class="row text-center g-2">
                                    <div class="col-md-3 border-end">
                                        <div class="small text-muted mb-1">Valor Padrão</div>
                                        <div class="fw-bold" id="sec_preview_padrao">R$ 0,00</div>
                                    </div>
                                    <div class="col-md-3 border-end">
                                        <div class="small text-muted mb-1">Preço de Custo</div>
                                        <div class="fw-bold text-danger" id="sec_preview_custo">R$ 0,00</div>
                                        <div class="small text-muted" id="sec_preview_custo_label"></div>
                                    </div>
                                    <div class="col-md-3 border-end">
                                        <div class="small text-muted mb-1">+ Margem de Lucro</div>
                                        <div class="fw-bold text-primary fs-5" id="sec_preview_venda">R$ 0,00</div>
                                        <div class="small text-muted">Preço de Venda</div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="small text-muted mb-1">Rotina / Urgência</div>
                                        <div class="fw-semibold text-success small" id="sec_preview_rotina">R$ 0,00</div>
                                        <div class="fw-semibold text-warning small" id="sec_preview_urgencia">R$ 0,00</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-3">
                            <button type="button" class="btn btn-primary fw-bold" id="btnSalvarSecao">
                                <i class="fas fa-save me-1"></i> Salvar Seção
                            </button>
                        </div>
                    </div>

                    <!-- ==================== ABA TAG DICOM ==================== -->
                    <div class="tab-pane fade" id="tab-dicom" role="tabpanel">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div>
                                <h6 class="fw-bold mb-0">Tags DICOM</h6>
                                <small class="text-muted">Defina as tags DICOM para identificação automática deste exame no PACS.</small>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddTag">
                                <i class="fas fa-plus me-1"></i> Adicionar Tag
                            </button>
                        </div>

                        <!-- Sugestões rápidas -->
                        <div class="mb-3">
                            <small class="text-muted fw-semibold">Tags comuns:</small>
                            <div class="d-flex flex-wrap gap-1 mt-1">
                                <?php
                                $tagsComuns = [
                                    'Modality' => 'CT',
                                    'StudyDescription' => '',
                                    'SeriesDescription' => '',
                                    'BodyPartExamined' => '',
                                    'ProtocolName' => '',
                                    'RequestedProcedureDescription' => '',
                                    'ScheduledProcedureStepDescription' => '',
                                ];
                                foreach ($tagsComuns as $tag => $val): ?>
                                    <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2 btn-tag-sugestao"
                                            style="font-size:11px"
                                            data-tag="<?php echo $tag; ?>"
                                            data-valor="<?php echo $val; ?>">
                                        <?php echo $tag; ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div id="dicom-tags-container">
                            <!-- Linhas de tags serão inseridas aqui via JS -->
                        </div>

                        <div class="d-flex justify-content-end mt-3">
                            <button type="button" class="btn btn-primary fw-bold" id="btnSalvarTags">
                                <i class="fas fa-save me-1"></i> Salvar Tags
                            </button>
                        </div>
                    </div>

                </div><!-- /tab-content -->
            </div><!-- /modal-body -->
        </div>
    </div>
</div>

<?php
// Incluir o JavaScript do módulo
echo '<script src="/assets/js/exames-tabela.js?v=' . time() . '"></script>';
?>
