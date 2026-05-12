<?php
/**
 * Aba: Dados Gerais do Colaborador (CLT / PJ)
 */
$c      = $colaborador ?? null;
$isEdit = !empty($c);
$tipo   = $c->tipo_contratacao ?? 'CLT';
$action = $isEdit ? '/colaboradores/update/' . (int)$c->id : '/colaboradores/store';
?>
<form method="POST" action="<?php echo $action; ?>" id="formGeral">

    <!-- ─── Tipo de Contratação ─────────────────────────────────────────── -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom py-3">
            <h5 class="mb-0 fw-bold"><i class="fas fa-briefcase text-primary me-2"></i>Tipo de Contratação</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fw-bold">Tipo <span class="text-danger">*</span></label>
                    <select name="tipo_contratacao" id="tipo_contratacao" class="form-select" onchange="alternarTipo(this.value)">
                        <option value="CLT" <?php echo $tipo === 'CLT' ? 'selected' : ''; ?>>CLT — Pessoa Física</option>
                        <option value="PJ"  <?php echo $tipo === 'PJ'  ? 'selected' : ''; ?>>PJ — Pessoa Jurídica</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold" id="label_cpf_cnpj">
                        <span id="txt_doc">CPF</span> <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                        <input type="text" name="cpf_cnpj" id="cpf_cnpj" class="form-control"
                            placeholder="000.000.000-00"
                            value="<?php echo htmlspecialchars($c->cpf_cnpj ?? ''); ?>"
                            maxlength="18" required>
                        <button type="button" class="btn btn-outline-secondary d-none" id="btnBuscarCnpj"
                                onclick="buscarCnpj()" title="Consultar Receita Federal">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                    <small class="text-muted" id="help_doc">CPF do colaborador</small>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Status</label>
                    <select name="status" class="form-select">
                        <option value="ativo"    <?php echo ($c->status ?? 'ativo') === 'ativo'    ? 'selected' : ''; ?>>Ativo</option>
                        <option value="inativo"  <?php echo ($c->status ?? '') === 'inativo'  ? 'selected' : ''; ?>>Inativo</option>
                        <option value="afastado" <?php echo ($c->status ?? '') === 'afastado' ? 'selected' : ''; ?>>Afastado</option>
                        <option value="demitido" <?php echo ($c->status ?? '') === 'demitido' ? 'selected' : ''; ?>>Demitido</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- ─── Identificação ──────────────────────────────────────────────── -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom py-3">
            <h5 class="mb-0 fw-bold"><i class="fas fa-id-card text-primary me-2"></i>Identificação</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold" id="label_nome">
                        Nome Completo <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="nome" id="nome" class="form-control"
                        placeholder="Nome completo do colaborador"
                        value="<?php echo htmlspecialchars($c->nome ?? ''); ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold" id="label_nome_social">Nome Social / Apelido</label>
                    <input type="text" name="nome_social" id="nome_social" class="form-control"
                        placeholder="Nome social ou apelido"
                        value="<?php echo htmlspecialchars($c->nome_social ?? ''); ?>">
                </div>

                <!-- Campos CLT -->
                <div id="campos_clt">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Data de Nascimento</label>
                            <input type="date" name="data_nascimento" class="form-control"
                                value="<?php echo htmlspecialchars($c->data_nascimento ?? ''); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">RG</label>
                            <input type="text" name="rg" class="form-control"
                                value="<?php echo htmlspecialchars($c->rg ?? ''); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold">Órgão Emissor</label>
                            <input type="text" name="orgao_emissor" class="form-control"
                                value="<?php echo htmlspecialchars($c->orgao_emissor ?? ''); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold">PIS/PASEP</label>
                            <input type="text" name="pis_pasep" class="form-control"
                                value="<?php echo htmlspecialchars($c->pis_pasep ?? ''); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold">Estado Civil</label>
                            <select name="estado_civil" class="form-select">
                                <option value="">Selecione</option>
                                <?php
                                $estadosCivis = ['solteiro' => 'Solteiro(a)', 'casado' => 'Casado(a)', 'divorciado' => 'Divorciado(a)', 'viuvo' => 'Viúvo(a)', 'uniao_estavel' => 'União Estável', 'outro' => 'Outro'];
                                foreach ($estadosCivis as $val => $label):
                                ?>
                                    <option value="<?php echo $val; ?>" <?php echo ($c->estado_civil ?? '') === $val ? 'selected' : ''; ?>>
                                        <?php echo $label; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">CTPS</label>
                            <input type="text" name="ctps" class="form-control"
                                value="<?php echo htmlspecialchars($c->ctps ?? ''); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold">Série CTPS</label>
                            <input type="text" name="ctps_serie" class="form-control"
                                value="<?php echo htmlspecialchars($c->ctps_serie ?? ''); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Escolaridade</label>
                            <input type="text" name="escolaridade" class="form-control"
                                value="<?php echo htmlspecialchars($c->escolaridade ?? ''); ?>">
                        </div>
                    </div>
                </div>

                <!-- Campos PJ -->
                <div id="campos_pj" class="d-none">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Inscrição Estadual</label>
                            <input type="text" name="inscricao_estadual" class="form-control"
                                value="<?php echo htmlspecialchars($c->inscricao_estadual ?? ''); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Inscrição Municipal</label>
                            <input type="text" name="inscricao_municipal" class="form-control"
                                value="<?php echo htmlspecialchars($c->inscricao_municipal ?? ''); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">CNAE Principal</label>
                            <input type="text" name="cnae_principal" id="cnae_principal" class="form-control"
                                value="<?php echo htmlspecialchars($c->cnae_principal ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Descrição CNAE</label>
                            <input type="text" name="descricao_cnae" id="descricao_cnae" class="form-control"
                                value="<?php echo htmlspecialchars($c->descricao_cnae ?? ''); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Nome do Responsável Legal</label>
                            <input type="text" name="nome_responsavel" class="form-control"
                                value="<?php echo htmlspecialchars($c->nome_responsavel ?? ''); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">CPF do Responsável</label>
                            <input type="text" name="cpf_responsavel" class="form-control"
                                value="<?php echo htmlspecialchars($c->cpf_responsavel ?? ''); ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ─── Contato ─────────────────────────────────────────────────────── -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom py-3">
            <h5 class="mb-0 fw-bold"><i class="fas fa-envelope text-primary me-2"></i>Contato</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-bold">E-mail <span class="text-danger">*</span></label>
                    <input type="email" name="email" id="email" class="form-control"
                        value="<?php echo htmlspecialchars($c->email ?? ''); ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Telefone</label>
                    <input type="text" name="telefone" class="form-control"
                        value="<?php echo htmlspecialchars($c->telefone ?? ''); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Celular</label>
                    <div class="input-group">
                        <input type="text" name="celular" id="celular" class="form-control"
                            placeholder="(00) 00000-0000"
                            maxlength="15"
                            value="<?php echo htmlspecialchars($c->celular ?? ''); ?>"
                            oninput="mascaraCelular(this)">
                        <a id="btnWhatsapp" href="#" target="_blank"
                           class="btn btn-success d-flex align-items-center px-3"
                           title="Abrir no WhatsApp"
                           onclick="abrirWhatsapp(event)">
                            <i class="fab fa-whatsapp fs-5"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ─── Endereço ─────────────────────────────────────────────────────── -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom py-3">
            <h5 class="mb-0 fw-bold"><i class="fas fa-map-marker-alt text-primary me-2"></i>Endereço</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-2">
                    <label class="form-label fw-bold">CEP</label>
                    <input type="text" name="cep" id="cep" class="form-control" maxlength="9"
                        value="<?php echo htmlspecialchars($c->cep ?? ''); ?>"
                        onblur="buscarCep(this.value)">
                </div>
                <div class="col-md-5">
                    <label class="form-label fw-bold">Endereço</label>
                    <input type="text" name="endereco" id="endereco" class="form-control"
                        value="<?php echo htmlspecialchars($c->endereco ?? ''); ?>">
                </div>
                <div class="col-md-1">
                    <label class="form-label fw-bold">Número</label>
                    <input type="text" name="numero" id="numero" class="form-control"
                        value="<?php echo htmlspecialchars($c->numero ?? ''); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Complemento</label>
                    <input type="text" name="complemento" id="complemento" class="form-control"
                        value="<?php echo htmlspecialchars($c->complemento ?? ''); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Bairro</label>
                    <input type="text" name="bairro" id="bairro" class="form-control"
                        value="<?php echo htmlspecialchars($c->bairro ?? ''); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Cidade</label>
                    <input type="text" name="cidade" id="cidade" class="form-control"
                        value="<?php echo htmlspecialchars($c->cidade ?? ''); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Estado (UF)</label>
                    <input type="text" name="estado" id="estado" class="form-control" maxlength="2"
                        value="<?php echo htmlspecialchars($c->estado ?? ''); ?>">
                </div>
            </div>
        </div>
    </div>

    <!-- ─── Dados Profissionais ──────────────────────────────────────────── -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom py-3">
            <h5 class="mb-0 fw-bold"><i class="fas fa-building text-primary me-2"></i>Dados Profissionais</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-bold">Cargo</label>
                    <input type="text" name="cargo" class="form-control"
                        value="<?php echo htmlspecialchars($c->cargo ?? ''); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Departamento</label>
                    <input type="text" name="departamento" class="form-control"
                        value="<?php echo htmlspecialchars($c->departamento ?? ''); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Data de Admissão</label>
                    <input type="date" name="data_admissao" class="form-control"
                        value="<?php echo htmlspecialchars($c->data_admissao ?? ''); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Data de Demissão</label>
                    <input type="date" name="data_demissao" class="form-control"
                        value="<?php echo htmlspecialchars($c->data_demissao ?? ''); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Salário Base (R$)</label>
                    <input type="text" name="salario_base" class="form-control"
                        value="<?php echo number_format((float)($c->salario_base ?? 0), 2, ',', '.'); ?>">
                </div>
            </div>
        </div>
    </div>

    <!-- ─── Dados Bancários ──────────────────────────────────────────────── -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom py-3">
            <h5 class="mb-0 fw-bold"><i class="fas fa-university text-primary me-2"></i>Dados Bancários</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-bold">Banco</label>
                    <select name="banco" id="banco" class="form-select">
                        <option value="">Selecione o banco</option>
                        <?php
                        $bancos = [
                            '001' => 'Banco do Brasil',
                            '003' => 'Banco da Amazônia',
                            '004' => 'Banco do Nordeste',
                            '021' => 'Banestes',
                            '025' => 'Banco Alfa',
                            '033' => 'Santander',
                            '036' => 'Banco BBI',
                            '037' => 'Banco do Estado do Pará',
                            '040' => 'Banco Cargill',
                            '041' => 'Banrisul',
                            '047' => 'Banco do Estado de Sergipe',
                            '069' => 'Banco Crefisa',
                            '070' => 'BRB — Banco de Brasília',
                            '077' => 'Banco Inter',
                            '084' => 'Uniprime Norte do Paraná',
                            '085' => 'Cooperativa Central de Crédito Urbano (Cecred)',
                            '089' => 'Credisan',
                            '097' => 'Credisis',
                            '099' => 'Uniprime Central',
                            '104' => 'Caixa Econômica Federal',
                            '107' => 'Banco Bocom BBM',
                            '114' => 'Central das Cooperativas de Economia e Crédito Mútuo (Cecoopes)',
                            '119' => 'Banco Western Union',
                            '120' => 'Banco Rodobens',
                            '121' => 'Banco Agiplan',
                            '125' => 'Banco Genial',
                            '128' => 'MS Bank',
                            '129' => 'UBS Brasil',
                            '130' => 'Caruana SCFI',
                            '131' => 'Tullett Prebon Brasil',
                            '132' => 'ICBC do Brasil',
                            '133' => 'Cresol Confederação',
                            '136' => 'Unicred',
                            '144' => 'Bexs Banco de Câmbio',
                            '149' => 'Facta Financeira',
                            '169' => 'Banco Olé Bonsucesso',
                            '173' => 'BRL Trust DTVM',
                            '174' => 'Pernambucanas Financiadora',
                            '177' => 'Guide Investimentos',
                            '180' => 'CM Capital Markets',
                            '183' => 'Socred',
                            '184' => 'Banco Itaú BBA',
                            '190' => 'Servicoop',
                            '191' => 'Nova Futura',
                            '194' => 'Parmetal DTVM',
                            '196' => 'Fair CC',
                            '197' => 'Stone Pagamentos',
                            '208' => 'Banco BTG Pactual',
                            '212' => 'Banco Original',
                            '213' => 'Banco Arbi',
                            '217' => 'Banco John Deere',
                            '218' => 'Banco BS2',
                            '222' => 'Banco Credit Agricole Brasil',
                            '224' => 'Banco Fibra',
                            '233' => 'Banco Cifra',
                            '237' => 'Bradesco',
                            '241' => 'Banco Clássico',
                            '243' => 'Banco Master',
                            '246' => 'Banco ABC Brasil',
                            '249' => 'Banco Investcred Unibanco',
                            '250' => 'BCV — Banco de Crédito e Varejo',
                            '253' => 'Bexs CC',
                            '254' => 'Parana Banco',
                            '260' => 'Nubank',
                            '265' => 'Banco Fator',
                            '266' => 'Banco Cédula',
                            '268' => 'Barigui Companhia Hipotecária',
                            '269' => 'HSBC Brasil',
                            '270' => 'Sagitur CC',
                            '271' => 'IB CCTVM',
                            '272' => 'AGK CC',
                            '273' => 'CCR de São Miguel do Oeste',
                            '274' => 'Money Plus SCMEPP',
                            '276' => 'Senff',
                            '278' => 'Genial Investimentos CVM',
                            '279' => 'CCR de Primavera do Leste',
                            '280' => 'Avista',
                            '281' => 'CCR Coopavel',
                            '283' => 'RB Capital Investimentos DTVM',
                            '285' => 'Frente CC',
                            '286' => 'CCR de Ouro',
                            '288' => 'Carol DTVM',
                            '289' => 'EFX CC',
                            '290' => 'PagSeguro Internet',
                            '292' => 'BS2 DTVM',
                            '293' => 'Lastro RDV DTVM',
                            '296' => 'OZ Investimentos DTVM',
                            '298' => 'Vips CC',
                            '299' => 'Sorocred',
                            '300' => 'Banco de la Nacion Argentina',
                            '301' => 'BPP Instituição de Pagamento',
                            '306' => 'Portopar DTVM',
                            '307' => 'Terra Investimentos DTVM',
                            '309' => 'Cambionet CC',
                            '310' => 'VORTX DTVM',
                            '315' => 'PI DTVM',
                            '318' => 'Banco BMG',
                            '319' => 'OM DTVM',
                            '320' => 'China Construction Bank',
                            '321' => 'Crefaz SCMEPP',
                            '322' => 'CCR de Abelardo Luz',
                            '323' => 'Mercado Pago',
                            '324' => 'Cartos SCD',
                            '325' => 'Órama DTVM',
                            '326' => 'Parati — CFI',
                            '328' => 'Cecm Fabric Calçados Sapiranga',
                            '329' => 'QI SCD',
                            '330' => 'Banco Bari',
                            '331' => 'Fram Capital DTVM',
                            '332' => 'Acesso Soluções de Pagamento',
                            '335' => 'Banco Digio',
                            '336' => 'Banco C6',
                            '340' => 'Super Pagamentos',
                            '341' => 'Itaú Unibanco',
                            '342' => 'Creditas SCD',
                            '343' => 'FFA SCMEPP',
                            '348' => 'Banco XP',
                            '349' => 'AL5 SCD',
                            '350' => 'Crehnor Laranjeiras',
                            '352' => 'Toro CTVM',
                            '354' => 'Necton Investimentos',
                            '355' => 'Ótimo SCD',
                            '356' => 'Banco Real (ABN AMRO)',
                            '358' => 'Midway',
                            '359' => 'Zema CFI',
                            '360' => 'Trinus Capital DTVM',
                            '362' => 'Cielo',
                            '363' => 'Singulare CTVM',
                            '364' => 'Gerencianet Pagamentos',
                            '365' => 'Solidus CCVM',
                            '366' => 'Banco Societe Generale Brasil',
                            '368' => 'Banco CSF',
                            '370' => 'Banco Mizuho',
                            '371' => 'Warren CVMC',
                            '372' => 'Oliveira Trust DTVM',
                            '373' => 'UP.p SEP',
                            '374' => 'Realize CFI',
                            '376' => 'Banco J. P. Morgan',
                            '377' => 'BMS SCD',
                            '378' => 'BBC Leasing',
                            '379' => 'Cecm Cooperforte',
                            '380' => 'PicPay',
                            '381' => 'Banco Municipal de Osasco',
                            '382' => 'Fiducia SCMEPP',
                            '383' => 'Ebanx IP',
                            '384' => 'Global SCM',
                            '385' => 'Cecm dos Trabalhadores Portuários de Paranaguá',
                            '386' => 'Nu Financeira',
                            '387' => 'Banco Toyota do Brasil',
                            '389' => 'Banco Mercantil do Brasil',
                            '390' => 'GM Financial',
                            '391' => 'CCR de Ibiam',
                            '393' => 'Banco Volkswagen',
                            '394' => 'Banco Bradesco Financiamentos',
                            '395' => 'FD Gold CC',
                            '396' => 'Hub IP',
                            '397' => 'Listo SCD',
                            '398' => 'Ideal CTVM',
                            '399' => 'Kirton Bank',
                            '400' => 'Cooperativa de Crédito Rural de Pequenos Agricultores e da Reforma Agrária',
                            '401' => 'Iugu IP',
                            '402' => 'Cobuccio SCD',
                            '403' => 'Cora SCD',
                            '404' => 'Sumup SCD',
                            '406' => 'Accredito SCD',
                            '407' => 'Índigo Investimentos DTVM',
                            '408' => 'Bonuspago SCD',
                            '410' => 'Planner CV',
                            '411' => 'Via Certa Financiadora',
                            '412' => 'Banco Capital',
                            '413' => 'Banco BV',
                            '414' => 'Work SCD',
                            '415' => 'Lamara SCD',
                            '416' => 'Lamara SCD',
                            '418' => 'Zipdin SCD',
                            '419' => 'Numbrs SCD',
                            '421' => 'Celcoin IP',
                            '422' => 'Banco Safra',
                            '423' => 'Coluna SCD',
                            '425' => 'Treviso CC',
                            '426' => 'Neon Financeira',
                            '427' => 'Cresol',
                            '428' => 'Credsystem SCD',
                            '429' => 'Crediare CFI',
                            '430' => 'CCR Seara',
                            '433' => 'BR-Capital DTVM',
                            '435' => 'Delcred SCD',
                            '438' => 'Planner Trustee DTVM',
                            '440' => 'Credibrf Coop',
                            '443' => 'Credihome SCD',
                            '444' => 'Trinus SCD',
                            '445' => 'Plantae CFI',
                            '447' => 'Mirae Asset CCTVM',
                            '448' => 'Hemera DTVM',
                            '449' => 'Dmcard SCD',
                            '450' => 'Fitbank IP',
                            '451' => 'J17 — MEP',
                            '452' => 'Credifit SCD',
                            '453' => 'Mérito DTVM',
                            '454' => 'Mérito DTVM',
                            '455' => 'Fênix DTVM',
                            '456' => 'Banco MUFG Brasil',
                            '457' => 'UY3 SCD',
                            '458' => 'Hedge Investments DTVM',
                            '459' => 'CCR Nosso Crédito',
                            '460' => 'Unavanti SCD',
                            '461' => 'Asaas IP',
                            '462' => 'Stark SCD',
                            '463' => 'Azumi DTVM',
                            '464' => 'Banco Sumitomo Mitsui Brasileiro',
                            '465' => 'Capital Consig SCD',
                            '467' => 'Master S/A CCTVM',
                            '469' => 'Picpay Bank',
                            '470' => 'CDC SCD',
                            '473' => 'Banco Caixa Geral Brasil',
                            '477' => 'Citibank',
                            '478' => 'Gazincred SCD',
                            '479' => 'Banco ItauBank',
                            '481' => 'Superlógica SCD',
                            '482' => 'SBCASH SCD',
                            '484' => 'MAF DTVM',
                            '487' => 'Deutsche Bank',
                            '488' => 'JPMorgan Chase Bank',
                            '492' => 'ING Bank',
                            '495' => 'Banco de La Provincia de Buenos Aires',
                            '505' => 'Banco Credit Suisse',
                            '506' => 'RJI',
                            '507' => 'Scfi e Bancos com Carteiras de Crédito Imobiliário',
                            '509' => 'Celcoin IP',
                            '511' => 'Magnum SCD',
                            '512' => 'Captalys DTVM',
                            '516' => 'Qista SCD',
                            '519' => 'Ewally IP',
                            '521' => 'Peak SEP',
                            '522' => 'Red Financeira',
                            '523' => 'HR Digital SCD',
                            '524' => 'WNT Capital DTVM',
                            '525' => 'Intercam CC',
                            '526' => 'Nagro SCD',
                            '527' => 'Aticca SCD',
                            '528' => 'Reag DTVM',
                            '529' => 'Pinbank IP',
                            '530' => 'Sarbabu SCD',
                            '531' => 'BMP SCD',
                            '532' => 'Eagle SCD',
                            '533' => 'SRM Bank',
                            '534' => 'Ewally IP',
                            '535' => 'Opea SCD',
                            '536' => 'Neon Pagamentos',
                            '537' => 'Microcash SCMEPP',
                            '538' => 'Sudacred SCD',
                            '539' => 'Santinvest SCD',
                            '540' => 'Bukly IP',
                            '541' => 'Fundo de Garantia do Estado de Minas Gerais',
                            '543' => 'Cooperativa de Crédito Rural de Planalto das Araucárias',
                            '544' => 'Multicred SCD',
                            '545' => 'Senso CCVM',
                            '546' => 'Unfocused SCD',
                            '547' => 'BRL Trust DTVM',
                            '548' => 'RPW SCD',
                            '549' => 'Intra SCD',
                            '550' => 'Beeteller',
                            '552' => 'Num Financeira',
                            '553' => 'Percapita SCD',
                            '554' => 'Proseftur SCD',
                            '556' => 'Prosper Soluções Financeiras',
                            '558' => 'Zipdin SCD',
                            '559' => 'Kanastra SCD',
                            '560' => 'Maru SCD',
                            '561' => 'Pay4Fun IP',
                            '562' => 'Azimut Brasil DTVM',
                            '563' => 'Grão Direto SCD',
                            '564' => 'Finabank SCD',
                            '565' => 'Ágora CTVM',
                            '566' => 'Flagship SCD',
                            '567' => 'Libra IP',
                            '568' => 'Blu Financeira',
                            '569' => 'Conta Simples SCD',
                            '571' => 'Brcondos SCD',
                            '572' => 'Zro IP',
                            '574' => 'Finaxis CTVM',
                            '576' => 'Banco Guanabara',
                            '577' => 'Reag DTVM',
                            '579' => 'RFB SCD',
                            '580' => 'Portoseg CFI',
                            '581' => 'Banco Brasileiro de Crédito',
                            '582' => 'Vortx DTVM',
                            '583' => 'Vert Financeira',
                            '584' => 'Lecca CFI',
                            '585' => 'Itaú Unibanco Holding',
                            '586' => 'Finvest DTVM',
                            '588' => 'Prover IP',
                            '589' => 'Creditas SCD',
                            '590' => 'Moneycorp Banco de Câmbio',
                            '591' => 'Banco Bradesco Cartões',
                            '600' => 'Banco Luso Brasileiro',
                            '604' => 'Banco Industrial do Brasil',
                            '610' => 'Banco VR',
                            '611' => 'Banco Paulista',
                            '612' => 'Banco Guanabara',
                            '613' => 'Omni Banco',
                            '620' => 'Banco Crédit Agricole Brasil',
                            '623' => 'Banco Pan',
                            '626' => 'Banco C6 Consignado',
                            '630' => 'Banco Intercap',
                            '633' => 'Banco Rendimento',
                            '634' => 'Banco Triângulo',
                            '637' => 'Banco Sofisa',
                            '643' => 'Banco Pine',
                            '652' => 'Itaú Unibanco Holding',
                            '653' => 'Banco Indusval',
                            '654' => 'Banco A.J. Renner',
                            '655' => 'Banco Votorantim',
                            '707' => 'Banco Daycoval',
                            '712' => 'Banco Ourinvest',
                            '719' => 'Banif',
                            '720' => 'Banco RNX',
                            '739' => 'Banco Cetelem',
                            '741' => 'Banco Ribeirão Preto',
                            '743' => 'Banco Semear',
                            '745' => 'Banco Citibank',
                            '746' => 'Banco Modal',
                            '747' => 'Banco Rabobank',
                            '748' => 'Sicredi',
                            '751' => 'Scotiabank Brasil',
                            '752' => 'Banco BNP Paribas Brasil',
                            '753' => 'Novo Banco Continental',
                            '754' => 'Banco Sistema',
                            '755' => 'Bank of America Merrill Lynch',
                            '756' => 'Sicoob',
                            '757' => 'Banco Keb Hana do Brasil',
                        ];
                        $bancoSalvo = $c->banco ?? '';
                        foreach ($bancos as $cod => $nome):
                            $selected = ($bancoSalvo === $cod || $bancoSalvo === $nome || $bancoSalvo === "$cod - $nome") ? 'selected' : '';
                        ?>
                            <option value="<?php echo $cod; ?>" <?php echo $selected; ?>>
                                <?php echo $cod . ' — ' . $nome; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Agência</label>
                    <input type="text" name="agencia" class="form-control"
                        value="<?php echo htmlspecialchars($c->agencia ?? ''); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Conta</label>
                    <input type="text" name="conta" class="form-control"
                        value="<?php echo htmlspecialchars($c->conta ?? ''); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Tipo de Conta</label>
                    <select name="tipo_conta" class="form-select">
                        <option value="">Selecione</option>
                        <option value="corrente" <?php echo ($c->tipo_conta ?? '') === 'corrente' ? 'selected' : ''; ?>>Corrente</option>
                        <option value="poupanca" <?php echo ($c->tipo_conta ?? '') === 'poupanca' ? 'selected' : ''; ?>>Poupança</option>
                        <option value="salario"  <?php echo ($c->tipo_conta ?? '') === 'salario'  ? 'selected' : ''; ?>>Salário</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Chave PIX</label>
                    <input type="text" name="chave_pix" class="form-control"
                        value="<?php echo htmlspecialchars($c->chave_pix ?? ''); ?>">
                </div>
            </div>
        </div>
    </div>

    <!-- ─── Observações ──────────────────────────────────────────────────── -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <label class="form-label fw-bold">Observações</label>
            <textarea name="observacoes" class="form-control" rows="3"><?php echo htmlspecialchars($c->observacoes ?? ''); ?></textarea>
        </div>
    </div>

    <!-- Botões de ação -->
    <div class="d-flex gap-2 justify-content-end mb-5">
        <a href="/colaboradores" class="btn btn-outline-secondary btn-lg">Cancelar</a>
        <button type="submit" class="btn btn-primary btn-lg fw-bold">
            <i class="fas fa-save me-1"></i>
            <?php echo $isEdit ? 'Salvar Alterações' : 'Cadastrar Colaborador'; ?>
        </button>
    </div>
