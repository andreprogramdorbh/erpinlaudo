<?php

use App\Core\UI;

$contaId = $conta->id ?? null;
$anexos = $anexos ?? [];
?>

<section class="form-section">
    <h2 class="form-section-title">
        <i class="fas fa-paperclip section-icon"></i>
        Anexos
    </h2>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="POST" action="/financeiro/contas-a-pagar/anexos/upload" enctype="multipart/form-data" class="row g-3 align-items-end">
                <input type="hidden" name="conta_pagar_id" value="<?php echo (int)$contaId; ?>">

                <div class="col-md-8">
                    <label class="form-label small fw-bold text-muted">Arquivo (PDF/JPG/PNG/XLS/XLSX até 5MB)</label>
                    <input type="file" name="anexo" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.xls,.xlsx,.xlsm,.xlsb,.xlt,.xltx,.xltm,application/pdf,image/jpeg,image/png,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required>
                </div>

                <div class="col-md-4 d-grid">
                    <button type="submit" class="btn btn-primary fw-bold">
                        <i class="fas fa-upload me-1"></i> Enviar
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
                $date = htmlspecialchars($a->created_at ?? '');

                $download = '<a class="text-primary me-2" href="/financeiro/contas-a-pagar/anexos/download/' . (int)$a->id . '" title="Baixar"><i class="fas fa-download"></i></a>';
                $del = '<a class="text-danger" href="#" title="Remover" onclick="confirmDeleteAnexo(' . (int)$a->id . '); return false;"><i class="fas fa-trash"></i></a>';

                return '<tr>'
                    . '<td><strong>' . $name . '</strong></td>'
                    . '<td>' . $mime . '</td>'
                    . '<td>' . $sizeText . '</td>'
                    . '<td>' . $date . '</td>'
                    . '<td>' . $download . $del . '</td>'
                    . '</tr>';
            };

            UI::render('table', [
                'headers' => $headers,
                'items' => $anexos,
                'rowRenderer' => $rowRenderer,
                'emptyMessage' => 'Nenhum anexo enviado ainda.',
            ]);
            ?>
        </div>
    </div>
</section>

<script>
function confirmDeleteAnexo(id) {
    if (confirm('Deseja remover este anexo?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/financeiro/contas-a-pagar/anexos/delete/' + id;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
