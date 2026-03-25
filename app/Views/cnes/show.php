<?php
use App\Core\UI;

// ─── Mapeamento de tipos de unidade ──────────────────────────────────────────
$tiposUnidade = [
    '01' => 'Posto de Saúde',
    '02' => 'Centro de Saúde/Unidade Básica',
    '04' => 'Policlínica',
    '05' => 'Hospital Geral',
    '06' => 'Hospital Especializado',
    '07' => 'Unidade Mista',
    '15' => 'Unidade de Apoio Diagnose e Terapia (SADT Isolado)',
    '20' => 'Pronto-Socorro Geral',
    '21' => 'Pronto-Socorro Especializado',
    '22' => 'Consultório Isolado',
    '32' => 'Clínica/Centro de Especialidade',
    '36' => 'Clínica de Reabilitação',
    '39' => 'Serviço de Atenção Domiciliar Isolado (Home Care)',
    '40' => 'Unidade de Saúde da Família',
    '42' => 'Unidade Móvel Terrestre',
    '43' => 'Unidade Móvel de Nível Pré-Hospitalar na Área de Urgência',
    '50' => 'Unidade de Vigilância em Saúde',
    '60' => 'Cooperativa ou Empresa de Cessão de Trabalhadores na Saúde',
    '61' => 'Centro de Parto Normal – Isolado',
    '62' => 'Hospital/Dia – Isolado',
    '64' => 'Central de Regulação de Serviços de Saúde',
    '65' => 'Pronto Atendimento',
    '67' => 'Laboratório Central de Saúde Pública – LACEN',
    '68' => 'Central de Gestão em Saúde',
    '69' => 'Centro de Atenção Hemoterapia e/ou Hematológica',
    '70' => 'Centro de Atenção Psicossocial',
    '71' => 'Centro de Apoio à Saúde da Família',
    '72' => 'Unidade de Atenção à Saúde Indígena',
    '73' => 'Pronto-Socorro de Trauma',
    '74' => 'Centro de Prevenção e Tratamento de Toxicômanos',
    '75' => 'Centro de Referência em Saúde do Trabalhador',
    '76' => 'Central de Regulação Médica das Urgências',
    '77' => 'Serviço de Atenção em Regime Residencial',
    '78' => 'Unidade de Atenção em Regime Residencial',
    '79' => 'Oficina Ortopédica',
    '80' => 'Laboratório de Saúde Pública',
    '81' => 'Laboratório Clínico',
    '82' => 'Serviço de Diagnóstico por Imagem',
    '83' => 'Serviço de Diagnóstico por Métodos Gráficos Dinâmicos',
    '84' => 'Serviço de Radioterapia',
    '85' => 'Serviço de Medicina Nuclear',
    '86' => 'Serviço de Hemoterapia',
    '87' => 'Banco de Células e Tecidos Germinativos',
    '88' => 'Serviço de Verificação de Óbito',
];

$tipoUnidadeDesc = $tiposUnidade[$estab->tp_unidade ?? ''] ?? ('Tipo ' . ($estab->tp_unidade ?? '—'));

// ─── Mapeamento de gestão ─────────────────────────────────────────────────────
$gestoes = ['M' => 'Municipal', 'E' => 'Estadual', 'D' => 'Dupla', 'S' => 'Sem Gestão'];
$gestaoDesc = $gestoes[$estab->tp_gestao ?? ''] ?? ($estab->tp_gestao ?? '—');

$actions = [];
if (!$clienteVinculado) {
    $actions[] = [
        'text'  => 'Importar como Cliente',
        'link'  => '#',
        'icon'  => 'fas fa-file-import',
        'class' => 'btn-success btn-importar-header',
    ];
}
UI::sectionHeader(
    $estab->no_fantasia ?: $estab->no_razao_social,
    'CNES: ' . $estab->co_cnes . ' — ' . $tipoUnidadeDesc,
    $actions
);
?>