</form>

<script>
const tipoAtual = '<?php echo $tipo; ?>';

function alternarTipo(tipo) {
    const isClt = tipo === 'CLT';
    document.getElementById('campos_clt').classList.toggle('d-none', !isClt);
    document.getElementById('campos_pj').classList.toggle('d-none', isClt);
    document.getElementById('txt_doc').textContent = isClt ? 'CPF' : 'CNPJ';
    document.getElementById('help_doc').textContent = isClt ? 'CPF do colaborador' : 'CNPJ da empresa';
    document.getElementById('label_nome').innerHTML = (isClt ? 'Nome Completo' : 'Razão Social') + ' <span class="text-danger">*</span>';
    document.getElementById('label_nome_social').textContent = isClt ? 'Nome Social / Apelido' : 'Nome Fantasia';
    document.getElementById('nome').placeholder = isClt ? 'Nome completo do colaborador' : 'Razão Social da empresa';
    document.getElementById('btnBuscarCnpj').classList.toggle('d-none', isClt);
    const cpfCnpj = document.getElementById('cpf_cnpj');
    cpfCnpj.placeholder = isClt ? '000.000.000-00' : '00.000.000/0000-00';
    cpfCnpj.maxLength = isClt ? 14 : 18;
}

// Inicializa com o tipo correto ao carregar
alternarTipo(tipoAtual);

