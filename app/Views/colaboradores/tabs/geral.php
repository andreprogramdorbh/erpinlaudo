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
                    <input type="text" name="celular" class="form-control"
                        value="<?php echo htmlspecialchars($c->celular ?? ''); ?>">
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
                    <input type="text" name="banco" class="form-control"
                        value="<?php echo htmlspecialchars($c->banco ?? ''); ?>">
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