<?php if ($clienteVinculado): ?>
<div class="alert alert-success d-flex align-items-center mb-4 border-0 shadow-sm">
    <i class="fas fa-check-circle me-2 fs-5"></i>
    <div>
        Este estabelecimento já foi importado como cliente:
        <a href="/clientes/<?php echo (int)$clienteVinculado->id; ?>" class="alert-link fw-bold ms-1">
            <?php echo htmlspecialchars($clienteVinculado->razao_social); ?> #<?php echo (int)$clienteVinculado->id; ?>
        </a>
    </div>
</div>
<?php endif; ?>

<!-- Abas de navegação -->
<ul class="nav nav-tabs border-bottom mb-4" id="cnesTabs">
    <li class="nav-item">
        <a class="nav-link <?php echo $aba === 'dados' ? 'active fw-semibold' : ''; ?>"
           href="/cnes/<?php echo urlencode($estab->co_cnes); ?>?aba=dados">
            <i class="fas fa-building me-1"></i> Dados do Estabelecimento
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo $aba === 'equipamentos' ? 'active fw-semibold' : ''; ?>"
           href="/cnes/<?php echo urlencode($estab->co_cnes); ?>?aba=equipamentos">
            <i class="fas fa-x-ray me-1"></i> Equipamentos
            <?php if ($totalEquip > 0): ?>
            <span class="badge bg-primary ms-1"><?php echo $totalEquip; ?></span>
            <?php endif; ?>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo $aba === 'profissionais' ? 'active fw-semibold' : ''; ?>"
           href="/cnes/<?php echo urlencode($estab->co_cnes); ?>?aba=profissionais">
            <i class="fas fa-user-md me-1"></i> Profissionais
            <?php if ($totalProf > 0): ?>
            <span class="badge bg-primary ms-1"><?php echo number_format($totalProf, 0, ',', '.'); ?></span>
            <?php endif; ?>
        </a>
    </li>
</ul>

<!-- ═══════════════════════════════════════════════════════════════════════════
     ABA: DADOS DO ESTABELECIMENTO
     ═══════════════════════════════════════════════════════════════════════════ -->
