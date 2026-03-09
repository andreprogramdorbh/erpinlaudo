<?php
use App\Core\UI;
use App\Core\Auth;


// Header da Seção com Ação (Novo Cliente)
$actions = [];
if (Auth::can('create_clients')) {
    $actions[] = [
        'text' => 'Novo Cliente',
        'link' => '/clientes/create',
        'icon' => 'fas fa-plus',
        'class' => 'btn-primary'
    ];
}

UI::sectionHeader('Gestão de Clientes', 'Visualize e gerencie sua base de clientes e parceiros', $actions);
?>

<!-- Filtros Avançados -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4">
        <form method="GET" action="/clientes" class="row g-3 align-items-end">
            <div class="col-md-5">
                <label class="form-label small fw-bold text-muted">Pesquisar</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" name="q" class="form-control border-start-0"
                        placeholder="Nome, Razão Social ou CNPJ..."
                        value="<?php echo htmlspecialchars($filtros['pesquisa'] ?? ''); ?>">
                </div>
            </div>

            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted">Estado (UF)</label>
                <select name="uf" class="form-select">
                    <option value="">Todos os estados</option>
                    <?php
                    $ufs = ['AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'];
                    foreach ($ufs as $uf): ?>
                        <option value="<?php echo $uf; ?>" <?php echo ($filtros['uf'] ?? '') == $uf ? 'selected' : ''; ?>>
                            <?php echo $uf; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted">Status</label>
                <select name="status" class="form-select">
                    <option value="" <?php echo ($filtros['status'] ?? '') == '' ? 'selected' : ''; ?>>Todos</option>
                    <option value="ativo" <?php echo ($filtros['status'] ?? '') == 'ativo' ? 'selected' : ''; ?>>Ativo
                    </option>
                    <option value="inativo" <?php echo ($filtros['status'] ?? '') == 'inativo' ? 'selected' : ''; ?>>
                        Inativo</option>
                </select>
            </div>

            <div class="col-md-2 d-grid">
                <button type="submit" class="btn btn-primary fw-bold">
                    <i class="fas fa-filter me-1"></i> Filtrar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Listagem -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <?php
        $headers = ['Razão Social', 'CNPJ/CPF', 'Status', 'Ações'];

        $rowRenderer = function ($cliente) {
            $acoes = '';

            if (Auth::can('edit_clients')) {
                $acoes .= '<a href="/clientes/edit/' . (int) $cliente->id . '" class="text-primary me-2" title="Editar"><i class="fas fa-edit"></i></a>';
            }

            if (Auth::can('delete_clients')) {
                $acoes .= '<a href="#" class="text-danger" title="Excluir" onclick="confirmDelete(' . (int) $cliente->id . '); return false;"><i class="fas fa-trash"></i></a>';
            }

            $status = $cliente->status ?? 'ativo';
            $badge = ($status === 'ativo')
                ? '<span class="badge bg-success">Ativo</span>'
                : '<span class="badge bg-secondary">Inativo</span>';

            $razao = '<strong>' . htmlspecialchars($cliente->razao_social) . '</strong>';
            if (!empty($cliente->nome_fantasia)) {
                $razao .= '<br><small class="text-muted">' . htmlspecialchars($cliente->nome_fantasia) . '</small>';
            }

            return '<tr>'
                . '<td>' . $razao . '</td>'
                . '<td>' . htmlspecialchars($cliente->cpf_cnpj ?? '') . '</td>'
                . '<td>' . $badge . '</td>'
                . '<td>' . $acoes . '</td>'
                . '</tr>';
        };

        UI::render('table', [
            'headers' => $headers,
            'items' => $clientes ?? [],
            'rowRenderer' => $rowRenderer,
            'emptyMessage' => 'Nenhum cliente encontrado com os filtros aplicados.',
            'emptyIcon' => 'fas fa-users-slash'
        ]);
        ?>
    </div>
</div>

<script>
    function confirmDelete(id) {
        if (confirm('Deseja realmente excluir este cliente?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '/clientes/delete/' + id;
            document.body.appendChild(form);
            form.submit();
        }
    }
</script>
