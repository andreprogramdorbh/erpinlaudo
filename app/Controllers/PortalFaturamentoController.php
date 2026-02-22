<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\View;
use App\Core\Logger;
use App\Models\PortalCliente;
use App\Models\NotaFiscal;

/**
 * Controller de Faturamento do Portal do Cliente.
 * Gerencia: listagem de notas fiscais, download de XML.
 */
class PortalFaturamentoController extends Controller
{
    private PortalCliente $portalModel;
    private NotaFiscal $notaFiscalModel;
    private Logger $logger;

    public function __construct()
    {
        $this->portalModel     = new PortalCliente();
        $this->notaFiscalModel = new NotaFiscal();
        $this->logger          = new Logger();
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
    // GET /portal/faturamento/notas-fiscais
    // ---------------------------------------------------------------
    public function notasFiscais(): void
    {
        $portal    = $this->getPortalCliente();
        $clienteId = (int) $portal->cliente_id;
        $tenantId  = (int) $portal->tenant_id;

        $this->logger->info('[Portal] Notas Fiscais acessado', [
            'portal_id'  => $portal->id,
            'cliente_id' => $clienteId,
        ]);

        // Busca notas fiscais do cliente (apenas emitidas e importadas)
        $notas = $this->notaFiscalModel->findByClienteIdAndTenantId($clienteId, $tenantId);

        View::render('portal/faturamento/notas-fiscais', [
            'title'   => 'Minhas Notas Fiscais',
            '_layout' => 'portal',
            'portal'  => $portal,
            'notas'   => $notas,
        ]);
    }

    // ---------------------------------------------------------------
    // GET /portal/faturamento/nota-fiscal/xml/{id}
    // Download do XML da nota fiscal
    // ---------------------------------------------------------------
    public function downloadXml(int $id): void
    {
        $portal    = $this->getPortalCliente();
        $clienteId = (int) $portal->cliente_id;
        $tenantId  = (int) $portal->tenant_id;

        $nota = $this->notaFiscalModel->findById($id);

        // Segurança: verifica se a nota pertence ao cliente logado
        if (!$nota || (int) $nota->cliente_id !== $clienteId || (int) $nota->usuario_id !== $tenantId) {
            $this->logger->warning('[Portal] Tentativa de download de nota de outro cliente', [
                'portal_id'  => $portal->id,
                'nota_id'    => $id,
                'cliente_id' => $clienteId,
            ]);
            header('HTTP/1.1 403 Forbidden');
            echo 'Acesso não autorizado.';
            exit();
        }

        if (empty($nota->xml_path)) {
            header('Location: /portal/faturamento/notas-fiscais?error=xml_indisponivel');
            exit();
        }

        $xmlFile = BASE_PATH . '/public/' . ltrim($nota->xml_path, '/');

        if (!file_exists($xmlFile)) {
            $this->logger->error('[Portal] Arquivo XML não encontrado', [
                'nota_id'  => $id,
                'xml_path' => $nota->xml_path,
            ]);
            header('Location: /portal/faturamento/notas-fiscais?error=arquivo_nao_encontrado');
            exit();
        }

        $this->logger->info('[Portal] Download de XML realizado', [
            'portal_id' => $portal->id,
            'nota_id'   => $id,
        ]);

        header('Content-Type: application/xml');
        header('Content-Disposition: attachment; filename="NF-' . $nota->numero_nf . '.xml"');
        header('Content-Length: ' . filesize($xmlFile));
        readfile($xmlFile);
        exit();
    }
}