<?php if ($aba === 'dados'): ?>
<div class="row g-4">
    <!-- Identificação -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold"><i class="fas fa-id-card me-2 text-primary"></i>Identificação</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tbody>
                        <tr>
                            <td class="text-muted small fw-semibold" style="width:40%">CNES</td>
                            <td><span class="badge bg-primary"><?php echo htmlspecialchars($estab->co_cnes); ?></span></td>
                        </tr>
                        <tr>
                            <td class="text-muted small fw-semibold">Código Unidade</td>
                            <td class="small"><?php echo htmlspecialchars($estab->co_unidade); ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted small fw-semibold">Razão Social</td>
                            <td class="fw-semibold"><?php echo htmlspecialchars($estab->no_razao_social); ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted small fw-semibold">Nome Fantasia</td>
                            <td><?php echo htmlspecialchars($estab->no_fantasia ?? '—'); ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted small fw-semibold">CNPJ</td>
                            <td><?php echo htmlspecialchars($estab->nu_cnpj ?? '—'); ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted small fw-semibold">CNPJ Mantenedora</td>
                            <td class="small"><?php echo htmlspecialchars($estab->nu_cnpj_mantenedora ?? '—'); ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted small fw-semibold">Tipo de Unidade</td>
                            <td><?php echo htmlspecialchars($tipoUnidadeDesc); ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted small fw-semibold">Gestão</td>
                            <td><?php echo htmlspecialchars($gestaoDesc); ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted small fw-semibold">Natureza Jurídica</td>
                            <td class="small"><?php echo htmlspecialchars($estab->co_natureza_jur ?? '—'); ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted small fw-semibold">Atualização CNES</td>
                            <td class="small"><?php echo htmlspecialchars($estab->dt_atualizacao ?? '—'); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Endereço e Contato -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold"><i class="fas fa-map-marker-alt me-2 text-danger"></i>Endereço</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tbody>
                        <tr>
                            <td class="text-muted small fw-semibold" style="width:40%">Logradouro</td>
                            <td><?php echo htmlspecialchars($estab->no_logradouro ?? '—'); ?>, <?php echo htmlspecialchars($estab->nu_endereco ?? ''); ?></td>
                        </tr>
                        <?php if ($estab->no_complemento): ?>
                        <tr>
                            <td class="text-muted small fw-semibold">Complemento</td>
                            <td><?php echo htmlspecialchars($estab->no_complemento); ?></td>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <td class="text-muted small fw-semibold">Bairro</td>
                            <td><?php echo htmlspecialchars($estab->no_bairro ?? '—'); ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted small fw-semibold">CEP</td>
                            <td><?php echo htmlspecialchars($estab->co_cep ?? '—'); ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted small fw-semibold">UF</td>
                            <td><strong><?php echo htmlspecialchars($estab->co_estado_gestor ?? '—'); ?></strong></td>
                        </tr>
                        <tr>
                            <td class="text-muted small fw-semibold">Cód. Município</td>
                            <td class="small"><?php echo htmlspecialchars($estab->co_municipio_gestor ?? '—'); ?></td>
                        </tr>
                        <?php if ($estab->nu_latitude && $estab->nu_longitude): ?>
                        <tr>
                            <td class="text-muted small fw-semibold">Coordenadas</td>
                            <td class="small">
                                <a href="https://maps.google.com/?q=<?php echo urlencode($estab->nu_latitude . ',' . $estab->nu_longitude); ?>"
                                   target="_blank" class="text-primary">
                                    <?php echo htmlspecialchars($estab->nu_latitude); ?>, <?php echo htmlspecialchars($estab->nu_longitude); ?>
                                    <i class="fas fa-external-link-alt ms-1 small"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold"><i class="fas fa-phone me-2 text-success"></i>Contato</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tbody>
                        <tr>
                            <td class="text-muted small fw-semibold" style="width:40%">Telefone</td>
                            <td><?php echo htmlspecialchars($estab->nu_telefone ?? '—'); ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted small fw-semibold">Fax</td>
                            <td><?php echo htmlspecialchars($estab->nu_fax ?? '—'); ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted small fw-semibold">E-mail</td>
                            <td>
                                <?php if ($estab->no_email): ?>
                                <a href="mailto:<?php echo htmlspecialchars($estab->no_email); ?>">
                                    <?php echo htmlspecialchars($estab->no_email); ?>
                                </a>
                                <?php else: ?>—<?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted small fw-semibold">Website</td>
                            <td>
                                <?php if ($estab->no_url): ?>
                                <a href="<?php echo htmlspecialchars($estab->no_url); ?>" target="_blank">
                                    <?php echo htmlspecialchars($estab->no_url); ?>
                                    <i class="fas fa-external-link-alt ms-1 small"></i>
                                </a>
                                <?php else: ?>—<?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted small fw-semibold">Internet</td>
                            <td><?php echo $estab->st_conexao_internet === 'S' ? '<span class="badge bg-success-subtle text-success">Sim</span>' : '<span class="badge bg-secondary-subtle text-muted">Não</span>'; ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Diretor Clínico -->
    <?php if ($estab->nu_cpf_diretor || $estab->reg_diretor): ?>
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold"><i class="fas fa-user-tie me-2 text-warning"></i>Diretor Clínico</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <small class="text-muted fw-semibold d-block">CPF</small>
                        <span><?php echo htmlspecialchars($estab->nu_cpf_diretor ?? '—'); ?></span>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted fw-semibold d-block">Registro</small>
                        <span><?php echo htmlspecialchars($estab->reg_diretor ?? '—'); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- ═══════════════════════════════════════════════════════════════════════════
     ABA: EQUIPAMENTOS
     ═══════════════════════════════════════════════════════════════════════════ -->
<?php elseif ($aba === 'equipamentos'): ?>

<!-- Filtro de tipo -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
        <form method="GET" action="/cnes/<?php echo urlencode($estab->co_cnes); ?>" class="row g-2 align-items-end">
            <input type="hidden" name="aba" value="equipamentos">
            <div class="col-md-4">
                <label class="form-label small fw-bold text-muted mb-1">Tipo de Equipamento</label>
                <select name="tipo_equip" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Todos os tipos</option>
                    <?php foreach ($tiposEquip as $tipo): ?>
                    <option value="<?php echo htmlspecialchars($tipo->co_tipo_equipamento); ?>"
                            <?php echo ($_GET['tipo_equip'] ?? '') === $tipo->co_tipo_equipamento ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($tipo->no_tipo_equipamento ?? 'Tipo ' . $tipo->co_tipo_equipamento); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>
