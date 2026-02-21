<?php

use App\Core\UI;

UI::sectionHeader('Importar XML de NF-e', 'Envie um arquivo XML e o sistema criará a Nota Fiscal automaticamente', [
    [
        'text' => 'Voltar',
        'link' => '/faturamento/notas-fiscais',
        'icon' => 'fas fa-arrow-left',
        'class' => 'btn-light'
    ]
]);
?>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <form method="POST" action="/faturamento/notas-fiscais/importar" enctype="multipart/form-data" class="row g-3">
            <div class="col-12">
                <label class="form-label fw-bold">Arquivo XML</label>
                <input type="file" name="xml" class="form-control" accept=".xml,text/xml,application/xml" required>
                <div class="form-text">Tamanho máximo: 5MB. O vínculo ao cliente é feito via CPF/CNPJ do destinatário no XML.</div>
            </div>

            <div class="col-12 d-grid d-md-flex justify-content-md-end">
                <button type="submit" class="btn btn-primary fw-bold">
                    <i class="fas fa-file-import me-1"></i> Importar
                </button>
            </div>
        </form>
    </div>
</div>
