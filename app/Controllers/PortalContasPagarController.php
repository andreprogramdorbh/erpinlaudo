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
 *
 * Fluxo de pagamento por meio:
 *  - PIX      → exibe QR Code + código copia-e-cola na tela
 *  - Boleto   → redireciona para URL do boleto no Asaas
 *  - Checkout → redireciona para invoiceUrl (cliente escolhe o meio no Asaas)
 *  - Manual   → exibe mensagem de contato
 */
class PortalContasPagarController extends Controller
{
    private PortalCliente $portalModel;
    private ContaReceber  $contaModel;
    private Logger        $logger;

    public function __construct()
    {
        $this->portalModel = new PortalCliente();
        $this->contaModel  = new ContaReceber();
        $this->logger      = new Logger();
    }

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------

    private function getPortalCliente(): object
    {
        $id     = (int) ($_SESSION['portal_cliente_id'] ?? 0);
        $portal = $this->portalModel->findById($id);
        if (!$portal) {
            session_unset();
            header('Location: /login?error=sessao_expirada');
            exit();
        }
        return $portal;
    }

    // ---------------------------------------------------------------
    // GET /portal/contas-a-pagar/status/{id}  — polling para PIX
    // ---------------------------------------------------------------
    public function statusCheck(int $id): void
    {
        header('Content-Type: application/json');

        $portal    = $this->getPortalCliente();
        $clienteId = (int) $portal->cliente_id;

        $conta = $this->contaModel->findById($id);

        if (!$conta || (int) $conta->cliente_id !== $clienteId) {
            echo json_encode(['status' => 'error', 'message' => 'nao_autorizado']);
            exit();
        }

        echo json_encode([
            'status'  => $conta->status,
            'conta_id'=> $id,
        ]);
        exit();
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

        $hoje = date('Y-m-d');

        $contasAbertas    = array_filter($contas, fn($c) => $c->status === 'aberta');
        $contasVencidas   = array_filter($contasAbertas, fn($c) => ($c->data_vencimento ?? '') < $hoje);
        $contasRecebidas  = array_filter($contas, fn($c) => $c->status === 'recebida');
        $contasCanceladas = array_filter($contas, fn($c) => $c->status === 'cancelada');

        View::render('portal/contas-a-pagar/index', [
            'title'            => 'Minhas Contas',
            '_layout'          => 'portal',
            'portal'           => $portal,
            'contas'           => $contas,
            'contasAbertas'    => $contasAbertas,
            'contasVencidas'   => $contasVencidas,
            'contasRecebidas'  => $contasRecebidas,
            'contasCanceladas' => $contasCanceladas,
            'statusFiltro'     => $statusFiltro,
        ]);
    }

    // ---------------------------------------------------------------
    // GET /portal/contas-a-pagar/pagar/{id}
    // Redireciona ou exibe QR Code conforme o meio de pagamento
    // ---------------------------------------------------------------
    public function pagar(int $id): void
    {
        $portal    = $this->getPortalCliente();
        $clienteId = (int) $portal->cliente_id;
        $tenantId  = (int) $portal->tenant_id;

        $conta = $this->contaModel->findById($id);

        // Segurança: a conta deve pertencer ao cliente logado
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

        $meio = $conta->meio_pagamento ?? '';

        // Meios manuais — sem integração Asaas
        $meiosManuais = ['dinheiro', 'transferencia', 'cartao', 'outro', ''];
        if (in_array($meio, $meiosManuais, true)) {
            View::render('portal/contas-a-pagar/pagamento-manual', [
                'title'   => 'Pagamento',
                '_layout' => 'portal',
                'portal'  => $portal,
                'conta'   => $conta,
            ]);
            return;
        }

        // Meios Asaas — requer payment_id
        if (empty($conta->asaas_payment_id)) {
            $this->logger->warning('[Portal] Conta sem asaas_payment_id', ['conta_id' => $id]);
            header('Location: /portal/contas-a-pagar?error=sem_link_pagamento');
            exit();
        }

        if (!AsaasService::isConfigured()) {
            $this->logger->error('[Portal] AsaasService não configurado', ['tenant_id' => $tenantId]);
            header('Location: /portal/contas-a-pagar?error=pagamento_indisponivel');
            exit();
        }

        try {
            $asaas = new AsaasService();

            AuditLogger::log('portal_pagamento_iniciado', [
                'portal_id'        => $portal->id,
                'cliente_id'       => $clienteId,
                'conta_id'         => $id,
                'asaas_payment_id' => $conta->asaas_payment_id,
                'meio_pagamento'   => $meio,
            ]);

            switch ($meio) {

                // PIX — exibe QR Code na tela
                case 'pix':
                    $pixData = $asaas->getPixQrCode((string) $conta->asaas_payment_id);

                    if (empty($pixData['encodedImage'])) {
                        $this->logger->error('[Portal] QR Code PIX não disponível', [
                            'conta_id'         => $id,
                            'asaas_payment_id' => $conta->asaas_payment_id,
                        ]);
                        header('Location: /portal/contas-a-pagar?error=pix_indisponivel');
                        exit();
                    }

                    $this->logger->info('[Portal] QR Code PIX gerado', ['conta_id' => $id]);

                    View::render('portal/contas-a-pagar/pagar-pix', [
                        'title'          => 'Pagar com PIX',
                        '_layout'        => 'portal',
                        'portal'         => $portal,
                        'conta'          => $conta,
                        'pixEncodedImage'=> $pixData['encodedImage'],
                        'pixPayload'     => $pixData['payload'] ?? '',
                        'pixExpiracao'   => $pixData['expirationDate'] ?? '',
                    ]);
                    return;

                // Boleto — redireciona para URL do boleto
                case 'boleto':
                    $boletoUrl = $asaas->getBoletoUrl((string) $conta->asaas_payment_id);

                    if (empty($boletoUrl)) {
                        $this->logger->error('[Portal] URL do boleto não disponível', [
                            'conta_id'         => $id,
                            'asaas_payment_id' => $conta->asaas_payment_id,
                        ]);
                        header('Location: /portal/contas-a-pagar?error=boleto_indisponivel');
                        exit();
                    }

                    $this->logger->info('[Portal] Redirecionando para boleto', [
                        'conta_id' => $id,
                        'url'      => $boletoUrl,
                    ]);

                    header("Location: {$boletoUrl}");
                    exit();

                // Checkout — redireciona para invoiceUrl (cliente escolhe o meio no Asaas)
                case 'checkout':
                default:
                    $link = $asaas->getLinkPagamento((string) $conta->asaas_payment_id);

                    if (empty($link)) {
                        $this->logger->error('[Portal] Link de checkout não disponível', [
                            'conta_id'         => $id,
                            'asaas_payment_id' => $conta->asaas_payment_id,
                        ]);
                        header('Location: /portal/contas-a-pagar?error=link_indisponivel');
                        exit();
                    }

                    $this->logger->info('[Portal] Redirecionando para checkout Asaas', [
                        'conta_id' => $id,
                        'link'     => $link,
                    ]);

                    header("Location: {$link}");
                    exit();
            }

        } catch (\Throwable $e) {
            $this->logger->error('[Portal] Erro ao processar pagamento: ' . $e->getMessage(), [
                'conta_id' => $id,
                'trace'    => $e->getTraceAsString(),
            ]);
            header('Location: /portal/contas-a-pagar?error=erro_pagamento');
            exit();
        }
    }
}