</div>

<?php if (empty($equipamentos)): ?>
<div class="card border-0 shadow-sm">
    <div class="card-body text-center py-5">
        <i class="fas fa-x-ray fa-3x text-muted opacity-50 mb-3 d-block"></i>
        <p class="text-muted">Nenhum equipamento registrado para este estabelecimento.</p>
    </div>
</div>
<?php else: ?>

<?php
// Agrupar por tipo
$equipPorTipo = [];
foreach ($equipamentos as $eq) {
    $tipo = $eq->no_tipo_desc ?? ('Tipo ' . ($eq->co_tipo_equipamento ?? '?'));
    $equipPorTipo[$tipo][] = $eq;
}
?>

<?php foreach ($equipPorTipo as $tipoNome => $itens): ?>
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
        <h6 class="mb-0 fw-bold">
            <i class="fas fa-layer-group me-2 text-primary"></i>
            <?php echo htmlspecialchars($tipoNome); ?>
        </h6>
        <span class="badge bg-primary-subtle text-primary"><?php echo count($itens); ?> equipamento(s)</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Equipamento</th>
                        <th class="text-center">Qtd. Existente</th>
                        <th class="text-center">Qtd. em Uso</th>
                        <th class="text-center">SUS</th>
                        <th>Fabricante</th>
                        <th>Modelo</th>
                        <th class="text-center">Ano Inst.</th>
                        <th class="text-center">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($itens as $eq): ?>
                    <tr id="equip-row-<?php echo (int)$eq->id; ?>">
                        <td class="ps-4 fw-semibold"><?php echo htmlspecialchars($eq->no_equipamento ?? 'Equip. ' . $eq->co_equipamento); ?></td>
                        <td class="text-center">
                            <span class="badge bg-secondary-subtle text-dark"><?php echo (int)$eq->qt_existente; ?></span>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-primary-subtle text-primary"><?php echo (int)$eq->qt_uso; ?></span>
                        </td>
                        <td class="text-center">
                            <?php if ($eq->tp_sus == '1'): ?>
                            <span class="badge bg-success-subtle text-success">Sim (<?php echo (int)$eq->qt_sus; ?>)</span>
                            <?php else: ?>
                            <span class="badge bg-light text-muted border">Não</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="equip-fabricante-display text-muted small">
                                <?php echo htmlspecialchars($eq->fabricante ?? '—'); ?>
                            </span>
                        </td>
                        <td>
                            <span class="equip-modelo-display text-muted small">
                                <?php echo htmlspecialchars($eq->modelo ?? '—'); ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <span class="equip-ano-display text-muted small">
                                <?php echo $eq->ano_instalacao ? htmlspecialchars($eq->ano_instalacao) : '—'; ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-primary btn-editar-equip"
                                    data-id="<?php echo (int)$eq->id; ?>"
                                    data-fabricante="<?php echo htmlspecialchars($eq->fabricante ?? ''); ?>"
                                    data-modelo="<?php echo htmlspecialchars($eq->modelo ?? ''); ?>"
                                    data-ano="<?php echo htmlspecialchars($eq->ano_instalacao ?? ''); ?>"
                                    data-obs="<?php echo htmlspecialchars($eq->observacoes ?? ''); ?>"
                                    title="Editar dados extras">
                                <i class="fas fa-edit"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<!-- ═══════════════════════════════════════════════════════════════════════════
     ABA: PROFISSIONAIS
     ═══════════════════════════════════════════════════════════════════════════ -->
<?php elseif ($aba === 'profissionais'): ?>

