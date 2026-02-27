<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\View;
use App\Core\Logger;
use App\Models\PortalCliente;
use App\Models\NotaFiscal;
use App\Models\NotaFiscalAnexo;
use App\Models\ContaReceber;
use App\Models\Integracao;
use App\Services\AsaasService;

/**
 * Controller de Faturamento do Portal do Cliente.
 * Gerencia: listagem de notas fiscais, emissão via Asaas, download de PDF/XML.
 */
class PortalFaturamentoController extends Controller
{
    private PortalCliente   $portalModel;
    private NotaFiscal      $notaFiscalModel;
    private NotaFiscalAnexo $anexoModel;
    private ContaReceber    $contaReceberModel;
    private Logger          $logger;

    public function __construct()
    {
        $this->portalModel       = new PortalCliente();
        $this->notaFiscalModel   = new NotaFiscal();
        $this->anexoModel        = new NotaFiscalAnexo();
        $this->contaReceberModel = new ContaReceber();
        $this->logger            = new Logger();
    }

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

    private function getAsaasService(int $tenantId): ?AsaasService
    {
        $integracaoModel = new Integracao();
        $config = $integracaoModel->findByProvider('asaas', $tenantId);
        if (!$config || $config->status !== 'active' || empty($config->api_key)) {
            return null;
        }
        return new AsaasService($config->api_key, $config->environment ?? 'sandbox');
    }

    // GET /portal/faturamento/notas-fiscais
    public function notasFiscais(): void
    {
        $portal    = $this->getPortalCliente();
        $clienteId = (int) $portal->cliente_id;
        $tenantId  = (int) $portal->tenant_id;

        $filtros = [
            'numero_nf'   => trim($_GET['numero_nf'] ?? ''),
            'data_inicio' => trim($_GET['data_inicio'] ?? ''),
            'data_fim'    => trim($_GET['data_fim'] ?? ''),
            'status'      => trim($_GET['status'] ?? ''),
            'pesquisa'    => trim($_GET['pesquisa'] ?? ''),
        ];

        $this->logger->info('[Portal] Notas Fiscais acessado', [
            'portal_id'  => $portal->id,
            'cliente_id' => $clienteId,
            'filtros'    => $filtros,
        ]);

        $notas = $this->notaFiscalModel->findByClienteIdAndTenantId($clienteId, $tenantId, $filtros);

        foreach ($notas as $nota) {
            try {
                $nota->anexos = $this->anexoModel->findByNotaIdForPortal((int) $nota->id, $tenantId);
            } catch (\Exception $e) {
                $this->logger->warning('[Portal] Erro ao carregar anexos da nota ' . $nota->id . ': ' . $e->getMessage());
                $nota->anexos = [];
            }
        }

        $successMsg = $_GET['success'] ?? null;
        $errorMsg   = $_GET['error'] ?? null;

        View::render('portal/faturamento/notas-fiscais', [
            'title'      => 'Minhas Notas Fiscais',
            '_layout'    => 'portal',
            'portal'     => $portal,
            'notas'      => $notas,
            'filtros'    => $filtros,
            'successMsg' => $successMsg,
            'errorMsg'   => $errorMsg,
        ]);
    }

    // POST /portal/faturamento/emitir-nfs/{id}
    public function emitirNfs(int $contaId): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $portal    = $this->getPortalCliente();
            $clienteId = (int) $portal->cliente_id;
            $tenantId  = (int) $portal->tenant_id;

