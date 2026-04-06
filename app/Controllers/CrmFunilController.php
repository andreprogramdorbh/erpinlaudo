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

    private function isAdmin(): bool
    {
        $role = $_SESSION['user_role'] ?? '';
        return in_array(strtolower($role), ['admin', 'superadmin'], true);
    }

    // ---------------------------------------------------------------
    // GET /crm/funil
    // ---------------------------------------------------------------
    public function index(): void
    {
        $uid     = $this->usuarioId();
        $isAdmin = $this->isAdmin();

        // Admin pode filtrar por qualquer usuário; 0 = todos
        $filtroUid = $isAdmin ? (int) ($_GET['uid'] ?? 0) : $uid;
        $uidBusca  = ($isAdmin && $filtroUid === 0) ? 0 : ($isAdmin ? $filtroUid : $uid);

        // Oportunidades abertas agrupadas por etapa
        $colunas = $this->opModel->findAbertosByUsuarioId($uidBusca);

        // Resumo por etapa (totais e valores)
        $resumo = $this->opModel->resumoFunilByUsuarioId($uidBusca);

        // Contagem de leads por status
        $leadsCount = $this->leadModel->countByStatusAndUsuarioId($uidBusca);

        // Lista de usuários com oportunidades (para o seletor do admin)
        $usuariosComOportunidades = $isAdmin
            ? $this->opModel->findUsuariosComOportunidades()
            : [];

        $this->logger->info('[CRM] Funil acessado', ['usuario_id' => $uid, 'filtro_uid' => $uidBusca]);

        View::render('crm/funil/index', [
            'title'      => 'Funil de Vendas',
            '_layout'    => 'erp',
            'breadcrumb' => ['CRM', 'Funil de Vendas'],
            'colunas'    => $colunas,
            'resumo'     => $resumo,
            'etapas'     => CrmOportunidade::ETAPAS,
            'leadsCount' => $leadsCount,
            'isAdmin'    => $isAdmin,
            'filtroUid'  => $filtroUid,
            'usuariosComOportunidades' => $usuariosComOportunidades,
        ]);
    }
}
