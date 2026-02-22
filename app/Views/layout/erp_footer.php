</main>
</div>
</div>

<!-- SCRIPTS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
<!-- Flatpickr: datepicker leve para campos de data -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/pt.js"></script>

<!-- Enterprise Form Scripts -->
<link rel="stylesheet" href="/assets/css/form-layout.css">
<script src="/assets/js/sidebar.js"></script>
<script src="/assets/js/form-tabs.js"></script>

<!-- Scripts Específicos por Página -->
<?php
// Detecta a página atual para carregar scripts específicos
$currentPath = $_SERVER['REQUEST_URI'] ?? '';
$pageScripts = [];

// Clientes
if (strpos($currentPath, '/clientes') !== false) {
    $pageScripts[] = '/assets/js/clientes-form.js';
}

// Contas a Receber
if (strpos($currentPath, '/financeiro/contas-a-receber') !== false || strpos($currentPath, '/financeiro/receber') !== false) {
    $pageScripts[] = '/assets/js/contas-receber-form.js';
}

// Contas a Pagar
if (strpos($currentPath, '/financeiro/contas-a-pagar') !== false) {
    // Adicionar script específico quando existir
}

// Carrega os scripts específicos
foreach ($pageScripts as $script) {
    echo "<script src=\"{$script}\"></script>\n";
}
?>

</body>

</html>