            $conta = $this->contaReceberModel->findById($contaId);
            if (!$conta || (int) $conta->cliente_id !== $clienteId || (int) $conta->usuario_id !== $tenantId) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Conta não encontrada ou sem permissão.']);
                return;
            }

            if ($conta->status !== 'recebida') {
                http_response_code(422);
                echo json_encode(['success' => false, 'error' => 'A NF-s só pode ser emitida para contas pagas.']);
                return;
            }

            $nfExistente = $this->notaFiscalModel->findByContaReceberId($contaId, $tenantId);
            if ($nfExistente) {
                echo json_encode([
                    'success'    => true,
                    'ja_emitida' => true,
                    'redirect'   => '/portal/faturamento/notas-fiscais?success=nf_ja_emitida',
                    'message'    => 'Já existe uma NF-s emitida para esta conta.',
                ]);
                return;
            }

            $asaas = $this->getAsaasService($tenantId);
            if (!$asaas) {
                http_response_code(503);
                echo json_encode(['success' => false, 'error' => 'Integração Asaas não configurada. Contate o suporte.']);
                return;
            }

            $descricao = $conta->descricao ?? 'Serviços Prestados';
            $valor     = (float) $conta->valor;
            $dataHoje  = date('Y-m-d');

            $payload = [
                'serviceDescription'   => $descricao,
                'observations'         => 'NF-s emitida automaticamente via portal do cliente. Referência: ' . ($conta->descricao ?? 'Conta #' . $contaId),
                'value'                => $valor,
                'deductions'           => 0,
                'effectiveDate'        => $dataHoje,
                'municipalServiceName' => 'Serviços de Saúde / Radiologia',
                'taxes'                => [
                    'retainIss' => false,
                    'iss'       => 0,
                    'cofins'    => 0,
                    'csll'      => 0,
                    'inss'      => 0,
                    'ir'        => 0,
                    'pis'       => 0,
                ],
                'externalReference'    => 'portal|cr:' . $contaId . '|u:' . $tenantId,
            ];

            if (!empty($conta->asaas_payment_id)) {
                $payload['payment'] = $conta->asaas_payment_id;
            }

            $response = $asaas->agendarNotaFiscal($payload);

            $asaasInvoiceId = $response['id'] ?? null;
            $asaasStatus    = $response['status'] ?? 'SCHEDULED';
            $pdfUrl         = $response['pdfUrl'] ?? $response['invoiceUrl'] ?? null;
            $numeroNf       = $response['number'] ?? '';

            $nfId = $this->notaFiscalModel->create([
                'usuario_id'        => $tenantId,
                'cliente_id'        => $clienteId,
                'numero_nf'         => (string) $numeroNf,
                'serie'             => '1',
                'valor_total'       => $valor,
                'data_emissao'      => $dataHoje,
                'status'            => 'emitida',
                'xml_path'          => null,
                'asaas_invoice_id'  => $asaasInvoiceId,
                'origem_emissao'    => 'asaas',
                'conta_receber_id'  => $contaId,
                'asaas_pdf_url'     => $pdfUrl,
                'asaas_status'      => $asaasStatus,
                'servico_descricao' => $descricao,
                'observacoes_nf'    => $payload['observations'],
            ]);

            $this->logger->info('[Portal] NF-s emitida via Asaas', [
                'portal_id'        => $portal->id,
                'cliente_id'       => $clienteId,
                'conta_receber_id' => $contaId,
                'asaas_invoice_id' => $asaasInvoiceId,
                'asaas_status'     => $asaasStatus,
                'nf_local_id'      => $nfId,
            ]);

            echo json_encode([
                'success'          => true,
                'asaas_invoice_id' => $asaasInvoiceId,
                'asaas_status'     => $asaasStatus,
                'status_label'     => AsaasService::mapearStatusNfs($asaasStatus),
                'pdf_url'          => $pdfUrl,
                'redirect'         => '/portal/faturamento/notas-fiscais?success=nf_emitida',
                'message'          => 'NF-s emitida com sucesso! Redirecionando para suas notas fiscais...',
            ]);

        } catch (\RuntimeException $e) {
            $this->logger->error('[Portal] Erro ao emitir NF-s via Asaas: ' . $e->getMessage(), ['conta_id' => $contaId]);
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Erro ao emitir NF-s: ' . $e->getMessage()]);
        } catch (\Exception $e) {
            $this->logger->error('[Portal] Exceção ao emitir NF-s: ' . $e->getMessage(), ['conta_id' => $contaId]);
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Erro interno. Tente novamente ou contate o suporte.']);
        }
    }

    // GET /portal/faturamento/nota-fiscal/pdf/{id}
    public function downloadPdf(int $id): void
    {
        try {
            $portal    = $this->getPortalCliente();
            $clienteId = (int) $portal->cliente_id;
            $tenantId  = (int) $portal->tenant_id;

            $nota = $this->notaFiscalModel->findById($id);
            if (!$nota || (int) $nota->cliente_id !== $clienteId || (int) $nota->usuario_id !== $tenantId) {
                header('HTTP/1.1 403 Forbidden');
                echo 'Acesso não autorizado.';
                exit();
            }

            if (!empty($nota->asaas_invoice_id)) {
                try {
                    $asaas = $this->getAsaasService($tenantId);
                    if ($asaas) {
                        $invoiceData = $asaas->consultarNotaFiscal($nota->asaas_invoice_id);
                        $pdfUrl = $invoiceData['pdfUrl'] ?? $invoiceData['invoiceUrl'] ?? $nota->asaas_pdf_url ?? null;
                        if ($pdfUrl && $pdfUrl !== $nota->asaas_pdf_url) {
                            $this->notaFiscalModel->update($id, [
                                'asaas_pdf_url' => $pdfUrl,
                                'asaas_status'  => $invoiceData['status'] ?? $nota->asaas_status,
                            ]);
                        }
                        if ($pdfUrl) {
                            header('Location: ' . $pdfUrl);
                            exit();
                        }
                    }
                } catch (\Exception $e) {
                    $this->logger->warning('[Portal] Falha ao consultar Asaas para PDF', ['nota_id' => $id, 'error' => $e->getMessage()]);
                }
            }

            if (!empty($nota->asaas_pdf_url)) {
                header('Location: ' . $nota->asaas_pdf_url);
                exit();
            }

            header('Location: /portal/faturamento/notas-fiscais?error=pdf_indisponivel');
            exit();

        } catch (\Exception $e) {
            $this->logger->error('[Portal] Erro ao baixar PDF da NF: ' . $e->getMessage(), ['nota_id' => $id]);
            header('Location: /portal/faturamento/notas-fiscais?error=erro_download_pdf');
            exit();
        }
    }

    // GET /portal/faturamento/nota-fiscal/xml/{id}
    public function downloadXml(int $id): void
    {
        $portal    = $this->getPortalCliente();
        $clienteId = (int) $portal->cliente_id;
        $tenantId  = (int) $portal->tenant_id;
        $nota = $this->notaFiscalModel->findById($id);
        if (!$nota || (int) $nota->cliente_id !== $clienteId || (int) $nota->usuario_id !== $tenantId) {
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
            header('Location: /portal/faturamento/notas-fiscais?error=arquivo_nao_encontrado');
            exit();
        }
        header('Content-Type: application/xml');
        header('Content-Disposition: attachment; filename="NF-' . $nota->numero_nf . '.xml"');
        header('Content-Length: ' . filesize($xmlFile));
        readfile($xmlFile);
        exit();
    }

    // GET /portal/faturamento/nota-fiscal/anexo/{id}
    public function downloadAnexo(int $id): void
    {
        try {
            $portal    = $this->getPortalCliente();
            $clienteId = (int) $portal->cliente_id;
            $tenantId  = (int) $portal->tenant_id;
            $anexo = $this->anexoModel->findById($id);
            if (!$anexo || (int) $anexo->usuario_id !== $tenantId) {
                http_response_code(403);
                echo '403 - Acesso Negado';
                exit();
            }
            $nota = $this->notaFiscalModel->findById((int) $anexo->nota_fiscal_id);
            if (!$nota || (int) $nota->cliente_id !== $clienteId) {
                http_response_code(403);
                echo '403 - Acesso Negado (Nota Inválida)';
                exit();
            }
            $fileRel = (string) ($anexo->file_path ?? '');
            $fileAbs = BASE_PATH . '/' . ltrim($fileRel, '/');
            if (!is_file($fileAbs)) {
                http_response_code(404);
                echo '404 - Arquivo não encontrado';
                exit();
            }
            $mime = $anexo->mime_type ?? 'application/octet-stream';
            $name = $anexo->original_name ?? basename($fileAbs);
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
