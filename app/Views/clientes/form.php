<?php
use App\Core\UI;
use App\Core\Auth;

$cliente = $cliente ?? null;
$isEdit = !empty($cliente);
$title = $isEdit ? 'Editar Cliente' : 'Novo Cliente';
$activeTab = $tab ?? 'geral';

UI::sectionHeader($title, $isEdit ? "Gerencie os detalhes de {$cliente->razao_social}" : 'Cadastre um novo cliente na sua rede');
?>

<div class="row justify-content-center">
    <div class="col-lg-12">
        <!-- Navegação por Abas -->
        <ul class="nav nav-pills nav-fill mb-4 bg-white p-2 rounded shadow-sm" id="clientTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $activeTab === 'geral' ? 'active' : ''; ?> fw-bold py-3"
                    id="geral-tab" data-bs-toggle="pill" data-bs-target="#geral" type="button" role="tab">
                    <i class="fas fa-info-circle me-2"></i> 1. Dados Gerais & Endereço
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button
                    class="nav-link <?php echo $activeTab === 'contatos' ? 'active' : ''; ?> <?php echo !$isEdit ? 'disabled' : ''; ?> fw-bold py-3"
                    id="contatos-tab" data-bs-toggle="pill" data-bs-target="#contatos" type="button" role="tab">
                    <i class="fas fa-address-book me-2"></i> 2. Gestão de Contatos
                </button>
            </li>
        </ul>

        <div class="tab-content" id="clientTabsContent">
            <!-- Aba 1: Geral -->
            <div class="tab-pane fade <?php echo $activeTab === 'geral' ? 'show active' : ''; ?>" id="geral"
                role="tabpanel">
                <?php require __DIR__ . '/tabs/geral.php'; ?>
            </div>

            <!-- Aba 2: Contatos -->
            <div class="tab-pane fade <?php echo $activeTab === 'contatos' ? 'show active' : ''; ?>" id="contatos"
                role="tabpanel">
                <?php if ($isEdit): ?>
                    <?php require __DIR__ . '/tabs/contatos.php'; ?>
                <?php else: ?>
                    <div class="card border-0 shadow-sm py-5 text-center">
                        <div class="card-body">
                            <i class="fas fa-lock fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Salve os dados gerais primeiro para habilitar os contatos.</h5>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Scripts de Módulo -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
<script src="/assets/js/clientes-module.js"></script>