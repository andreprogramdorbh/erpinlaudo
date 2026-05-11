<?php
/**
 * Aba: Anexos do Colaborador
 */
use App\Core\UI;
$colaboradorId = $colaborador->id ?? null;
$anexos        = $anexos ?? [];
?>
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom py-3">
        <h5 class="mb-0 fw-bold"><i class="fas fa-paperclip text-primary me-2"></i>Enviar Novo Anexo</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="/colaboradores/anexos/add" enctype="multipart/form-data" class="row g-3 align-items-end">
            <input type="hidden" name="colaborador_id" value="<?php echo (int)$colaboradorId; ?>">
            <div class="col-md-4">
                <label class="form-label fw-bold">Nome / Descrição do Documento <span class="text-danger">*</span></label>
                <input type="text" name="nome_anexo" class="form-control" required
                    placeholder="Ex: Contrato de Trabalho, CNH, Diploma...">
            </div>
            <div class="col-md-5">
                <label class="form-label fw-bold">Arquivo (PDF, Imagens, Word, etc. — máx. 10 MB)</label>
                <input type="file" name="arquivo" class="form-control" required>
            </div>
            <div class="col-md-3 d-grid">
                <button type="submit" class="btn btn-primary fw-bold">
                    <i class="fas fa-upload me-1"></i> Enviar Anexo
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom py-3">
        <h5 class="mb-0 fw-bold"><i class="fas fa-folder-open text-primary me-2"></i>Documentos Anexados</h5>
    </div>
    <div class="card-body p-0">
        <?php
        $headers     = ['Nome / Descrição', 'Arquivo Original', 'Tamanho', 'Data', 'Ações'];
        $rowRenderer = function ($a) {
            $nome     = htmlspecialchars($a->nome_anexo    ?? 'Sem nome');
            $original = htmlspecialchars($a->original_name ?? '');
            $size     = (int)($a->file_size ?? 0);
            $sizeText = $size > 0 ? number_format($size / 1024, 0, ',', '.') . ' KB' : '—';
            $date     = date('d/m/Y H:i', strtotime($a->created_at));
            $dl  = '<a class="btn btn-sm btn-outline-primary me-2" href="/colaboradores/anexos/download/' . (int)$a->id . '" title="Baixar"><i class="fas fa-download"></i></a>';
            $del = '<button class="btn btn-sm btn-outline-danger" onclick="removerAnexo(' . (int)$a->id . ')" title="Remover"><i class="fas fa-trash"></i></button>';
            return '<tr>'
                . '<td><strong>' . $nome . '</strong></td>'
                . '<td class="text-muted small">' . $original . '</td>'
                . '<td>' . $sizeText . '</td>'
                . '<td>' . $date . '</td>'
                . '<td class="text-end">' . $dl . $del . '</td>'
                . '</tr>';
        };
        UI::render('table', [
            'headers'      => $headers,
            'items'        => $anexos,
            'rowRenderer'  => $rowRenderer,
            'emptyMessage' => 'Nenhum anexo cadastrado para este colaborador.',
        ]);
        ?>
    </div>
</div>

<script>
function removerAnexo(id) {
    if (!confirm('Remover este anexo permanentemente?')) return;
    fetch('/colaboradores/anexos/remove', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id=' + id
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) { window.location.reload(); }
        else { alert('Erro: ' + (data.error || 'Erro desconhecido')); }
    })
    .catch(() => alert('Erro ao processar requisição.'));
}
</script>