// ─── Máscara de celular ──────────────────────────────────────────────────────
function mascaraCelular(input) {
    let v = input.value.replace(/\D/g, '').substring(0, 11);
    if (v.length > 10) {
        v = v.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
    } else if (v.length > 6) {
        v = v.replace(/(\d{2})(\d{4})(\d{0,4})/, '($1) $2-$3');
    } else if (v.length > 2) {
        v = v.replace(/(\d{2})(\d{0,5})/, '($1) $2');
    } else if (v.length > 0) {
        v = v.replace(/(\d{0,2})/, '($1');
    }
    input.value = v;
}

// Aplica máscara ao valor já salvo no banco
(function() {
    const cel = document.getElementById('celular');
    if (cel && cel.value) mascaraCelular(cel);
})();

// ─── WhatsApp ────────────────────────────────────────────────────────────────
function abrirWhatsapp(e) {
    e.preventDefault();
    const raw = document.getElementById('celular').value.replace(/\D/g, '');
    if (!raw || raw.length < 10) {
        alert('Informe um número de celular válido antes de abrir o WhatsApp.');
        return;
    }
    // Adiciona DDI 55 (Brasil) se não estiver presente
    const numero = raw.startsWith('55') ? raw : '55' + raw;
    window.open('https://wa.me/' + numero, '_blank');
}

