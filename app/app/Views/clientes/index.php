<?php require_once dirname(__DIR__) . '/layout/erp_header.php'; ?>

<div class="main-content">
    <div class="container-fluid">
        <div class="header-body">
            <div class="row align-items-center py-4">
                <div class="col-lg-6 col-7">
                    <h6 class="h2 text-white d-inline-block mb-0">Clientes</h6>
                    <nav aria-label="breadcrumb" class="d-none d-md-inline-block ml-md-4">
                        <ol class="breadcrumb breadcrumb-links breadcrumb-dark">
                            <li class="breadcrumb-item"><a href="/dashboard"><i class="fas fa-home"></i></a></li>
                            <li class="breadcrumb-item active" aria-current="page">Clientes</li>
                        </ol>
                    </nav>
                </div>
                <div class="col-lg-6 col-5 text-right">
                    <a href="/clientes/create" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus"></i> Novo Cliente
                    </a>
                </div>
            </div>
        </div>

        <!-- Cards de Estatísticas -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card card-stats">
                    <div class="card-body">
                        <div class="row">
                            <div class="col">
                                <h5 class="card-title text-uppercase text-muted mb-0">Total de Clientes</h5>
                                <span class="h2 font-weight-bold mb-0"><?php echo htmlspecialchars($totalClientes ?? 0); ?></span>
                            </div>
                            <div class="col-auto">
                                <div class="icon icon-shape bg-gradient-info text-white rounded-circle shadow">
                                    <i class="fas fa-users"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-stats">
                    <div class="card-body">
                        <div class="row">
                            <div class="col">
                                <h5 class="card-title text-uppercase text-muted mb-0">Clientes Ativos</h5>
                                <span class="h2 font-weight-bold mb-0"><?php echo count($clientes ?? []); ?></span>
                            </div>
                            <div class="col-auto">
                                <div class="icon icon-shape bg-gradient-green text-white rounded-circle shadow">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabela de Clientes -->
        <div class="card">
            <div class="card-header border-0">
                <h3 class="mb-0">Listagem de Clientes</h3>
            </div>
            <div class="table-responsive">
                <?php if (!empty($clientes)): ?>
                <table class="table table-sm table-hover align-items-center mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th scope="col">Razão Social</th>
                            <th scope="col">CPF/CNPJ</th>
                            <th scope="col">Email</th>
                            <th scope="col">Tipo</th>
                            <th scope="col">Status</th>
                            <th scope="col">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clientes as $cliente): ?>
                        <tr>
                            <td>
                                <span class="mb-0 text-sm font-weight-bold">
                                    <?php echo htmlspecialchars($cliente->razao_social); ?>
                                </span>
                                <?php if (!empty($cliente->nome_fantasia)): ?>
                                <br><small class="text-muted"><?php echo htmlspecialchars($cliente->nome_fantasia); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge badge-light"><?php echo htmlspecialchars($cliente->cpf_cnpj); ?></span>
                            </td>
                            <td>
                                <?php if (!empty($cliente->email)): ?>
                                <a href="mailto:<?php echo htmlspecialchars($cliente->email); ?>">
                                    <?php echo htmlspecialchars($cliente->email); ?>
                                </a>
                                <?php else: ?>
                                <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge <?php echo $cliente->tipo === 'PJ' ? 'badge-primary' : 'badge-info'; ?>">
                                    <?php echo $cliente->tipo === 'PJ' ? 'Pessoa Jurídica' : 'Pessoa Física'; ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge <?php echo $cliente->status === 'ativo' ? 'badge-success' : 'badge-danger'; ?>">
                                    <?php echo ucfirst($cliente->status); ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="/clientes/edit/<?php echo $cliente->id; ?>" class="btn btn-sm btn-info" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-danger" onclick="confirmarDelecao(<?php echo $cliente->id; ?>, '<?php echo htmlspecialchars($cliente->razao_social); ?>')" title="Deletar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="card-body text-center py-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Nenhum cliente cadastrado</h5>
                    <p class="text-muted">Comece criando um novo cliente clicando no botão acima.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Script para ações da tabela -->
<script>
function confirmarDelecao(clienteId, razaoSocial) {
    if (confirm('Tem certeza que deseja deletar o cliente ' + razaoSocial + '?')) {
        fetch('/clientes/delete/' + clienteId, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.sucesso) {
                alert(data.mensagem);
                location.reload();
            } else {
                alert('Erro: ' + (data.erro || 'Erro ao deletar cliente'));
            }
        })
        .catch(error => {
            alert('Erro ao comunicar com o servidor: ' + error.message);
        });
    }
}
</script>

<?php require_once dirname(__DIR__) . '/layout/erp_footer.php'; ?>