<!-- Filtros -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
        <form method="GET" action="/cnes/<?php echo urlencode($estab->co_cnes); ?>" class="row g-2 align-items-end">
            <input type="hidden" name="aba" value="profissionais">
            <div class="col-md-4">
                <label class="form-label small fw-bold text-muted mb-1">Pesquisar</label>
                <input type="text" name="q_prof" class="form-control form-control-sm"
                       placeholder="Nome ou CBO..."
                       value="<?php echo htmlspecialchars($_GET['q_prof'] ?? ''); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted mb-1">Ocupação (CBO)</label>
                <select name="cbo" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    <?php foreach ($cbos as $cbo): ?>
                    <option value="<?php echo htmlspecialchars($cbo->co_cbo); ?>"
                            <?php echo ($_GET['cbo'] ?? '') === $cbo->co_cbo ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cbo->no_cbo ?? $cbo->co_cbo); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted mb-1">Situação</label>
                <select name="situacao" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    <option value="ativo" <?php echo ($_GET['situacao'] ?? '') === 'ativo' ? 'selected' : ''; ?>>Ativo</option>
                    <option value="inativo" <?php echo ($_GET['situacao'] ?? '') === 'inativo' ? 'selected' : ''; ?>>Inativo</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary btn-sm w-100">
                    <i class="fas fa-filter me-1"></i> Filtrar
                </button>
            </div>
        </form>
    </div>
</div>

<?php if (empty($profissionais)): ?>
<div class="card border-0 shadow-sm">
    <div class="card-body text-center py-5">
        <i class="fas fa-user-md fa-3x text-muted opacity-50 mb-3 d-block"></i>
        <p class="text-muted">Nenhum profissional encontrado com os filtros aplicados.</p>
    </div>
</div>
<?php else: ?>
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
        <h6 class="mb-0 fw-bold">
            <i class="fas fa-users me-2 text-primary"></i>
            Profissionais de Saúde
        </h6>
        <span class="badge bg-primary-subtle text-primary"><?php echo count($profissionais); ?> registros</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Nome</th>
                        <th>CBO / Ocupação</th>
                        <th>Conselho</th>
                        <th>Registro</th>
                        <th>Tipo</th>
                        <th>E-mail</th>
                        <th>Contato</th>
                        <th class="text-center">Situação</th>
                        <th class="text-center">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($profissionais as $prof): ?>
                    <tr id="prof-row-<?php echo (int)$prof->id; ?>">
                        <td class="ps-4 fw-semibold"><?php echo htmlspecialchars($prof->no_profissional); ?></td>
                        <td>
                            <div class="small fw-semibold"><?php echo htmlspecialchars($prof->co_cbo ?? '—'); ?></div>
                            <div class="text-muted" style="font-size:0.75rem">
                                <?php echo htmlspecialchars($prof->no_cbo_desc ?? $prof->no_cbo ?? ''); ?>
                            </div>
                        </td>
                        <td class="small">
                            <?php echo htmlspecialchars($prof->no_conselho_desc ?? $prof->no_conselho_classe ?? '—'); ?>
                        </td>
                        <td class="small text-muted">
                            <?php if ($prof->nu_registro): ?>
                            <?php echo htmlspecialchars($prof->nu_registro); ?>
                            <?php if ($prof->sg_uf_crm): ?>/<strong><?php echo htmlspecialchars($prof->sg_uf_crm); ?></strong><?php endif; ?>
                            <?php else: ?>—<?php endif; ?>
                        </td>
                        <td class="small">
                            <?php echo $prof->tp_sus_nao_sus === 'S'
                                ? '<span class="badge bg-success-subtle text-success">SUS</span>'
                                : '<span class="badge bg-secondary-subtle text-muted">Não-SUS</span>'; ?>
                        </td>
                        <td>
                            <span class="prof-email-display small text-muted">
                                <?php echo $prof->email
                                    ? '<a href="mailto:' . htmlspecialchars($prof->email) . '">' . htmlspecialchars($prof->email) . '</a>'
                                    : '—'; ?>
                            </span>
                        </td>
                        <td>
                            <span class="prof-contato-display small text-muted">
                                <?php echo htmlspecialchars($prof->contato ?? '—'); ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <span class="prof-situacao-display">
                                <?php echo ($prof->situacao ?? 'ativo') === 'ativo'
                                    ? '<span class="badge bg-success-subtle text-success">Ativo</span>'
                                    : '<span class="badge bg-secondary-subtle text-muted">Inativo</span>'; ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-primary btn-editar-prof"
                                    data-id="<?php echo (int)$prof->id; ?>"
                                    data-nome="<?php echo htmlspecialchars($prof->no_profissional); ?>"
                                    data-email="<?php echo htmlspecialchars($prof->email ?? ''); ?>"
                                    data-contato="<?php echo htmlspecialchars($prof->contato ?? ''); ?>"
                                    data-situacao="<?php echo htmlspecialchars($prof->situacao ?? 'ativo'); ?>"
                                    data-obs="<?php echo htmlspecialchars($prof->observacoes ?? ''); ?>"
                                    title="Editar contato">
                                <i class="fas fa-edit"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>
