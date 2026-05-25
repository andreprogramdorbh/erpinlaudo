<?php
$action = $isEdit ? '/fornecedores/update/' . ($fornecedor->id ?? '') : '/fornecedores';
?>
<form id="fornecedorFormGeral" action="<?php echo $action; ?>" method="POST" class="enterprise-form-main">

    <!-- Seção: Identificação -->
    <section class="form-section">
        <h2 class="form-section-title">
            <i class="fas fa-id-card section-icon"></i>
            Identificação
        </h2>
        <div class="form-grid form-grid-3">
            <div class="form-group">
                <label for="tipo" class="form-label">Tipo de Pessoa</label>
                <select name="tipo" id="tipo" class="form-select">
                    <option value="PJ" <?php echo ($fornecedor->tipo ?? 'PJ') === 'PJ' ? 'selected' : ''; ?>>Pessoa Jurídica (CNPJ)</option>
                    <option value="PF" <?php echo ($fornecedor->tipo ?? '') === 'PF' ? 'selected' : ''; ?>>Pessoa Física (CPF)</option>
                </select>
                <small class="text-muted">Selecione o tipo de identificação</small>
            </div>
            <div class="form-group">
                <label for="documento" class="form-label" id="label_documento">CNPJ</label>
                <div class="input-group">
                    <input type="text" name="documento" id="documento" class="form-control"
                        placeholder="00.000.000/0000-00"
                        value="<?php echo htmlspecialchars($fornecedor->documento ?? ''); ?>">
                    <button type="button" class="btn btn-primary" id="btn_consulta_cnpj" title="Buscar dados do CNPJ">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
                <small class="text-muted" id="doc_help">Digite o CNPJ e clique em buscar para preencher automaticamente</small>
            </div>
            <div class="form-group">
                <label for="status" class="form-label">Status</label>
                <select name="status" id="status" class="form-select">
                    <option value="ativo" <?php echo ($fornecedor->status ?? 'ativo') === 'ativo' ? 'selected' : ''; ?>>Ativo</option>
                    <option value="inativo" <?php echo ($fornecedor->status ?? '') === 'inativo' ? 'selected' : ''; ?>>Inativo</option>
                </select>
            </div>
        </div>
        <div id="cnpj_alerta" class="alert mt-2 d-none" role="alert"></div>
    </section>

    <!-- Seção: Dados Principais -->
    <section class="form-section">
        <h2 class="form-section-title">
            <i class="fas fa-building section-icon"></i>
            Dados Principais
        </h2>
        <div class="form-grid form-grid-2">
            <div class="form-group">
                <label for="nome" class="form-label required">Razão Social / Nome</label>
                <input type="text" name="nome" id="nome" class="form-control"
                    placeholder="Nome completo ou Razão Social"
                    value="<?php echo htmlspecialchars($fornecedor->nome ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="nome_fantasia" class="form-label">Nome Fantasia</label>
                <input type="text" name="nome_fantasia" id="nome_fantasia" class="form-control"
                    placeholder="Nome comercial (opcional)"
                    value="<?php echo htmlspecialchars($fornecedor->nome_fantasia ?? ''); ?>">
            </div>
        </div>
        <div class="form-grid form-grid-3 mt-3">
            <div class="form-group">
                <label for="email" class="form-label">E-mail</label>
                <input type="email" name="email" id="email" class="form-control"
                    placeholder="financeiro@fornecedor.com"
                    value="<?php echo htmlspecialchars($fornecedor->email ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="telefone" class="form-label">Telefone</label>
                <input type="text" name="telefone" id="telefone" class="form-control"
                    placeholder="(00) 0000-0000"
                    value="<?php echo htmlspecialchars($fornecedor->telefone ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="celular" class="form-label">Celular / WhatsApp</label>
                <input type="text" name="celular" id="celular" class="form-control"
                    placeholder="(00) 00000-0000"
                    value="<?php echo htmlspecialchars($fornecedor->celular ?? ''); ?>">
            </div>
        </div>
        <div class="form-grid form-grid-2 mt-3">
            <div class="form-group">
                <label for="contato_nome" class="form-label">Nome do Contato</label>
                <input type="text" name="contato_nome" id="contato_nome" class="form-control"
                    placeholder="Responsável comercial"
                    value="<?php echo htmlspecialchars($fornecedor->contato_nome ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="website" class="form-label">Website</label>
                <input type="text" name="website" id="website" class="form-control"
                    placeholder="https://www.fornecedor.com.br"
                    value="<?php echo htmlspecialchars($fornecedor->website ?? ''); ?>">
            </div>
        </div>
    </section>

    <!-- Seção: Endereço -->
    <section class="form-section">
        <h2 class="form-section-title">
            <i class="fas fa-map-marker-alt section-icon"></i>
            Endereço
        </h2>
        <div class="form-grid form-grid-3">
            <div class="form-group">
                <label for="cep" class="form-label">CEP</label>
                <div class="input-group">
                    <input type="text" name="cep" id="cep" class="form-control"
                        placeholder="00000-000"
                        value="<?php echo htmlspecialchars($fornecedor->cep ?? ''); ?>">
                    <button type="button" class="btn btn-outline-secondary" id="btn_buscar_cep" title="Buscar endereço pelo CEP">
                        <i class="fas fa-map-pin"></i>
                    </button>
                </div>
            </div>
            <div class="form-group col-span-2">
                <label for="endereco" class="form-label">Logradouro</label>
                <input type="text" name="endereco" id="endereco" class="form-control"
                    placeholder="Rua, Avenida, etc."
                    value="<?php echo htmlspecialchars($fornecedor->endereco ?? ''); ?>">
            </div>
        </div>
        <div class="form-grid form-grid-4 mt-3">
            <div class="form-group">
                <label for="numero" class="form-label">Número</label>
                <input type="text" name="numero" id="numero" class="form-control"
                    placeholder="123"
                    value="<?php echo htmlspecialchars($fornecedor->numero ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="complemento" class="form-label">Complemento</label>
                <input type="text" name="complemento" id="complemento" class="form-control"
                    placeholder="Sala, Bloco, etc."
                    value="<?php echo htmlspecialchars($fornecedor->complemento ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="bairro" class="form-label">Bairro</label>
                <input type="text" name="bairro" id="bairro" class="form-control"
                    placeholder="Centro"
                    value="<?php echo htmlspecialchars($fornecedor->bairro ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="cidade" class="form-label">Cidade</label>
                <input type="text" name="cidade" id="cidade" class="form-control"
                    placeholder="São Paulo"
                    value="<?php echo htmlspecialchars($fornecedor->cidade ?? ''); ?>">
            </div>
        </div>
        <div class="form-grid form-grid-4 mt-3">
            <div class="form-group">
                <label for="estado" class="form-label">UF</label>
                <select name="estado" id="estado" class="form-select">
                    <option value="">Selecione...</option>
                    <?php
                    $ufs = ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'];
                    foreach ($ufs as $uf): ?>
                        <option value="<?php echo $uf; ?>" <?php echo ($fornecedor->estado ?? '') === $uf ? 'selected' : ''; ?>>
                            <?php echo $uf; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </section>

    <!-- Seção: Dados Fiscais e Comerciais -->
    <section class="form-section">
        <h2 class="form-section-title">
            <i class="fas fa-file-invoice section-icon"></i>
            Dados Fiscais e Comerciais
        </h2>
        <div class="form-grid form-grid-3">
            <div class="form-group">
                <label for="inscricao_estadual" class="form-label">Inscrição Estadual</label>
                <input type="text" name="inscricao_estadual" id="inscricao_estadual" class="form-control"
                    placeholder="000.000.000.000"
                    value="<?php echo htmlspecialchars($fornecedor->inscricao_estadual ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="inscricao_municipal" class="form-label">Inscrição Municipal</label>
                <input type="text" name="inscricao_municipal" id="inscricao_municipal" class="form-control"
                    placeholder="000000000"
                    value="<?php echo htmlspecialchars($fornecedor->inscricao_municipal ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="prazo_pagamento" class="form-label">Prazo de Pagamento (dias)</label>
                <input type="number" name="prazo_pagamento" id="prazo_pagamento" class="form-control"
                    placeholder="30" min="0" max="365"
                    value="<?php echo htmlspecialchars($fornecedor->prazo_pagamento ?? ''); ?>">
            </div>
        </div>
        <div class="form-grid form-grid-2 mt-3">
            <div class="form-group">
                <label for="cnae_principal" class="form-label">CNAE Principal</label>
                <input type="text" name="cnae_principal" id="cnae_principal" class="form-control"
                    placeholder="0000-0/00"
                    value="<?php echo htmlspecialchars($fornecedor->cnae_principal ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="descricao_cnae" class="form-label">Descrição do CNAE</label>
                <input type="text" name="descricao_cnae" id="descricao_cnae" class="form-control"
                    placeholder="Atividade principal da empresa"
                    value="<?php echo htmlspecialchars($fornecedor->descricao_cnae ?? ''); ?>">
            </div>
        </div>
        <div class="form-group mt-3">
            <label for="observacoes" class="form-label">Observações</label>
            <textarea name="observacoes" id="observacoes" class="form-control" rows="3"
                placeholder="Informações adicionais sobre o fornecedor..."><?php echo htmlspecialchars($fornecedor->observacoes ?? ''); ?></textarea>
        </div>
    </section>

