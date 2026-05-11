<?php
use App\Core\UI;
use App\Core\Auth;

$actions = [];
if (Auth::can('create_colaboradores')) {
    $actions[] = [
        'text'  => 'Novo Colaborador',
        'link'  => '/colaboradores/create',
        'icon'  => 'fas fa-plus',
        'class' => 'btn-primary'
    ];
}
UI::sectionHeader('Colaboradores', 'Gerencie os colaboradores CLT e PJ da sua empresa', $actions);

// Mensagens de feedback
$success = $_GET['success'] ?? '';
$error   = $_GET['error']   ?? '';
$msgs = [
    'created' => ['success', 'Colaborador cadastrado com sucesso.'],
    'updated' => ['success', 'Colaborador atualizado com sucesso.'],
    'deleted' => ['success', 'Colaborador excluído com sucesso.'],
];
$errs = [
    'cpf_cnpj_exists' => 'CPF/CNPJ já cadastrado para outro colaborador.',
    'create_failed'   => 'Falha ao criar colaborador. Tente novamente.',
    'update_failed'   => 'Falha ao atualizar colaborador.',
    'unauthorized'    => 'Você não tem permissão para esta ação.',
];
if ($success && isset($msgs[$success])):
    [$type, $msg] = $msgs[$success]; ?>
    <div class="alert alert-<?php echo $type; ?> alert-dismissible fade show mb-3" role="alert">
        <i class="fas fa-check-circle me-2"></i><?php echo $msg; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif;
if ($error && isset($errs[$error])): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i><?php echo $errs[$error]; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Filtros -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4">
        <form method="GET" action="/colaboradores" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label small fw-bold text-muted">Pesquisar</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" name="q" class="form-control border-start-0"
                        placeholder="Nome, CPF/CNPJ, cargo ou e-mail..."
                        value="<?php echo htmlspecialchars($filtros['pesquisa'] ?? ''); ?>">
                </div>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted">Tipo</label>
                <select name="tipo" class="form-select">
                    <option value="">Todos</option>
                    <option value="CLT" <?php echo ($filtros['tipo_contratacao'] ?? '') === 'CLT' ? 'selected' : ''; ?>>CLT</option>
                    <option value="PJ"  <?php echo ($filtros['tipo_contratacao'] ?? '') === 'PJ'  ? 'selected' : ''; ?>>PJ</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted">Status</label>
                <select name="status" class="form-select">
                    <option value=""        <?php echo ($filtros['status'] ?? '') === ''        ? 'selected' : ''; ?>>Todos</option>
                    <option value="ativo"   <?php echo ($filtros['status'] ?? '') === 'ativo'   ? 'selected' : ''; ?>>Ativo</option>
                    <option value="inativo" <?php echo ($filtros['status'] ?? '') === 'inativo' ? 'selected' : ''; ?>>Inativo</option>
                    <option value="afastado"<?php echo ($filtros['status'] ?? '') === 'afastado'? 'selected' : ''; ?>>Afastado</option>
                    <option value="demitido"<?php echo ($filtros['status'] ?? '') === 'demitido'? 'selected' : ''; ?>>Demitido</option>
                </select>
            </div>
            <div class="col-md-2 d-grid">
                <button type="submit" class="btn btn-primary fw-bold">
                    <i class="fas fa-filter me-1"></i> Filtrar
                </button>
            </div>
            <div class="col-md-2 d-grid">
                <a href="/colaboradores" class="btn btn-outline-secondary">
                    <i class="fas fa-times me-1"></i> Limpar
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Listagem -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <?php
        $statusLabels = [
            'ativo'    => '<span class="badge bg-success">Ativo</span>',
            'inativo'  => '<span class="badge bg-secondary">Inativo</span>',
            'afastado' => '<span class="badge bg-warning text-dark">Afastado</span>',
            'demitido' => '<span class="badge bg-danger">Demitido</span>',
        ];
        $tipoLabels = [
            'CLT' => '<span class="badge bg-primary">CLT</span>',
            'PJ'  => '<span class="badge bg-info text-dark">PJ</span>',
        ];
        $headers     = ['Colaborador', 'CPF / CNPJ', 'Cargo', 'Tipo', 'Status', 'Ações'];
        $rowRenderer = function ($col) use ($statusLabels, $tipoLabels) {
            $nome   = htmlspecialchars($col->nome ?? '');
            $email  = htmlspecialchars($col->email ?? '');
            $doc    = htmlspecialchars($col->cpf_cnpj ?? '');
            $cargo  = htmlspecialchars($col->cargo ?? '—');
            $tipo   = $tipoLabels[$col->tipo_contratacao] ?? $col->tipo_contratacao;
            $status = $statusLabels[$col->status] ?? $col->status;
            $acoes  = '';
            if (Auth::can('edit_colaboradores')) {
                $acoes .= '<a href="/colaboradores/edit/' . (int)$col->id . '" class="text-primary me-2" title="Editar"><i class="fas fa-edit"></i></a>';
            }
            if (Auth::can('delete_colaboradores')) {
                $acoes .= '<a href="/colaboradores/delete/' . (int)$col->id . '" class="text-danger"
                              title="Excluir"
                              onclick="return confirm(\'Excluir ' . addslashes($nome) . '?\')">
                              <i class="fas fa-trash"></i></a>';
            }
            return '<tr>'
                . '<td><strong>' . $nome . '</strong><br><small class="text-muted">' . $email . '</small></td>'
                . '<td>' . $doc . '</td>'
                . '<td>' . $cargo . '</td>'
                . '<td>' . $tipo . '</td>'
                . '<td>' . $status . '</td>'
                . '<td class="text-end">' . $acoes . '</td>'
                . '</tr>';
        };
        UI::render('table', [
            'headers'      => $headers,
            'items'        => $colaboradores,
            'rowRenderer'  => $rowRenderer,
            'emptyMessage' => 'Nenhum colaborador encontrado.',
        ]);
        ?>
    </div>
</div>
