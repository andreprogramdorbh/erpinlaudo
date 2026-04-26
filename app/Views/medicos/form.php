<?php

use App\Core\View;

$medico        = $medico ?? null;
$especialidades = $especialidades ?? [];
$medicoExames  = $medicoExames ?? [];
$tabelaExames  = $tabelaExames ?? [];
$medicoCrms    = $medicoCrms ?? [];
$isEdit        = ($formMode ?? 'create') === 'edit';
$medicoId      = (int) ($medico->id ?? 0);
$error         = $_GET['error'] ?? '';
$success       = $_GET['success'] ?? '';
$activeTab     = $_GET['tab'] ?? 'dados';

$errorMessages = [
    'missing_fields'      => 'Preencha todos os campos obrigatorios do medico.',
    'invalid_uf'          => 'Informe uma UF valida para o CRM.',
    'invalid_email'       => 'Informe um e-mail valido.',
    'invalid_especialidade' => 'Selecione uma especialidade valida.',
    'invalid_upload'      => 'Nao foi possivel processar o upload da assinatura.',
    'invalid_file_type'   => 'A assinatura deve ser PNG, JPG ou PDF.',
    'file_too_large'      => 'A assinatura digital deve ter no maximo 5 MB.',
    'db_failure'          => 'Nao foi possivel salvar o cadastro no momento.',
    'fatal'               => 'Ocorreu um erro inesperado ao salvar o medico.',
];
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h2 class="h4 mb-1 fw-bold text-dark"><?php echo $pageTitle; ?></h2>
        <p class="text-muted small mb-0">Cadastre dados basicos e profissionais do medico.</p>
    </div>
    <a href="/medicos" class="btn btn-light border">
        <i class="fas fa-arrow-left me-1"></i> Voltar
    </a>
</div>

<?php if ($success === 'created'): ?>
    <div class="alert alert-success border-0 shadow-sm"><i class="fas fa-check-circle me-2"></i>Medico cadastrado com sucesso.</div>
<?php elseif ($success === 'updated'): ?>
    <div class="alert alert-success border-0 shadow-sm"><i class="fas fa-check-circle me-2"></i>Medico atualizado com sucesso.</div>
<?php elseif ($success === 'exame_salvo'): ?>
    <div class="alert alert-success border-0 shadow-sm"><i class="fas fa-check-circle me-2"></i>Serviço/Exame vinculado com sucesso.</div>
<?php endif; ?>

<?php if (isset($errorMessages[$error])): ?>
    <div class="alert alert-danger border-0 shadow-sm"><i class="fas fa-exclamation-circle me-2"></i><?php echo $errorMessages[$error]; ?></div>
<?php endif; ?>

<?php if ($isEdit): ?>
<!-- ============================================================
     ABAS (apenas no modo edição)
     ============================================================ -->
<ul class="nav nav-tabs mb-4 border-bottom" id="medicoTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link <?php echo $activeTab !== 'servicos' ? 'active' : ''; ?>"
                id="tab-dados" data-bs-toggle="tab" data-bs-target="#pane-dados"
                type="button" role="tab">
            <i class="fas fa-user-md me-1"></i> Dados do Médico
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?php echo $activeTab === 'servicos' ? 'active' : ''; ?>"
                id="tab-servicos" data-bs-toggle="tab" data-bs-target="#pane-servicos"
                type="button" role="tab">
            <i class="fas fa-stethoscope me-1"></i> Serviços / Exames
            <?php if (!empty($medicoExames)): ?>
                <span class="badge bg-primary rounded-pill ms-1"><?php echo count($medicoExames); ?></span>
            <?php endif; ?>
        </button>
    </li>
</ul>

<div class="tab-content" id="medicoTabsContent">

<!-- ============================================================
     PANE: Dados do Médico
     ============================================================ -->
<div class="tab-pane fade <?php echo $activeTab !== 'servicos' ? 'show active' : ''; ?>" id="pane-dados" role="tabpanel">
<?php endif; ?>

