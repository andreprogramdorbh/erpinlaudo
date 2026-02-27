<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\View;
use App\Core\Logger;
use App\Models\PortalCliente;
use App\Models\NotaFiscal;
use App\Models\NotaFiscalAnexo;

/**
 * Controller de Faturamento do Portal do Cliente.
 * Gerencia: listagem de notas fiscais, download de XML.
 */
class PortalFaturamentoController extends Controller
{
    private PortalCliente   $portalModel;
    private NotaFiscal      $notaFiscalModel;
    private NotaFiscalAnexo $anexoModel;
    private Logger          $logger;

    public function __construct()
    {
        $this->portalModel     = new PortalCliente();
        $this->notaFiscalModel = new NotaFiscal();
        $this->anexoModel      = new NotaFiscalAnexo();
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
            header('Location: /login?error=sessao_expirada');
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

        // Carrega os anexos de cada nota para exibição no portal
        foreach ($notas as $nota) {
            try {
                $nota->anexos = $this->anexoModel->findByNotaIdForPortal((int) $nota->id, $tenantId);
            } catch (\Exception $e) {
                $this->logger->warning('[Portal] Erro ao carregar anexos da nota ' . $nota->id . ': ' . $e->getMessage());
                $nota->anexos = [];
            }
        }

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

    // ---------------------------------------------------------------
    // GET /portal/faturamento/nota-fiscal/anexo/{id}
    // Download de um anexo da nota fiscal pelo portal do cliente
    // ---------------------------------------------------------------
    public function downloadAnexo(int $id): void
    {
        try {
            $portal    = $this->getPortalCliente();
            $clienteId = (int) $portal->cliente_id;
            $tenantId  = (int) $portal->tenant_id;

            $anexo = $this->anexoModel->findById($id);

            // Verifica se o anexo pertence ao tenant
            if (!$anexo || (int) $anexo->usuario_id !== $tenantId) {
                $this->logger->warning('[Portal] Tentativa de download de anexo de outro tenant', [
                    'portal_id' => $portal->id,
                    'anexo_id'  => $id,
                ]);
                http_response_code(403);
                echo '403 - Acesso Negado';
                exit();
            }

            // Verifica se a nota vinculada pertence ao cliente logado
            $nota = $this->notaFiscalModel->findById((int) $anexo->nota_fiscal_id);
            if (!$nota || (int) $nota->cliente_id !== $clienteId) {
                $this->logger->warning('[Portal] Tentativa de download de anexo de nota de outro cliente', [
                    'portal_id'  => $portal->id,
                    'anexo_id'   => $id,
                    'cliente_id' => $clienteId,
                ]);
                http_response_code(403);
                echo '403 - Acesso Negado (Nota Inv\u00e1lida)';
                exit();
            }

            $fileRel = (string) ($anexo->file_path ?? '');
            $fileAbs = BASE_PATH . '/' . ltrim($fileRel, '/');

            if (!is_file($fileAbs)) {
                $this->logger->error('[Portal] Arquivo de anexo n\u00e3o encontrado', [
                    'portal_id' => $portal->id,
                    'anexo_id'  => $id,
                    'file_path' => $fileRel,
                ]);
                http_response_code(404);
                echo '404 - Arquivo n\u00e3o encontrado';
                exit();
            }

            $mime = $anexo->mime_type ?? 'application/octet-stream';
            $name = $anexo->original_name ?? basename($fileAbs);

            $this->logger->info('[Portal] Download de anexo de nota fiscal realizado', [
                'portal_id'      => $portal->id,
                'anexo_id'       => $id,
                'nota_fiscal_id' => $anexo->nota_fiscal_id,
            ]);

            header('Content-Type: ' . $mime);
            header('Content-Length: ' . filesize($fileAbs));
            header('Content-Disposition: attachment; filename="' . addslashes($name) . '"');
            readfile($fileAbs);
            exit();
        } catch (\Exception $e) {
            $this->logger->error('[Portal] Erro ao baixar anexo de nota fiscal: ' . $e->getMessage());
            http_response_code(500);
            echo 'Erro ao baixar arquivo';
            exit();
        }
    }
}
