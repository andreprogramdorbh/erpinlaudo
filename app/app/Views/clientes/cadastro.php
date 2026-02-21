<?php require_once dirname(__DIR__) . '/layout/erp_header.php'; ?>

<div class="main-content">
    <div class="container-fluid">
        <div class="header-body">
            <div class="row align-items-center py-4">
                <div class="col-lg-6 col-7">
                    <h6 class="h2 text-white d-inline-block mb-0"><?php echo $cliente ? 'Editar Cliente' : 'Novo Cliente'; ?></h6>
                    <nav aria-label="breadcrumb" class="d-none d-md-inline-block ml-md-4">
                        <ol class="breadcrumb breadcrumb-links breadcrumb-dark">
                            <li class="breadcrumb-item"><a href="/dashboard"><i class="fas fa-home"></i></a></li>
                            <li class="breadcrumb-item"><a href="/clientes">Clientes</a></li>
                            <li class="breadcrumb-item active" aria-current="page"><?php echo $cliente ? 'Editar' : 'Novo'; ?></li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body">
                        <form id="formCliente" method="POST">
                            <div class="form-group mb-3">
                                <label for="tipo" class="form-label">Tipo de Cliente</label>
                                <select class="form-control" id="tipo" name="tipo" required>
                                    <option value="PJ" <?php echo ($cliente && $cliente->tipo === 'PJ') ? 'selected' : 'selected'; ?>>Pessoa Jurídica (PJ)</option>
                                    <option value="PF" <?php echo ($cliente && $cliente->tipo === 'PF') ? 'selected' : ''; ?>>Pessoa Física (PF)</option>
                                </select>
                            </div>

                            <div class="form-group mb-3">
                                <label for="cpf_cnpj" class="form-label">CPF/CNPJ <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="cpf_cnpj" name="cpf_cnpj" placeholder="00.000.000/0000-00" 
                                           value="<?php echo $cliente ? htmlspecialchars($cliente->cpf_cnpj) : ''; ?>" required>
                                    <button class="btn btn-outline-secondary" type="button" id="btnBuscarCnpj" style="display: none;">
                                        <i class="fas fa-search"></i> Buscar
                                    </button>
                                </div>
                                <small class="form-text text-muted">Digite um CNPJ válido para buscar dados automaticamente</small>
                                <div id="errosCpfCnpj" class="invalid-feedback d-block" style="display: none;"></div>
                            </div>

                            <div class="form-group mb-3">
                                <label for="razao_social" class="form-label">Razão Social <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="razao_social" name="razao_social" placeholder="Nome da Empresa"
                                       value="<?php echo $cliente ? htmlspecialchars($cliente->razao_social) : ''; ?>" required>
                                <div id="errosRazaoSocial" class="invalid-feedback d-block" style="display: none;"></div>
                            </div>

                            <div class="form-group mb-3">
                                <label for="nome_fantasia" class="form-label">Nome Fantasia</label>
                                <input type="text" class="form-control" id="nome_fantasia" name="nome_fantasia" placeholder="Nome Fantasia (opcional)"
                                       value="<?php echo $cliente ? htmlspecialchars($cliente->nome_fantasia) : ''; ?>">
                            </div>

                            <div class="form-group mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" placeholder="contato@empresa.com.br"
                                       value="<?php echo $cliente ? htmlspecialchars($cliente->email) : ''; ?>">
                                <div id="errosEmail" class="invalid-feedback d-block" style="display: none;"></div>
                            </div>

                            <div class="form-group mb-3">
                                <label for="cnae_principal" class="form-label">CNAE Principal</label>
                                <input type="text" class="form-control" id="cnae_principal" name="cnae_principal" placeholder="Ex: 6209-1/00"
                                       value="<?php echo $cliente ? htmlspecialchars($cliente->cnae_principal) : ''; ?>" readonly>
                            </div>

                            <div class="form-group mb-3">
                                <label for="descricao_cnae" class="form-label">Descrição CNAE</label>
                                <textarea class="form-control" id="descricao_cnae" name="descricao_cnae" rows="3" readonly><?php echo $cliente ? htmlspecialchars($cliente->descricao_cnae) : ''; ?></textarea>
                            </div>

                            <?php if ($cliente): ?>
                            <div class="form-group mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-control" id="status" name="status">
                                    <option value="ativo" <?php echo $cliente->status === 'ativo' ? 'selected' : ''; ?>>Ativo</option>
                                    <option value="inativo" <?php echo $cliente->status === 'inativo' ? 'selected' : ''; ?>>Inativo</option>
                                </select>
                            </div>
                            <?php endif; ?>

                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> <?php echo $cliente ? 'Atualizar' : 'Cadastrar'; ?>
                                </button>
                                <a href="/clientes" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Cancelar
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card bg-light">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-info-circle"></i> Informações
                        </h5>
                        <p class="card-text">
                            <strong>Preenchimento Automático:</strong><br>
                            Digite um CNPJ válido (14 dígitos) e clique em "Buscar" para preencher automaticamente os dados da empresa através da API BrasilAPI.
                        </p>
                        <hr>
                        <p class="card-text">
                            <strong>Campos Obrigatórios:</strong>
                            <ul class="mb-0">
                                <li>Razão Social</li>
                                <li>CPF/CNPJ</li>
                            </ul>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="/public/assets/js/clientes-form.js"></script>

<?php require_once dirname(__DIR__) . '/layout/erp_footer.php'; ?>