<form method="POST" action="<?php echo htmlspecialchars($formAction); ?>" enctype="multipart/form-data">
    <?php echo View::csrfField(); ?>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-0 pb-0">
            <h3 class="h5 mb-1">Dados basicos</h3>
            <p class="text-muted small mb-0">Informacoes principais para o cadastro do medico.</p>
        </div>
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="nome" class="form-label fw-semibold">Nome</label>
                    <input type="text" class="form-control" id="nome" name="nome" required
                        value="<?php echo htmlspecialchars($medico->nome ?? ''); ?>">
                </div>
                <div class="col-md-3">
                    <label for="crm" class="form-label fw-semibold">CRM Principal</label>
                    <input type="text" class="form-control" id="crm" name="crm" required
                        value="<?php echo htmlspecialchars($medico->crm ?? ''); ?>">
                </div>
                <div class="col-md-3">
                    <label for="uf_crm" class="form-label fw-semibold">UF CRM Principal</label>
                    <?php
                    $ufCrmAtual = strtoupper(trim($medico->uf_crm ?? ''));
                    $ufsOptions = [
                        'AC'=>'AC — Acre','AL'=>'AL — Alagoas','AP'=>'AP — Amapá','AM'=>'AM — Amazonas',
                        'BA'=>'BA — Bahia','CE'=>'CE — Ceará','DF'=>'DF — Distrito Federal',
                        'ES'=>'ES — Espírito Santo','GO'=>'GO — Goiás','MA'=>'MA — Maranhão',
                        'MT'=>'MT — Mato Grosso','MS'=>'MS — Mato Grosso do Sul',
                        'MG'=>'MG — Minas Gerais','PA'=>'PA — Pará','PB'=>'PB — Paraíba',
                        'PR'=>'PR — Paraná','PE'=>'PE — Pernambuco','PI'=>'PI — Piauí',
                        'RJ'=>'RJ — Rio de Janeiro','RN'=>'RN — Rio Grande do Norte',
                        'RS'=>'RS — Rio Grande do Sul','RO'=>'RO — Rondônia',
                        'RR'=>'RR — Roraima','SC'=>'SC — Santa Catarina',
                        'SP'=>'SP — São Paulo','SE'=>'SE — Sergipe','TO'=>'TO — Tocantins',
                    ];
                    ?>
                    <select class="form-select" id="uf_crm" name="uf_crm" required>
                        <option value="">Selecione</option>
                        <?php foreach ($ufsOptions as $sigla => $label): ?>
                        <option value="<?php echo $sigla; ?>" <?php echo $ufCrmAtual === $sigla ? 'selected' : ''; ?>>
                            <?php echo $label; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="cpf" class="form-label fw-semibold">CPF</label>
                    <input type="text" class="form-control" id="cpf" name="cpf" required
                        value="<?php echo htmlspecialchars($medico->cpf ?? ''); ?>">
                </div>
                <div class="col-md-4">
                    <label for="email" class="form-label fw-semibold">Email</label>
                    <input type="email" class="form-control" id="email" name="email" required
                        value="<?php echo htmlspecialchars($medico->email ?? ''); ?>">
                </div>
                <div class="col-md-4">
                    <label for="telefone" class="form-label fw-semibold">Telefone</label>
                    <input type="text" class="form-control" id="telefone" name="telefone" required
                        value="<?php echo htmlspecialchars($medico->telefone ?? ''); ?>">
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================
         CARD: CRMs adicionais (multi-estado)
         ============================================================ -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-0 pb-0 d-flex align-items-center justify-content-between">
            <div>
                <h3 class="h5 mb-1">CRMs por Estado</h3>
                <p class="text-muted small mb-0">O médico pode ter CRM em mais de um estado. O CRM principal (acima) é usado como padrão.</p>
            </div>
            <button type="button" class="btn btn-sm btn-outline-primary" id="btn-add-crm">
                <i class="fas fa-plus me-1"></i> Adicionar CRM
            </button>
        </div>
        <div class="card-body p-4">
            <div id="lista-crms">
                <?php
                // Montar lista de CRMs: prioridade para medico_crms; fallback para o CRM principal
                $crmsParaExibir = !empty($medicoCrms) ? $medicoCrms : [];
                if (empty($crmsParaExibir) && !empty($medico->crm)) {
                    // Nenhum registro em medico_crms ainda — exibir o CRM principal como linha
                    $crmsParaExibir = [(object)[
                        'crm'       => $medico->crm,
                        'uf_crm'    => $medico->uf_crm,
                        'principal' => 1,
                    ]];
                }
                foreach ($crmsParaExibir as $idx => $mc): ?>
                <div class="row g-2 align-items-center mb-2 crm-row" id="crm-row-<?php echo $idx; ?>">
                    <div class="col-md-5">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                            <input type="text" class="form-control crm-input"
                                name="crms[<?php echo $idx; ?>][crm]"
                                placeholder="Número do CRM"
                                value="<?php echo htmlspecialchars($mc->crm ?? ''); ?>">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <?php $ufCrmLinha = strtoupper(trim($mc->uf_crm ?? '')); ?>
                        <select class="form-select uf-input"
                            name="crms[<?php echo $idx; ?>][uf_crm]">
                            <option value="">UF</option>
                            <?php foreach ($ufsOptions as $sigla => $label): ?>
                            <option value="<?php echo $sigla; ?>" <?php echo $ufCrmLinha === $sigla ? 'selected' : ''; ?>>
                                <?php echo $sigla; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <div class="form-check mt-1">
                            <input class="form-check-input principal-radio" type="radio"
                                name="crm_principal_idx" value="<?php echo $idx; ?>"
                                id="crm-principal-<?php echo $idx; ?>"
                                <?php echo ($mc->principal ?? 0) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="crm-principal-<?php echo $idx; ?>">
                                CRM Principal
                            </label>
                            <input type="hidden" name="crms[<?php echo $idx; ?>][principal]" class="principal-val" value="<?php echo ($mc->principal ?? 0) ? '1' : '0'; ?>">
                        </div>
                    </div>
                    <div class="col-md-2 text-end">
                        <?php if (($mc->principal ?? 0) != 1): ?>
                        <button type="button" class="btn btn-sm btn-outline-danger btn-remove-crm"
                            data-idx="<?php echo $idx; ?>">
                            <i class="fas fa-trash"></i>
                        </button>
                        <?php else: ?>
                        <span class="badge bg-primary"><i class="fas fa-star me-1"></i>Principal</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="text-muted small mt-2">
                <i class="fas fa-info-circle me-1"></i>
                Os CRMs adicionais são usados na importação de planilhas para vincular automaticamente o médico.
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-0 pb-0">
            <h3 class="h5 mb-1">Dados profissionais</h3>
            <p class="text-muted small mb-0">Especialidade, RQE e assinatura digital.</p>
        </div>
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="especialidade_id" class="form-label fw-semibold">Especialidade</label>
                    <select class="form-select" id="especialidade_id" name="especialidade_id" required>
                        <option value="">Selecione</option>
                        <?php foreach ($especialidades as $especialidade): ?>
                            <option
                                value="<?php echo (int) $especialidade->id; ?>"
                                <?php echo ((int) ($medico->especialidade_id ?? 0) === (int) $especialidade->id) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($especialidade->especialidade ?? ''); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="subespecialidade" class="form-label fw-semibold">Subespecialidade</label>
                    <input type="text" class="form-control" id="subespecialidade" name="subespecialidade"
                        value="<?php echo htmlspecialchars($medico->subespecialidade ?? ''); ?>">
                </div>
                <div class="col-md-4">
                    <label for="rqe" class="form-label fw-semibold">RQE</label>
                    <input type="text" class="form-control" id="rqe" name="rqe"
                        value="<?php echo htmlspecialchars($medico->rqe ?? ''); ?>">
                </div>
                <div class="col-md-4">
                    <label for="assinatura_digital" class="form-label fw-semibold">Assinatura digital</label>
                    <input type="file" class="form-control" id="assinatura_digital" name="assinatura_digital"
                        accept=".png,.jpg,.jpeg,.pdf,image/png,image/jpeg,application/pdf">
                    <div class="form-text">Formatos aceitos: PNG, JPG ou PDF.</div>
                </div>
                <div class="col-md-4">
                    <label for="status" class="form-label fw-semibold">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="ativo"   <?php echo ($medico->status ?? 'ativo') === 'ativo'   ? 'selected' : ''; ?>>Ativo</option>
                        <option value="inativo" <?php echo ($medico->status ?? '') === 'inativo' ? 'selected' : ''; ?>>Inativo</option>
                    </select>
                </div>
            </div>

            <?php if (!empty($medico->assinatura_digital)): ?>
                <div class="alert alert-light border mt-3 mb-0">
                    Arquivo atual: <strong><?php echo htmlspecialchars(basename((string) $medico->assinatura_digital)); ?></strong>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2">
        <a href="/medicos" class="btn btn-light border">Cancelar</a>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save me-1"></i>
            <?php echo $isEdit ? 'Salvar alteracoes' : 'Cadastrar medico'; ?>
        </button>
    </div>