// Busca CNPJ na Receita Federal
function buscarCnpj() {
    const cnpj = document.getElementById('cpf_cnpj').value.replace(/\D/g, '');
    if (cnpj.length !== 14) { alert('Digite um CNPJ válido com 14 dígitos.'); return; }
    const btn = document.getElementById('btnBuscarCnpj');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    btn.disabled = true;
    fetch('/colaboradores/buscar-cnpj?cnpj=' + cnpj)
        .then(r => r.json())
        .then(data => {
            if (data.success && data.dados) {
                const d = data.dados;
                if (d.nome)           document.getElementById('nome').value           = d.nome;
                if (d.nome_social)    document.getElementById('nome_social').value    = d.nome_social;
                if (d.email)          document.getElementById('email').value          = d.email;
                if (d.cep)            document.getElementById('cep').value            = d.cep;
                if (d.endereco)       document.getElementById('endereco').value       = d.endereco;
                if (d.numero)         document.getElementById('numero').value         = d.numero;
                if (d.complemento)    document.getElementById('complemento').value    = d.complemento;
                if (d.bairro)         document.getElementById('bairro').value         = d.bairro;
                if (d.cidade)         document.getElementById('cidade').value         = d.cidade;
                if (d.estado)         document.getElementById('estado').value         = d.estado;
                if (d.cnae_principal) document.getElementById('cnae_principal').value = d.cnae_principal;
                if (d.descricao_cnae) document.getElementById('descricao_cnae').value = d.descricao_cnae;
            } else {
                alert(data.erro || 'CNPJ não encontrado.');
            }
        })
        .catch(() => alert('Erro ao consultar CNPJ. Tente novamente.'))
        .finally(() => {
            btn.innerHTML = '<i class="fas fa-search"></i>';
            btn.disabled = false;
        });
}

// Busca CEP via ViaCEP
function buscarCep(cep) {
    cep = cep.replace(/\D/g, '');
    if (cep.length !== 8) return;
    fetch('https://viacep.com.br/ws/' + cep + '/json/')
        .then(r => r.json())
        .then(data => {
            if (!data.erro) {
                document.getElementById('endereco').value   = data.logradouro || '';
                document.getElementById('bairro').value     = data.bairro     || '';
                document.getElementById('cidade').value     = data.localidade  || '';
                document.getElementById('estado').value     = data.uf          || '';
                document.getElementById('numero').focus();
            }
        })
        .catch(() => {});
}
</script>