<?php endif; // fim abas ?>

<!-- ═══════════════════════════════════════════════════════════════════════════
     MODAIS E JAVASCRIPT
     ═══════════════════════════════════════════════════════════════════════════ -->

<!-- Modal Editar Equipamento -->
<div class="modal fade" id="modalEquipamento" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold"><i class="fas fa-edit me-2 text-primary"></i>Editar Equipamento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="equipId">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Fabricante</label>
                    <input type="text" id="equipFabricante" class="form-control" placeholder="Ex: Siemens, Philips, GE...">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Modelo</label>
                    <input type="text" id="equipModelo" class="form-control" placeholder="Ex: SOMATOM Definition AS+">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Ano de Instalação</label>
                    <input type="number" id="equipAno" class="form-control" min="1980" max="<?php echo date('Y'); ?>" placeholder="Ex: 2022">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Observações</label>
                    <textarea id="equipObs" class="form-control" rows="2" placeholder="Informações adicionais..."></textarea>
                </div>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary fw-bold" id="btnSalvarEquip">
                    <i class="fas fa-save me-1"></i> Salvar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Editar Profissional -->
<div class="modal fade" id="modalProfissional" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold"><i class="fas fa-user-edit me-2 text-primary"></i>Editar Profissional</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="profId">
                <p class="text-muted small mb-3" id="profNomeLabel"></p>
                <div class="mb-3">
                    <label class="form-label fw-semibold">E-mail</label>
                    <input type="email" id="profEmail" class="form-control" placeholder="email@exemplo.com.br">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Contato (Telefone/WhatsApp)</label>
                    <input type="text" id="profContato" class="form-control" placeholder="(11) 99999-9999">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Situação</label>
                    <select id="profSituacao" class="form-select">
                        <option value="ativo">Ativo</option>
                        <option value="inativo">Inativo</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Observações</label>
                    <textarea id="profObs" class="form-control" rows="2" placeholder="Informações adicionais..."></textarea>
                </div>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary fw-bold" id="btnSalvarProf">
                    <i class="fas fa-save me-1"></i> Salvar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const cnes = '<?php echo addslashes($estab->co_cnes); ?>';

    // ── Importar como cliente (botão do header) ────────────────────────────
    const btnImportarHeader = document.querySelector('.btn-importar-header');
    if (btnImportarHeader) {
        btnImportarHeader.addEventListener('click', function (e) {
            e.preventDefault();
            Swal.fire({
                title: 'Importar como Cliente?',
                html: '<p>Deseja importar este estabelecimento como cliente no ERP?</p>',
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
    }

    // ── Modal Equipamento ──────────────────────────────────────────────────
    const modalEquip = new bootstrap.Modal(document.getElementById('modalEquipamento'));
    document.querySelectorAll('.btn-editar-equip').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('equipId').value        = this.dataset.id;
            document.getElementById('equipFabricante').value = this.dataset.fabricante;
            document.getElementById('equipModelo').value    = this.dataset.modelo;
            document.getElementById('equipAno').value       = this.dataset.ano;
            document.getElementById('equipObs').value       = this.dataset.obs;
            modalEquip.show();
        });
    });

    document.getElementById('btnSalvarEquip').addEventListener('click', function () {
        const id = document.getElementById('equipId').value;
        const fd = new URLSearchParams({
            fabricante:     document.getElementById('equipFabricante').value,
            modelo:         document.getElementById('equipModelo').value,
            ano_instalacao: document.getElementById('equipAno').value,
            observacoes:    document.getElementById('equipObs').value,
        });
        this.disabled = true;
        fetch('/cnes/equipamento/' + id + '/atualizar', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                this.disabled = false;
                if (data.success) {
                    modalEquip.hide();
                    Swal.fire({ icon: 'success', title: 'Salvo!', timer: 1500, showConfirmButton: false });
                    // Atualizar linha na tabela
                    const row = document.getElementById('equip-row-' + id);
                    if (row) {
                        row.querySelector('.equip-fabricante-display').textContent = document.getElementById('equipFabricante').value || '—';
                        row.querySelector('.equip-modelo-display').textContent     = document.getElementById('equipModelo').value || '—';
                        row.querySelector('.equip-ano-display').textContent        = document.getElementById('equipAno').value || '—';
                        // Atualizar data-* do botão
                        const btnEdit = row.querySelector('.btn-editar-equip');
                        btnEdit.dataset.fabricante = document.getElementById('equipFabricante').value;
                        btnEdit.dataset.modelo     = document.getElementById('equipModelo').value;
                        btnEdit.dataset.ano        = document.getElementById('equipAno').value;
                        btnEdit.dataset.obs        = document.getElementById('equipObs').value;
                    }
                } else {
                    Swal.fire('Erro', data.error || 'Não foi possível salvar.', 'error');
                }
            })
            .catch(() => { this.disabled = false; Swal.fire('Erro', 'Falha na comunicação.', 'error'); });
    });

    // ── Modal Profissional ─────────────────────────────────────────────────
    const modalProf = new bootstrap.Modal(document.getElementById('modalProfissional'));
    document.querySelectorAll('.btn-editar-prof').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('profId').value         = this.dataset.id;
            document.getElementById('profNomeLabel').textContent = this.dataset.nome;
            document.getElementById('profEmail').value      = this.dataset.email;
            document.getElementById('profContato').value    = this.dataset.contato;
            document.getElementById('profSituacao').value   = this.dataset.situacao;
            document.getElementById('profObs').value        = this.dataset.obs;
            modalProf.show();
        });
    });

    document.getElementById('btnSalvarProf').addEventListener('click', function () {
        const id = document.getElementById('profId').value;
        const fd = new URLSearchParams({
            email:     document.getElementById('profEmail').value,
            contato:   document.getElementById('profContato').value,
            situacao:  document.getElementById('profSituacao').value,
            observacoes: document.getElementById('profObs').value,
        });
        this.disabled = true;
        fetch('/cnes/profissional/' + id + '/atualizar', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                this.disabled = false;
                if (data.success) {
                    modalProf.hide();
                    Swal.fire({ icon: 'success', title: 'Salvo!', timer: 1500, showConfirmButton: false });
                    // Atualizar linha na tabela
                    const row = document.getElementById('prof-row-' + id);
                    if (row) {
                        const email = document.getElementById('profEmail').value;
                        row.querySelector('.prof-email-display').innerHTML = email
                            ? '<a href="mailto:' + email + '">' + email + '</a>'
                            : '—';
                        row.querySelector('.prof-contato-display').textContent = document.getElementById('profContato').value || '—';
                        const sit = document.getElementById('profSituacao').value;
                        row.querySelector('.prof-situacao-display').innerHTML = sit === 'ativo'
                            ? '<span class="badge bg-success-subtle text-success">Ativo</span>'
                            : '<span class="badge bg-secondary-subtle text-muted">Inativo</span>';
                        // Atualizar data-* do botão
                        const btnEdit = row.querySelector('.btn-editar-prof');
                        btnEdit.dataset.email    = email;
                        btnEdit.dataset.contato  = document.getElementById('profContato').value;
                        btnEdit.dataset.situacao = sit;
                        btnEdit.dataset.obs      = document.getElementById('profObs').value;
                    }
                } else {
                    Swal.fire('Erro', data.error || 'Não foi possível salvar.', 'error');
                }
            })
            .catch(() => { this.disabled = false; Swal.fire('Erro', 'Falha na comunicação.', 'error'); });
    });
});
</script>