</form>

<?php if ($isEdit): ?>
</div><!-- /pane-dados -->

<!-- ============================================================
     PANE: Serviços / Exames
     ============================================================ -->
<div class="tab-pane fade <?php echo $activeTab === 'servicos' ? 'show active' : ''; ?>" id="pane-servicos" role="tabpanel">

    <!-- Card: Vincular novo exame -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom d-flex align-items-center gap-2 py-3">
            <i class="fas fa-plus-circle text-primary"></i>
            <h5 class="mb-0 fw-semibold">Vincular Exame / Serviço</h5>
        </div>
        <div class="card-body p-4">
            <p class="text-muted small mb-3">
                Selecione um exame da tabela de preços. O sistema usará as TAGs DICOM vinculadas ao exame
                para identificar automaticamente os itens nas apurações deste médico.
            </p>

            <div class="row g-3 align-items-end" id="form-vincular-exame">
                <!-- Seletor de exame -->
                <div class="col-md-5">
                    <label class="form-label fw-semibold small text-muted">Exame da Tabela de Preços</label>
                    <select class="form-select" id="sel-tabela-exame">
                        <option value="">— Selecione o exame —</option>
                        <?php foreach ($tabelaExames as $te): ?>
                            <option value="<?php echo (int) $te->id; ?>"
                                    data-modalidade="<?php echo htmlspecialchars($te->modalidade ?? ''); ?>"
                                    data-nome="<?php echo htmlspecialchars($te->nome_exame ?? '', ENT_QUOTES); ?>"
                                    data-rotina="<?php echo number_format((float)($te->valor_rotina ?? $te->valor_padrao ?? 0), 2, '.', ''); ?>"
                                    data-urgencia="<?php echo number_format((float)($te->valor_urgencia ?? $te->valor_padrao ?? 0), 2, '.', ''); ?>"
                                    data-tags="<?php echo htmlspecialchars(implode(',', $te->tags_dicom ?? []), ENT_QUOTES); ?>">
                                [<?php echo htmlspecialchars($te->modalidade); ?>] <?php echo htmlspecialchars($te->nome_exame); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Preview do exame selecionado -->
                <div class="col-md-7" id="preview-exame" style="display:none;">
                    <div class="card border bg-light">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="badge bg-secondary" id="prev-modalidade"></span>
                                <strong id="prev-nome" class="text-dark"></strong>
                            </div>
                            <div class="mb-2">
                                <small class="text-muted fw-semibold">TAGs DICOM vinculadas:</small>
                                <div id="prev-tags" class="d-flex flex-wrap gap-1 mt-1"></div>
                            </div>
                            <div class="row g-2">
                                <div class="col-6">
                                    <small class="text-muted d-block">Valor Rotina (tabela)</small>
                                    <span class="text-success fw-semibold" id="prev-rotina">—</span>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block">Valor Urgência (tabela)</small>
                                    <span class="text-warning fw-semibold" id="prev-urgencia">—</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Opção de valor customizado -->
                <div class="col-12" id="bloco-custom" style="display:none;">
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" id="chk-custom" value="1">
                        <label class="form-check-label fw-semibold" for="chk-custom">
                            Usar valores específicos para este médico (override)
                        </label>
                    </div>
                    <div id="campos-custom" style="display:none;">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Valor Rotina (R$)</label>
                                <input type="text" class="form-control" id="inp-rotina" placeholder="0,00">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Valor Urgência (R$)</label>
                                <input type="text" class="form-control" id="inp-urgencia" placeholder="0,00">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Observações</label>
                                <input type="text" class="form-control" id="inp-obs" placeholder="Opcional">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <button type="button" class="btn btn-primary" id="btn-vincular-exame" disabled>
                        <i class="fas fa-link me-1"></i> Vincular Exame
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Card: Exames já vinculados -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between py-3">
            <div class="d-flex align-items-center gap-2">
                <i class="fas fa-list text-primary"></i>
                <h5 class="mb-0 fw-semibold">Exames Vinculados</h5>
            </div>
            <span class="badge bg-primary rounded-pill" id="badge-total-exames"><?php echo count($medicoExames); ?></span>
        </div>
        <div class="card-body p-0" id="tabela-exames-medico">
            <?php if (!empty($medicoExames)): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Exame</th>
                            <th>Tipo de Exame</th>
                            <th>TAGs DICOM</th>
                            <th class="text-end">Valor Rotina</th>
                            <th class="text-end">Valor Urgência</th>
                            <th class="text-center">Override</th>
                            <th class="text-center pe-4">Ações</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-exames">
                        <?php foreach ($medicoExames as $me): ?>
                        <tr id="row-me-<?php echo (int) $me->tabela_exame_id; ?>">
                            <td class="ps-4 fw-semibold"><?php echo htmlspecialchars($me->nome_exame ?? ''); ?></td>
                            <td>
                                <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($me->modalidade ?? ''); ?></span>
                            </td>
                            <td>
                                <?php if (!empty($me->tags_dicom)): ?>
                                    <div class="d-flex flex-wrap gap-1">
                                        <?php foreach ($me->tags_dicom as $tv): ?>
                                            <span class="badge bg-primary" style="font-size:.7rem">
                                                <i class="fas fa-tag me-1" style="font-size:.6rem"></i><?php echo htmlspecialchars($tv); ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted small">Nenhuma</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <?php if ($me->usa_valor_custom): ?>
                                    <span class="text-success fw-semibold">
                                        R$ <?php echo number_format((float) $me->valor_rotina, 2, ',', '.'); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-success">
                                        R$ <?php echo number_format((float) $me->tabela_valor_rotina, 2, ',', '.'); ?>
                                    </span>
                                    <br><small class="text-muted">(tabela)</small>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <?php if ($me->usa_valor_custom): ?>
                                    <span class="text-warning fw-semibold">
                                        R$ <?php echo number_format((float) $me->valor_urgencia, 2, ',', '.'); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-warning">
                                        R$ <?php echo number_format((float) $me->tabela_valor_urgencia, 2, ',', '.'); ?>
                                    </span>
                                    <br><small class="text-muted">(tabela)</small>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($me->usa_valor_custom): ?>
                                    <span class="badge bg-warning text-dark" title="Valor específico deste médico">
                                        <i class="fas fa-user-edit me-1"></i>Custom
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-light text-muted border">Tabela</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center pe-4">
                                <button type="button"
                                        class="btn btn-sm btn-outline-danger btn-remover-exame"
                                        data-id="<?php echo (int) $me->tabela_exame_id; ?>"
                                        data-nome="<?php echo htmlspecialchars($me->nome_exame ?? '', ENT_QUOTES); ?>"
                                        title="Remover vínculo">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="p-5 text-center text-muted" id="empty-state">
                <i class="fas fa-stethoscope fa-3x mb-3 opacity-25"></i>
                <p class="mb-0">Nenhum exame vinculado ainda.<br>Use o formulário acima para vincular exames da tabela de preços.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

