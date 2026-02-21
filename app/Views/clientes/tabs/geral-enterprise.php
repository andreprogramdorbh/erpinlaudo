<?php
/**
 * ERP InLaudo - Aba Geral do Formulário de Clientes (Enterprise Layout)
 * Conteúdo da aba de dados gerais e endereço
 */

$action = $isEdit ? '/clientes/update/' . ($cliente->id ?? '') : '/clientes';
?>

<form id="clienteFormGeral" action="<?php echo $action; ?>" method="POST" class="enterprise-form-main">

    <!-- Seção: Identificação e Documentos -->
    <section class="form-section">
        <h2 class="form-section-title">
            <i class="fas fa-id-card section-icon"></i>
            Identificação e Documentos
        </h2>

        <div class="form-grid form-grid-3">
            <div class="form-group">
                <label for="tipo" class="form-label">Tipo de Cliente</label>
                <select name="tipo" id="tipo" class="form-select">
                    <option value="PJ" <?php echo ($cliente->tipo ?? 'PJ') == 'PJ' ? 'selected' : ''; ?>>Pessoa Jurídica
                        (CNPJ)</option>
                    <option value="PF" <?php echo ($cliente->tipo ?? '') == 'PF' ? 'selected' : ''; ?>>Pessoa Física (CPF)
                    </option>
                </select>
                <small class="text-muted" id="tipo_help">Selecione o tipo de identificação</small>
            </div>

            <div class="form-group">
                <label for="cpf_cnpj" class="form-label required">CNPJ</label>
                <div class="input-group">
                    <input type="text" name="cpf_cnpj" id="cpf_cnpj" class="form-control"
                        placeholder="00.000.000/0000-00"
                        value="<?php echo htmlspecialchars($cliente->cpf_cnpj ?? ''); ?>" required>
                    <button type="button" class="btn btn-primary" id="btn_consulta">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>

            <div class="form-group">
                <label for="status" class="form-label">Status</label>
                <select name="status" id="status" class="form-select">
                    <option value="ativo" <?php echo ($cliente->status ?? 'ativo') == 'ativo' ? 'selected' : ''; ?>>
                        Ativo
                    </option>
                    <option value="inativo" <?php echo ($cliente->status ?? '') == 'inativo' ? 'selected' : ''; ?>>
                        Inativo
                    </option>
                </select>
            </div>
        </div>
    </section>

    <!-- Seção: Dados Principais -->
    <section class="form-section">
        <h2 class="form-section-title">
            <i class="fas fa-building section-icon"></i>
            Dados Principais
        </h2>

        <div class="form-grid form-grid-2">
            <div class="form-group">
                <label for="razao_social" class="form-label required">Razão Social</label>
                <input type="text" name="razao_social" id="razao_social" class="form-control"
                    placeholder="Nome completo da empresa"
                    value="<?php echo htmlspecialchars($cliente->razao_social ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="nome_fantasia" class="form-label">Nome Fantasia</label>
                <input type="text" name="nome_fantasia" id="nome_fantasia" class="form-control"
                    placeholder="Nome comercial (opcional)"
                    value="<?php echo htmlspecialchars($cliente->nome_fantasia ?? ''); ?>">
            </div>
        </div>

        <div class="form-grid form-grid-2 mt-3">
            <div class="form-group">
                <label for="email" class="form-label">E-mail Principal</label>
                <input type="email" name="email" id="email" class="form-control" placeholder="contato@empresa.com.br"
                    value="<?php echo htmlspecialchars($cliente->email ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="telefone" class="form-label">Telefone Principal</label>
                <input type="text" name="telefone" id="telefone" class="form-control" placeholder="(00) 0000-0000"
                    value="<?php echo htmlspecialchars($cliente->telefone ?? ''); ?>">
            </div>
        </div>
    </section>

    <!-- Seção: Localização e Endereço -->
    <section class="form-section">
        <h2 class="form-section-title">
            <i class="fas fa-map-marker-alt section-icon"></i>
            Localização / Endereço
        </h2>

        <div class="form-grid form-grid-3">
            <div class="form-group">
                <label for="cep" class="form-label">CEP</label>
                <input type="text" name="cep" id="cep" class="form-control" placeholder="00000-000"
                    value="<?php echo htmlspecialchars($cliente->cep ?? ''); ?>">
            </div>

            <div class="form-group col-span-2">
                <label for="endereco" class="form-label">Endereço</label>
                <input type="text" name="endereco" id="endereco" class="form-control" placeholder="Rua, Avenida, etc."
                    value="<?php echo htmlspecialchars($cliente->endereco ?? ''); ?>">
            </div>
        </div>

        <div class="form-grid form-grid-4 mt-3">
            <div class="form-group">
                <label for="numero" class="form-label">Número</label>
                <input type="text" name="numero" id="numero" class="form-control" placeholder="123"
                    value="<?php echo htmlspecialchars($cliente->numero ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="complemento" class="form-label">Complemento</label>
                <input type="text" name="complemento" id="complemento" class="form-control"
                    placeholder="Sala, Apartamento, etc."
                    value="<?php echo htmlspecialchars($cliente->complemento ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="bairro" class="form-label">Bairro</label>
                <input type="text" name="bairro" id="bairro" class="form-control" placeholder="Centro"
                    value="<?php echo htmlspecialchars($cliente->bairro ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="cidade" class="form-label">Cidade</label>
                <input type="text" name="cidade" id="cidade" class="form-control" placeholder="São Paulo"
                    value="<?php echo htmlspecialchars($cliente->cidade ?? ''); ?>">
            </div>
        </div>

        <div class="form-grid form-grid-4 mt-3">
            <div class="form-group">
                <label for="estado" class="form-label">UF</label>
                <select name="estado" id="estado" class="form-select">
                    <option value="">Selecione...</option>
                    <?php
                    $ufs = ['AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'];
                    foreach ($ufs as $uf): ?>
                        <option value="<?php echo $uf; ?>" <?php echo ($cliente->estado ?? '') == $uf ? 'selected' : ''; ?>>
                            <?php echo $uf; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="celular" class="form-label">Celular</label>
                <input type="text" name="celular" id="celular" class="form-control" placeholder="(00) 00000-0000"
                    value="<?php echo htmlspecialchars($cliente->celular ?? ''); ?>">
            </div>
        </div>
    </section>

    <!-- Campos ocultos adicionais -->
    <input type="hidden" name="website" value="<?php echo htmlspecialchars($cliente->website ?? ''); ?>">
    <input type="hidden" name="instagram" value="<?php echo htmlspecialchars($cliente->instagram ?? ''); ?>">
    <input type="hidden" name="tiktok" value="<?php echo htmlspecialchars($cliente->tiktok ?? ''); ?>">
    <input type="hidden" name="facebook" value="<?php echo htmlspecialchars($cliente->facebook ?? ''); ?>">
    <input type="hidden" name="cnae_principal" value="<?php echo htmlspecialchars($cliente->cnae_principal ?? ''); ?>">
    <input type="hidden" name="descricao_cnae" value="<?php echo htmlspecialchars($cliente->descricao_cnae ?? ''); ?>">

</form>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Implementar consulta de CNPJ via AJAX aqui se necessário
    });
</script>