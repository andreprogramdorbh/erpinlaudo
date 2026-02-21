<?php
$action = $isEdit ? '/clientes/update/' . $cliente->id : '/clientes';
?>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4 p-lg-5">
        <form id="clienteFormGeral" action="<?php echo $action; ?>" method="POST">

            <h5 class="mb-4 text-primary fw-bold"><i class="fas fa-id-card me-2"></i> Identificação e Documentos</h5>
            <div class="row g-4 mb-5">
                <div class="col-md-3">
                    <label class="form-label fw-bold small text-muted">Tipo de Cliente</label>
                    <select name="tipo" id="tipo_cliente" class="form-select bg-light" readonly>
                        <option value="PJ" selected>Pessoa Jurídica (CNPJ)</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold small text-muted" id="label_documento">Documento (CPF/CNPJ)</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i
                                class="fas fa-fingerprint text-muted"></i></span>
                        <input type="text" name="cpf_cnpj" id="cpf_cnpj" class="form-control border-start-0"
                            placeholder="00.000.000/0000-00" value="<?php echo $cliente->cpf_cnpj ?? ''; ?>" required>
                        <button class="btn btn-outline-primary fw-bold" type="button" id="btn_consulta">
                            <i class="fas fa-search me-1"></i> Consultar
                        </button>
                    </div>
                    <div class="form-text" id="status_consulta">Consulte para preencher o endereço automaticamente</div>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-bold small text-muted">Status</label>
                    <select name="status" class="form-select">
                        <option value="ativo" <?php echo ($cliente->status ?? 'ativo') == 'ativo' ? 'selected' : ''; ?>>
                            Ativo</option>
                        <option value="inativo" <?php echo ($cliente->status ?? '') == 'inativo' ? 'selected' : ''; ?>>
                            Inativo</option>
                    </select>
                </div>
            </div>

            <h5 class="mb-4 text-primary fw-bold"><i class="fas fa-building me-2"></i> Dados Principais</h5>
            <div class="row g-4 mb-5">
                <div class="col-md-6">
                    <label class="form-label fw-bold small text-muted">Nome / Razão Social</label>
                    <input type="text" name="razao_social" id="razao_social" class="form-control"
                        value="<?php echo $cliente->razao_social ?? ''; ?>" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold small text-muted">Nome Fantasia (Opcional)</label>
                    <input type="text" name="nome_fantasia" id="nome_fantasia" class="form-control"
                        value="<?php echo $cliente->nome_fantasia ?? ''; ?>">
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold small text-muted">E-mail Principal</label>
                    <input type="email" name="email" class="form-control" value="<?php echo $cliente->email ?? ''; ?>">
                </div>
            </div>

            <h5 class="mb-4 text-primary fw-bold"><i class="fas fa-map-marker-alt me-2"></i> Localização / Endereço</h5>
            <div class="row g-4 mb-4">
                <div class="col-md-2">
                    <label class="form-label fw-bold small text-muted">CEP</label>
                    <input type="text" name="cep" id="cep" class="form-control"
                        value="<?php echo $cliente->cep ?? ''; ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold small text-muted">Endereço</label>
                    <input type="text" name="endereco" id="endereco" class="form-control"
                        value="<?php echo $cliente->endereco ?? ''; ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold small text-muted">Nº</label>
                    <input type="text" name="numero" class="form-control" value="<?php echo $cliente->numero ?? ''; ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold small text-muted">UF</label>
                    <select name="estado" id="estado" class="form-select">
                        <option value="">--</option>
                        <?php
                        $ufs = ['AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'];
                        foreach ($ufs as $uf): ?>
                            <option value="<?php echo $uf; ?>" <?php echo ($cliente->estado ?? '') == $uf ? 'selected' : ''; ?>>
                                <?php echo $uf; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold small text-muted">Bairro</label>
                    <input type="text" name="bairro" id="bairro" class="form-control"
                        value="<?php echo $cliente->bairro ?? ''; ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold small text-muted">Cidade</label>
                    <input type="text" name="cidade" id="cidade" class="form-control"
                        value="<?php echo $cliente->cidade ?? ''; ?>">
                </div>
            </div>

            <div class="d-flex justify-content-end gap-3 mt-5 pt-4 border-top">
                <a href="/clientes" class="btn btn-lg btn-light px-4 fw-bold">Cancelar</a>
                <button type="submit" class="btn btn-primary btn-lg px-5 fw-bold">
                    <i class="fas fa-save me-2"></i>
                    <?php echo $isEdit ? 'Salvar e Continuar' : 'Salvar e Próxima Etapa'; ?>
                </button>
            </div>

        </form>
    </div>
</div>