</div><!-- /pane-servicos -->

</div><!-- /tab-content -->

<!-- ============================================================
     JavaScript da aba Serviços / Exames
     ============================================================ -->
<script>
(function () {
    const medicoId = <?php echo $medicoId; ?>;
    const csrfToken = '<?php echo View::csrfToken(); ?>';

    // ── Seletor de exame ──────────────────────────────────────
    const selExame   = document.getElementById('sel-tabela-exame');
    const preview    = document.getElementById('preview-exame');
    const blocoCustom = document.getElementById('bloco-custom');
    const btnVincular = document.getElementById('btn-vincular-exame');
    const chkCustom  = document.getElementById('chk-custom');
    const camposCustom = document.getElementById('campos-custom');

    selExame.addEventListener('change', function () {
        const opt = this.options[this.selectedIndex];
        if (!opt.value) {
            preview.style.display = 'none';
            blocoCustom.style.display = 'none';
            btnVincular.disabled = true;
            return;
        }

        const modalidade = opt.dataset.modalidade || '';
        const nome       = opt.dataset.nome || '';
        const rotina     = parseFloat(opt.dataset.rotina || '0');
        const urgencia   = parseFloat(opt.dataset.urgencia || '0');
        const tagsRaw    = opt.dataset.tags || '';
        const tags       = tagsRaw ? tagsRaw.split(',').filter(t => t.trim()) : [];

        document.getElementById('prev-modalidade').textContent = modalidade;
        document.getElementById('prev-nome').textContent = nome;
        document.getElementById('prev-rotina').textContent = 'R$ ' + rotina.toFixed(2).replace('.', ',');
        document.getElementById('prev-urgencia').textContent = 'R$ ' + urgencia.toFixed(2).replace('.', ',');

        const tagsDiv = document.getElementById('prev-tags');
        tagsDiv.innerHTML = '';
        if (tags.length > 0) {
            tags.forEach(t => {
                const badge = document.createElement('span');
                badge.className = 'badge bg-primary';
                badge.style.fontSize = '.7rem';
                badge.innerHTML = '<i class="fas fa-tag me-1" style="font-size:.6rem"></i>' + t.trim();
                tagsDiv.appendChild(badge);
            });
        } else {
            tagsDiv.innerHTML = '<span class="text-muted small">Nenhuma TAG DICOM cadastrada</span>';
        }

        // Preencher campos custom com valores da tabela
        document.getElementById('inp-rotina').value = rotina.toFixed(2).replace('.', ',');
        document.getElementById('inp-urgencia').value = urgencia.toFixed(2).replace('.', ',');

        preview.style.display = 'block';
        blocoCustom.style.display = 'block';
        btnVincular.disabled = false;
    });

    chkCustom.addEventListener('change', function () {
        camposCustom.style.display = this.checked ? 'block' : 'none';
    });

    // ── Vincular exame ────────────────────────────────────────
    btnVincular.addEventListener('click', function () {
        const exameId    = selExame.value;
        const usaCustom  = chkCustom.checked ? 1 : 0;
        const rotina     = document.getElementById('inp-rotina').value;
        const urgencia   = document.getElementById('inp-urgencia').value;
        const obs        = document.getElementById('inp-obs').value;

        if (!exameId) return;

        btnVincular.disabled = true;
        btnVincular.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Salvando...';

        const body = new URLSearchParams({
            _token: csrfToken,
            tabela_exame_id: exameId,
            usa_valor_custom: usaCustom,
            valor_rotina: rotina.replace(',', '.'),
            valor_urgencia: urgencia.replace(',', '.'),
            observacoes: obs,
        });

        fetch(`/medicos/${medicoId}/exames/save`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body.toString(),
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                // Recarregar a página na aba de serviços
                window.location.href = `/medicos/edit/${medicoId}?tab=servicos&success=exame_salvo`;
            } else {
                alert('Erro ao vincular exame: ' + (data.message || 'Tente novamente.'));
                btnVincular.disabled = false;
                btnVincular.innerHTML = '<i class="fas fa-link me-1"></i> Vincular Exame';
            }
        })
        .catch(() => {
            alert('Erro de comunicação. Tente novamente.');
            btnVincular.disabled = false;
            btnVincular.innerHTML = '<i class="fas fa-link me-1"></i> Vincular Exame';
        });
    });

    // ── Remover exame ─────────────────────────────────────────
    document.querySelectorAll('.btn-remover-exame').forEach(btn => {
        btn.addEventListener('click', function () {
            const exameId = this.dataset.id;
            const nome    = this.dataset.nome;

            if (!confirm(`Remover o vínculo com "${nome}"?\n\nIsso não afeta apurações já realizadas.`)) return;

            const body = new URLSearchParams({
                _token: csrfToken,
                tabela_exame_id: exameId,
            });

            fetch(`/medicos/${medicoId}/exames/delete`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body.toString(),
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const row = document.getElementById(`row-me-${exameId}`);
                    if (row) row.remove();

                    // Atualizar contador
                    const badge = document.getElementById('badge-total-exames');
                    const tbody = document.getElementById('tbody-exames');
                    const total = tbody ? tbody.querySelectorAll('tr').length : 0;
                    if (badge) badge.textContent = total;

                    // Mostrar empty state se não houver mais linhas
                    if (total === 0) {
                        const container = document.getElementById('tabela-exames-medico');
                        container.innerHTML = `
                            <div class="p-5 text-center text-muted" id="empty-state">
                                <i class="fas fa-stethoscope fa-3x mb-3 opacity-25"></i>
                                <p class="mb-0">Nenhum exame vinculado ainda.</p>
                            </div>`;
                    }
                } else {
                    alert('Erro ao remover: ' + (data.message || 'Tente novamente.'));
                }
            })
            .catch(() => alert('Erro de comunicação. Tente novamente.'));
        });
    }
    // ── Ativar tooltip Bootstrap (aguarda o Bootstrap estar carregado) ────────
    function initTooltips() {
        if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
            document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
                new bootstrap.Tooltip(el);
            });
        }
    }
    // Bootstrap é carregado no footer (após a view), então usar DOMContentLoaded
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTooltips);
    } else {
        // DOM já pronto mas Bootstrap pode ainda não estar — aguardar via setTimeout
        setTimeout(initTooltips, 0);
    }
})();
</script>
<!-- ============================================================
     SCRIPT: Gerenciamento de múltiplos CRMs
     ============================================================ -->
