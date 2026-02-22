<?php
/**
 * ERP InLaudo - Aba Anexos do Formulário de Clientes (Enterprise Layout)
 */

use App\Core\UI;

$clienteId = $cliente->id ?? null;
$anexos = $anexos ?? [];
?>

<section class="form-section">
    <h2 class="form-section-title">
        <i class="fas fa-paperclip section-icon"></i>
        Anexos e Documentos
    </h2>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="POST" action="/clientes/anexos/add" enctype="multipart/form-data" class="row g-3 align-items-end">
                <input type="hidden" name="cliente_id" value="<?php echo (int)$clienteId; ?>">

                <div class="col-md-8">
                    <label class="form-label small fw-bold text-muted">Arquivo (PDF, Imagens, etc. até 5MB)</label>
                    <input type="file" name="arquivo" class="form-control" required>
                </div>

                <div class="col-md-4 d-grid">
                    <button type="submit" class="btn btn-primary fw-bold">
                        <i class="fas fa-upload me-1"></i> Enviar Arquivo
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <?php
            $headers = ['Arquivo', 'Tipo', 'Tamanho', 'Data', 'Ações'];

            $rowRenderer = function ($a) {
                $name = htmlspecialchars($a->original_name ?? 'anexo');
                $mime = htmlspecialchars($a->mime_type ?? '');
                $size = (int)($a->file_size ?? 0);
                $sizeText = $size > 0 ? number_format($size / 1024, 0, ',', '.') . ' KB' : '';
                $date = date('d/m/Y H:i', strtotime($a->created_at));

                $download = '<a class="btn btn-sm btn-outline-primary me-2" href="/clientes/anexos/download/' . (int)$a->id . '" title="Baixar"><i class="fas fa-download"></i></a>';
                $del = '<button class="btn btn-sm btn-outline-danger" title="Remover" onclick="removeClienteAnexo(' . (int)$a->id . ')"><i class="fas fa-trash"></i></button>';

                return '<tr>'
                    . '<td><strong>' . $name . '</strong></td>'
                    . '<td>' . $mime . '</td>'
                    . '<td>' . $sizeText . '</td>'
                    . '<td>' . $date . '</td>'
                    . '<td class="text-end">' . $download . $del . '</td>'
                    . '</tr>';
            };

            UI::render('table', [
                'headers' => $headers,
                'items' => $anexos,
                'rowRenderer' => $rowRenderer,
                'emptyMessage' => 'Nenhum anexo encontrado para este cliente.',
            ]);
            ?>
        </div>
    </div>
</section>

<script>
function removeClienteAnexo(id) {
    if (!confirm('Tem certeza que deseja remover este anexo?')) return;

    fetch('/clientes/anexos/remove', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id=' + id
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            alert('Erro ao remover: ' + (data.error || 'Erro desconhecido'));
        }
    })
    .catch(err => {
        console.error(err);
        alert('Erro ao processar requisição.');
    });
}
</script>
