<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\View;
use App\Core\Logger;
use App\Models\CrmOportunidade;
use App\Models\CrmLead;

class CrmFunilController extends Controller
{
    private CrmOportunidade $opModel;
    private CrmLead $leadModel;
    private Logger $logger;

    public function __construct()
    {
        $this->opModel   = new CrmOportunidade();
        $this->leadModel = new CrmLead();
        $this->logger    = new Logger();
    }

    private function usuarioId(): int
    {
        return (int) ($_SESSION['user_id'] ?? 0);
    }

    // ---------------------------------------------------------------
    // GET /crm/funil
    // ---------------------------------------------------------------
    public function index(): void
    {
        $uid = $this->usuarioId();

        // Oportunidades abertas agrupadas por etapa
        $colunas = $this->opModel->findAbertosByUsuarioId($uid);

        // Resumo por etapa (totais e valores)
        $resumo = $this->opModel->resumoFunilByUsuarioId($uid);

        // Contagem de leads por status
        $leadsCount = $this->leadModel->countByStatusAndUsuarioId($uid);

        $this->logger->info('[CRM] Funil acessado', ['usuario_id' => $uid]);

        View::render('crm/funil/index', [
            'title'      => 'Funil de Vendas',
            '_layout'    => 'erp',
            'breadcrumb' => ['CRM', 'Funil de Vendas'],
            'colunas'    => $colunas,
            'resumo'     => $resumo,
            'etapas'     => CrmOportunidade::ETAPAS,
            'leadsCount' => $leadsCount,
        ]);
    }
}
