<?php
use App\Core\UI;

$notaId = $nota->id ?? null;
$anexos = $anexos ?? [];
?>
<section class="form-section">
    <h2 class="form-section-title">
        <i class="fas fa-paperclip section-icon"></i>
        Anexos
    </h2>

    <?php if ($notaId): ?>
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="POST"
                  action="/faturamento/notas-fiscais/anexos/upload"
                  enctype="multipart/form-data"
                  class="row g-3 align-items-end"
                  id="formUploadAnexoNF">
                <input type="hidden" name="nota_fiscal_id" value="<?php echo (int)$notaId; ?>">
                <div class="col-md-8">
                    <label class="form-label small fw-bold text-muted">
                        Arquivo (PDF / XML / JPG / XLS / XLSX — máximo 10 MB)
                    </label>
                    <input type="file"
                           name="anexo"
                           id="inputAnexoNF"
                           class="form-control"
                           accept=".pdf,.xml,.jpg,.jpeg,.xls,.xlsx,.xlsm,.xlsb,.xlt,.xltx,.xltm,application/pdf,text/xml,application/xml,image/jpeg,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                           required>
                    <div class="form-text text-muted">
                        Formatos aceitos: PDF, XML, JPG, Excel. Tamanho máximo: 10 MB.
                    </div>
                </div>
                <div class="col-md-4 d-grid">
                    <button type="submit" class="btn btn-primary fw-bold" id="btnEnviarAnexoNF">
                        <i class="fas fa-upload me-1"></i> Enviar Anexo
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <?php
            $headers = ['Arquivo', 'Tipo', 'Tamanho', 'Enviado em', 'Ações'];
            $rowRenderer = function ($a) {
                $name     = htmlspecialchars($a->original_name ?? 'anexo');
                $mime     = htmlspecialchars($a->mime_type ?? '');
                $size     = (int)($a->file_size ?? 0);
                $sizeText = $size > 0 ? number_format($size / 1024, 0, ',', '.') . ' KB' : '—';
                $date     = htmlspecialchars($a->created_at ?? '');

                // Ícone por tipo
                $icon = 'fa-file';
                if (str_contains($mime, 'pdf'))   $icon = 'fa-file-pdf text-danger';
                elseif (str_contains($mime, 'xml')) $icon = 'fa-file-code text-warning';
                elseif (str_contains($mime, 'image')) $icon = 'fa-file-image text-info';

                $download = '<a class="btn btn-sm btn-outline-primary me-1"'
                    . ' href="/faturamento/notas-fiscais/anexos/download/' . (int)$a->id . '"'
                    . ' title="Baixar"><i class="fas fa-download"></i></a>';

                $del = '<button class="btn btn-sm btn-outline-danger"'
                    . ' type="button"'
                    . ' title="Remover"'
                    . ' onclick="confirmDeleteAnexoNF(' . (int)$a->id . ')">'
                    . '<i class="fas fa-trash"></i></button>';

                return '<tr>'
                    . '<td><i class="fas ' . $icon . ' me-2"></i><strong>' . $name . '</strong></td>'
                    . '<td><span class="badge bg-light text-dark border">' . $mime . '</span></td>'
                    . '<td>' . $sizeText . '</td>'
                    . '<td>' . $date . '</td>'
                    . '<td>' . $download . $del . '</td>'
                    . '</tr>';
            };

            UI::render('table', [
                'headers'      => $headers,
                'items'        => $anexos,
                'rowRenderer'  => $rowRenderer,
                'emptyMessage' => 'Nenhum anexo enviado ainda. Utilize o formulário acima para adicionar arquivos.',
            ]);
            ?>
        </div>
    </div>
    <?php else: ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>
        Salve a nota fiscal primeiro para poder adicionar anexos.
    </div>
    <?php endif; ?>
</section>

<script>
function confirmDeleteAnexoNF(id) {
    if (confirm('Deseja remover este anexo? Esta ação não pode ser desfeita.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/faturamento/notas-fiscais/anexos/delete/' + id;
        document.body.appendChild(form);
        form.submit();
    }
}

// Validação de tamanho no lado do cliente (10 MB)
document.addEventListener('DOMContentLoaded', function () {
    const inputFile = document.getElementById('inputAnexoNF');
    const formUpload = document.getElementById('formUploadAnexoNF');

    if (inputFile && formUpload) {
        formUpload.addEventListener('submit', function (e) {
            const maxSize = 10 * 1024 * 1024; // 10 MB
            if (inputFile.files.length > 0 && inputFile.files[0].size > maxSize) {
                e.preventDefault();
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Arquivo muito grande',
                        text: 'O arquivo selecionado excede o limite de 10 MB.',
                        confirmButtonColor: '#00529B'
                    });
                } else {
                    alert('O arquivo selecionado excede o limite de 10 MB.');
                }
            }
        });
    }
});
</script>