<script>
// Lista de UFs para o select dinâmico
const ufSelectOptions = [
    'AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS',
    'MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'
].map(uf => `<option value="${uf}">${uf}</option>`).join('');

(function() {
    const listaCrms  = document.getElementById('lista-crms');
    const btnAddCrm  = document.getElementById('btn-add-crm');
    if (!listaCrms || !btnAddCrm) return;

    // Sincronizar radio "CRM Principal" com o hidden input
    function syncPrincipalRadios() {
        const radios = listaCrms.querySelectorAll('.principal-radio');
        radios.forEach(radio => {
            const row = radio.closest('.crm-row');
            if (!row) return;
            const hiddenVal = row.querySelector('.principal-val');
            const badge     = row.querySelector('.badge.bg-primary');
            const btnRemove = row.querySelector('.btn-remove-crm');

            if (radio.checked) {
                if (hiddenVal) hiddenVal.value = '1';
                // Sincronizar campos crm/uf_crm principais do formulário
                const crmInput = row.querySelector('.crm-input');
                const ufInput  = row.querySelector('.uf-input');
                const crmPrincipal = document.getElementById('crm');
                const ufPrincipal  = document.getElementById('uf_crm');
                if (crmInput && crmPrincipal) crmPrincipal.value = crmInput.value;
                if (ufInput  && ufPrincipal)  ufPrincipal.value  = ufInput.value;
            } else {
                if (hiddenVal) hiddenVal.value = '0';
            }
        });
    }

    // Sincronizar CRM principal quando usuário digita/altera campo da linha marcada como principal
    listaCrms.addEventListener('input', function(e) {
        const row = e.target.closest('.crm-row');
        if (!row) return;
        const radio = row.querySelector('.principal-radio');
        if (!radio || !radio.checked) return;
        const crmPrincipal = document.getElementById('crm');
        if (e.target.classList.contains('crm-input') && crmPrincipal) {
            crmPrincipal.value = e.target.value;
        }
    });

    // Ao mudar o radio OU o select de UF, sincronizar
    listaCrms.addEventListener('change', function(e) {
        if (e.target.classList.contains('principal-radio')) {
            syncPrincipalRadios();
        }
        // Sincronizar select UF quando linha marcada como principal é alterada
        if (e.target.classList.contains('uf-input')) {
            const row = e.target.closest('.crm-row');
            if (!row) return;
            const radio = row.querySelector('.principal-radio');
            if (!radio || !radio.checked) return;
            const ufPrincipal = document.getElementById('uf_crm');
            if (ufPrincipal) ufPrincipal.value = e.target.value;
        }
    });

    // Adicionar nova linha de CRM
    btnAddCrm.addEventListener('click', function() {
        const rows = listaCrms.querySelectorAll('.crm-row');
        const newIdx = rows.length;
        const div = document.createElement('div');
        div.className = 'row g-2 align-items-center mb-2 crm-row';
        div.id = `crm-row-${newIdx}`;
        div.innerHTML = `
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                    <input type="text" class="form-control crm-input"
                        name="crms[${newIdx}][crm]" placeholder="Número do CRM">
                </div>
            </div>
            <div class="col-md-2">
                <select class="form-select uf-input" name="crms[${newIdx}][uf_crm]">
                    <option value="">UF</option>
                    ${ufSelectOptions}
                </select>
            </div>
            <div class="col-md-3">
                <div class="form-check mt-1">
                    <input class="form-check-input principal-radio" type="radio"
                        name="crm_principal_idx" value="${newIdx}"
                        id="crm-principal-${newIdx}">
                    <label class="form-check-label" for="crm-principal-${newIdx}">CRM Principal</label>
                    <input type="hidden" name="crms[${newIdx}][principal]" class="principal-val" value="0">
                </div>
            </div>
            <div class="col-md-2 text-end">
                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-crm" data-idx="${newIdx}">
                    <i class="fas fa-trash"></i>
                </button>
            </div>`;
        listaCrms.appendChild(div);
    });

    // Remover linha de CRM
    listaCrms.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-remove-crm');
        if (!btn) return;
        const row = btn.closest('.crm-row');
        if (row) row.remove();
        // Renumerar índices dos campos hidden
        listaCrms.querySelectorAll('.crm-row').forEach((r, i) => {
            r.querySelectorAll('[name]').forEach(el => {
                el.name = el.name.replace(/crms\[\d+\]/, `crms[${i}]`);
            });
            const radio = r.querySelector('.principal-radio');
            if (radio) {
                radio.value = i;
                radio.id    = `crm-principal-${i}`;
                const lbl = r.querySelector(`label[for^="crm-principal-"]`);
                if (lbl) lbl.setAttribute('for', `crm-principal-${i}`);
            }
        });
    });
})();
</script>

<?php endif; // $isEdit ?>