</form>

<script>
document.addEventListener('DOMContentLoaded', function () {

    // ─── Tipo de pessoa: ajusta label e placeholder do documento ─────────────
    var tipoSelect  = document.getElementById('tipo');
    var docInput    = document.getElementById('documento');
    var labelDoc    = document.getElementById('label_documento');
    var docHelp     = document.getElementById('doc_help');
    var btnConsulta = document.getElementById('btn_consulta_cnpj');

    function atualizarTipo() {
        var tipo = tipoSelect ? tipoSelect.value : 'PJ';
        if (tipo === 'PF') {
            if (labelDoc)    labelDoc.textContent   = 'CPF';
            if (docInput)    docInput.placeholder   = '000.000.000-00';
            if (docHelp)     docHelp.textContent    = 'CPF da pessoa física';
            if (btnConsulta) btnConsulta.title       = 'Buscar dados do CPF';
        } else {
            if (labelDoc)    labelDoc.textContent   = 'CNPJ';
            if (docInput)    docInput.placeholder   = '00.000.000/0000-00';
            if (docHelp)     docHelp.textContent    = 'Digite o CNPJ e clique em buscar para preencher automaticamente';
            if (btnConsulta) btnConsulta.title       = 'Buscar dados do CNPJ';
        }
    }

    if (tipoSelect) {
        tipoSelect.addEventListener('change', atualizarTipo);
        atualizarTipo();
    }

    // ─── Alerta inline ────────────────────────────────────────────────────────
    function mostrarAlerta(tipo, mensagem) {
        var alerta = document.getElementById('cnpj_alerta');
        if (!alerta) return;
        alerta.className = 'alert alert-' + tipo + ' mt-2';
        alerta.textContent = mensagem;
        alerta.classList.remove('d-none');
        setTimeout(function () { alerta.classList.add('d-none'); }, 6000);
    }

    // ─── Preenche campos com dados retornados pela API ────────────────────────
    function preencherCampos(dados) {
        var mapa = {
            'nome':           dados.razao_social    || '',
            'nome_fantasia':  dados.nome_fantasia   || '',
            'email':          dados.email           || '',
            'telefone':       dados.telefone        || '',
            'cep':            dados.cep             || '',
            'endereco':       dados.endereco        || '',
            'numero':         dados.numero          || '',
            'complemento':    dados.complemento     || '',
            'bairro':         dados.bairro          || '',
            'cidade':         dados.cidade          || '',
            'cnae_principal': dados.cnae_principal  || '',
            'descricao_cnae': dados.descricao_cnae  || ''
        };
        Object.keys(mapa).forEach(function (id) {
            var el = document.getElementById(id);
            if (el && mapa[id] !== '') el.value = mapa[id];
        });
        // UF (select)
        if (dados.estado) {
            var estadoSel = document.getElementById('estado');
            if (estadoSel) {
                for (var i = 0; i < estadoSel.options.length; i++) {
                    if (estadoSel.options[i].value === dados.estado) {
                        estadoSel.selectedIndex = i;
                        break;
                    }
                }
            }
        }
    }

    // ─── Consulta CNPJ/CPF ────────────────────────────────────────────────────
    if (btnConsulta) {
        btnConsulta.addEventListener('click', function () {
            var doc = (docInput ? docInput.value : '').replace(/\D/g, '');
            if (doc.length < 11) {
                mostrarAlerta('warning', 'Digite um CNPJ (14 dígitos) ou CPF (11 dígitos) válido antes de buscar.');
                return;
            }
            btnConsulta.disabled = true;
            btnConsulta.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            fetch('/fornecedores/buscar-cnpj?cnpj=' + encodeURIComponent(doc))
                .then(function (r) { return r.json(); })
                .then(function (dados) {
                    if (dados.erro) {
                        mostrarAlerta('danger', dados.erro);
                    } else {
                        preencherCampos(dados);
                        mostrarAlerta('success', 'Dados importados com sucesso! Revise e salve o formulário.');
                    }
                })
                .catch(function () {
                    mostrarAlerta('danger', 'Erro de comunicação ao consultar o CNPJ. Tente novamente.');
                })
                .finally(function () {
                    btnConsulta.disabled = false;
                    btnConsulta.innerHTML = '<i class="fas fa-search"></i>';
                });
        });
    }

    // ─── Busca de CEP ─────────────────────────────────────────────────────────
    var btnCep  = document.getElementById('btn_buscar_cep');
    var cepInput = document.getElementById('cep');

    function buscarCep(cep) {
        cep = cep.replace(/\D/g, '');
        if (cep.length !== 8) return;
        if (btnCep) {
            btnCep.disabled = true;
            btnCep.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        }
        fetch('/fornecedores/buscar-cep?cep=' + encodeURIComponent(cep))
            .then(function (r) { return r.json(); })
            .then(function (dados) {
                if (dados.erro) {
                    mostrarAlerta('warning', 'CEP não encontrado: ' + dados.erro);
                } else {
                    var camposCep = {
                        'endereco': dados.logradouro || dados.endereco || '',
                        'bairro':   dados.bairro     || '',
                        'cidade':   dados.cidade     || dados.localidade || ''
                    };
                    Object.keys(camposCep).forEach(function (id) {
                        var el = document.getElementById(id);
                        if (el && camposCep[id] !== '') el.value = camposCep[id];
                    });
                    var uf = dados.estado || dados.uf || '';
                    if (uf) {
                        var estadoSel = document.getElementById('estado');
                        if (estadoSel) {
                            for (var i = 0; i < estadoSel.options.length; i++) {
                                if (estadoSel.options[i].value === uf) {
                                    estadoSel.selectedIndex = i;
                                    break;
                                }
                            }
                        }
                    }
                    var numEl = document.getElementById('numero');
                    if (numEl) numEl.focus();
                }
            })
            .catch(function () {
                mostrarAlerta('danger', 'Erro ao buscar o CEP. Tente novamente.');
            })
            .finally(function () {
                if (btnCep) {
                    btnCep.disabled = false;
                    btnCep.innerHTML = '<i class="fas fa-map-pin"></i>';
                }
            });
    }

    if (btnCep) {
        btnCep.addEventListener('click', function () {
            buscarCep(cepInput ? cepInput.value : '');
        });
    }

    if (cepInput) {
        cepInput.addEventListener('blur', function () {
            var cep = this.value.replace(/\D/g, '');
            if (cep.length === 8) buscarCep(cep);
        });
    }
});
</script>
