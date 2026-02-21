<?php
use App\Core\UI;
require_once dirname(__DIR__) . '/layout/erp_header.php';
?>

<div class="main-content">
    <div class="container-fluid">

        <?php UI::sectionHeader('Overview do Sistema', 'Bem-vindo de volta ao InLaudo ERP'); ?>

        <!-- Card stats -->
        <div class="row">
            <div class="col-xl-3 col-md-6">
                <?php UI::statCard('Clientes', '350', 'fas fa-users', 'danger', '<span class="text-success"><i class="fa fa-arrow-up"></i> 3.48%</span>'); ?>
            </div>
            <div class="col-xl-3 col-md-6">
                <?php UI::statCard('Contratos', '2,356', 'fas fa-file-signature', 'warning', '<span class="text-success"><i class="fa fa-arrow-up"></i> 12.1%</span>'); ?>
            </div>
            <div class="col-xl-3 col-md-6">
                <?php UI::statCard('Contas a Pagar', 'R$ 924,00', 'fas fa-arrow-down', 'success', '<span class="text-danger"><i class="fa fa-arrow-down"></i> 5.7%</span>'); ?>
            </div>
            <div class="col-xl-3 col-md-6">
                <?php UI::statCard('Contas a Receber', 'R$ 49.650,00', 'fas fa-arrow-up', 'info', '<span class="text-success"><i class="fa fa-arrow-up"></i> 10.5%</span>'); ?>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-xl-8">
                <?php
                $chartContent = '
                    <div class="chart text-center py-5">
                        <i class="fas fa-chart-line fa-5x text-light opacity-5"></i>
                        <p class="text-muted mt-3">Gráfico de desempenho será exibido aqui</p>
                    </div>';
                UI::card($chartContent, 'Desempenho de Vendas', ['subtitle' => 'Volume mensal de faturamento']);
                ?>
            </div>
            <div class="col-xl-4">
                <?php
                $activityContent = '
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item px-0 border-0">
                            <div class="d-flex align-items-center">
                                <div class="bg-primary-light p-2 rounded me-3"><i class="fas fa-plus text-primary"></i></div>
                                <div>Novo cliente: <strong>Empresa ACME</strong> <br><small class="text-muted">Há 2 horas</small></div>
                            </div>
                        </li>
                        <li class="list-group-item px-0 border-0">
                            <div class="d-flex align-items-center">
                                <div class="bg-success-light p-2 rounded me-3"><i class="fas fa-check text-success"></i></div>
                                <div>Fatura paga: <strong>João Silva</strong> <br><small class="text-muted">Há 5 horas</small></div>
                            </div>
                        </li>
                        <li class="list-group-item px-0 border-0">
                            <div class="d-flex align-items-center">
                                <div class="bg-info-light p-2 rounded me-3"><i class="fas fa-sync text-info"></i></div>
                                <div>Contrato renovado: <strong>Inova Tech</strong> <br><small class="text-muted">Ontem</small></div>
                            </div>
                        </li>
                    </ul>';
                UI::card($activityContent, 'Últimas Atividades');
                ?>
            </div>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/layout/erp_footer.php'; ?>