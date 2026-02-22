<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\View;
use App\Core\Logger;
use App\Core\Audit\AuditLogger;
use App\Models\PortalCliente;
use App\Models\ContaReceber;
use App\Services\AsaasService;

/**
 * Controller de Contas a Pagar do Portal do Cliente.
 * Exibe as contas a receber vinculadas ao cliente e gera links de pagamento Asaas.
 */
class PortalContasPagarController extends Controller
{
    private PortalCliente $portalModel;
    private ContaReceber $contaModel;
    private Logger $logger;

    public function __construct()
    {
        $this->portalModel = new PortalCliente();
        $this->contaModel  = new ContaReceber();
        $this->logger      = new Logger();
    }

    /**
     * Retorna os dados do cliente logado no portal.
     */
    private function getPortalCliente(): object
    {
        $id = (int) ($_SESSION['portal_cliente_id'] ?? 0);
        $portal = $this->portalModel->findById($id);
        if (!$portal) {
            session_unset();
            header('Location: /portal/login?error=sessao_expirada');
            exit();
        }
        return $portal;
    }

    // ---------------------------------------------------------------
    // GET /portal/contas-a-pagar
    // ---------------------------------------------------------------
    public function index(): void
    {
        $portal    = $this->getPortalCliente();
        $clienteId = (int) $portal->cliente_id;
        $tenantId  = (int) $portal->tenant_id;

        $statusFiltro = $_GET['status'] ?? '';

        $this->logger->info('[Portal] Contas a Pagar acessado', [
            'portal_id'  => $portal->id,
            'cliente_id' => $clienteId,
            'filtro'     => $statusFiltro,
        ]);

        $contas = $this->contaModel->findByClienteIdAndTenantId($clienteId, $tenantId, [
            'status' => $statusFiltro,
        ]);

        // Classifica por status para exibição
        $contasAbertas   = array_filter($contas, fn($c) => $c->status === 'aberta');
        $contasVencidas  = array_filter($contasAbertas, fn($c) => $c->data_vencimento < date('Y-m-d'));
        $contasRecebidas = array_filter($contas, fn($c) => $c->status === 'recebida');
        $contasCanceladas = array_filter($contas, fn($c) => $c->status === 'cancelada');

        View::render('portal/contas-a-pagar/index', [
            'title'           => 'Minhas Contas',
            '_layout'         => 'portal',
            'portal'          => $portal,
            'contas'          => $contas,
            'contasAbertas'   => $contasAbertas,
            'contasVencidas'  => $contasVencidas,
            'contasRecebidas' => $contasRecebidas,
            'contasCanceladas'=> $contasCanceladas,
            'statusFiltro'    => $statusFiltro,
        ]);
    }

    // ---------------------------------------------------------------
    // GET /portal/contas-a-pagar/pagar/{id}
    // Gera o link de checkout Asaas e redireciona o cliente
    // ---------------------------------------------------------------
    public function pagar(int $id): void
    {
        $portal    = $this->getPortalCliente();
        $clienteId = (int) $portal->cliente_id;
        $tenantId  = (int) $portal->tenant_id;

        $conta = $this->contaModel->findById($id);

        // Segurança: verifica se a conta pertence ao cliente logado
        if (!$conta || (int) $conta->cliente_id !== $clienteId) {
            $this->logger->warning('[Portal] Tentativa de acesso a conta de outro cliente', [
                'portal_id'  => $portal->id,
                'conta_id'   => $id,
                'cliente_id' => $clienteId,
            ]);
            header('Location: /portal/contas-a-pagar?error=nao_autorizado');
            exit();
        }

        if ($conta->status === 'recebida') {
            header('Location: /portal/contas-a-pagar?info=ja_pago');
            exit();
        }

        if ($conta->status === 'cancelada') {
            header('Location: /portal/contas-a-pagar?error=cancelada');
            exit();
        }

        // Verifica se já tem payment_id no Asaas
        if (empty($conta->asaas_payment_id)) {
            $this->logger->warning('[Portal] Conta sem asaas_payment_id — não é possível gerar link', [
                'conta_id' => $id,
            ]);
            header('Location: /portal/contas-a-pagar?error=sem_link_pagamento');
            exit();
        }

        // Verifica se o AsaasService está configurado para o tenant
        if (!AsaasService::isConfigured()) {
            $this->logger->error('[Portal] AsaasService não configurado para o tenant', ['tenant_id' => $tenantId]);
            header('Location: /portal/contas-a-pagar?error=pagamento_indisponivel');
            exit();
        }

        try {
            $asaas = new AsaasService();
            $link  = $asaas->getLinkPagamento((string) $conta->asaas_payment_id);

            if (empty($link)) {
                $this->logger->error('[Portal] Link de pagamento Asaas retornou vazio', [
                    'conta_id'         => $id,
                    'asaas_payment_id' => $conta->asaas_payment_id,
                ]);
                header('Location: /portal/contas-a-pagar?error=link_indisponivel');
                exit();
            }

            AuditLogger::log('portal_checkout_iniciado', [
                'portal_id'        => $portal->id,
                'cliente_id'       => $clienteId,
                'conta_id'         => $id,
                'asaas_payment_id' => $conta->asaas_payment_id,
                'link'             => $link,
            ]);

            $this->logger->info('[Portal] Redirecionando para checkout Asaas', [
                'conta_id' => $id,
                'link'     => $link,
            ]);

            // Redireciona para o checkout Asaas (o cliente escolhe o meio de pagamento lá)
            header("Location: {$link}");
            exit();

        } catch (\Throwable $e) {
            $this->logger->error('[Portal] Erro ao gerar link de pagamento: ' . $e->getMessage(), [
                'conta_id' => $id,
                'trace'    => $e->getTraceAsString(),
            ]);
            header('Location: /portal/contas-a-pagar?error=erro_pagamento');
            exit();
        }
    }
}
