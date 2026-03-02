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
use App\Models\ConfigNfs;
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
    private ConfigNfs       $configNfsModel;
    private Logger          $logger;

    public function __construct()
    {
        $this->portalModel       = new PortalCliente();
        $this->notaFiscalModel   = new NotaFiscal();
        $this->anexoModel        = new NotaFiscalAnexo();
        $this->contaReceberModel = new ContaReceber();
        $this->configNfsModel    = new ConfigNfs();
        $this->logger            = new Logger();
    }

    private function normalizeCep(?string $cep): string
    {
        return preg_replace('/\D/', '', (string) ($cep ?? ''));
    }

    private function isValidCep(string $cepDigits): bool
    {
        return (bool) preg_match('/^\d{8}$/', $cepDigits);
    }

    /**
     * Valida campos mínimos de endereço para emissão via Asaas (NFS-e exige endereço completo).
     * Retorna lista de campos faltantes/invalidos para auditoria e mensagem.
     */
    private function validateEnderecoForAsaas(object $portal): array
    {
        $missing = [];

        $cepDigits = $this->normalizeCep($portal->cep ?? '');
        if (!$this->isValidCep($cepDigits)) {
            $missing[] = 'cep';
        }

        if (empty(trim((string) ($portal->endereco ?? '')))) {
            $missing[] = 'endereco';
        }
        if (empty(trim((string) ($portal->bairro ?? '')))) {
            $missing[] = 'bairro';
        }
        if (empty(trim((string) ($portal->cidade ?? '')))) {
            $missing[] = 'cidade';
        }

        $uf = strtoupper(trim((string) ($portal->estado ?? '')));
        if (strlen($uf) !== 2) {
            $missing[] = 'estado';
        }

        return $missing;
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

    /**
     * Sincroniza em tempo real as NFs com asaas_invoice_id mas sem pdfUrl.
     * Consulta a API Asaas para cada NF pendente e atualiza o banco de dados.
     * Modifica os objetos $notas in-place para refletir os dados atualizados.
     *
     * @param  array  $notas     Lista de objetos de notas fiscais (passada por referência)
     * @param  int    $tenantId  ID do tenant (usuario_id)
     */
    private function sincronizarNotasPendentes(array &$notas, int $tenantId): void
    {
        // Filtra apenas NFs que precisam de sincronização:
        // - Têm asaas_invoice_id (foram criadas via Asaas)
        // - Não têm pdfUrl (ainda não foram autorizadas ou não sincronizamos)
        // - Não estão canceladas ou com erro (já estão em estado final)
        $pendentes = array_filter($notas, function ($nota) {
            return !empty($nota->asaas_invoice_id)
                && empty($nota->asaas_pdf_url)
                && !in_array($nota->status ?? '', ['cancelada', 'erro_emissao'], true);
        });

        if (empty($pendentes)) {
            return;
        }

        // Obtém o serviço Asaas (retorna null se não configurado)
        $asaas = $this->getAsaasService($tenantId);
        if (!$asaas) {
            $this->logger->warning('[Portal] Sincronização de NFs: Asaas não configurado', [
                'tenant_id'   => $tenantId,
                'pendentes'   => count($pendentes),
            ]);
            return;
        }

        $this->logger->info('[Portal] Sincronizando NFs pendentes com Asaas', [
            'tenant_id' => $tenantId,
            'total'     => count($pendentes),
        ]);

        foreach ($pendentes as &$nota) {
            try {
                $invoiceData = $asaas->consultarNotaFiscal((string) $nota->asaas_invoice_id);
                $asaasStatus = $invoiceData['status'] ?? null;
                $pdfUrl      = $invoiceData['pdfUrl'] ?? $invoiceData['invoiceUrl'] ?? null;
                $xmlUrl      = $invoiceData['xmlUrl'] ?? null;
                $numeroNf    = $invoiceData['number'] ?? null;

                if (!$asaasStatus) {
                    continue;
                }

                // Mapeia o status Asaas para o status interno do banco
                $novoStatus = AsaasService::mapearStatusNfsParaBanco($asaasStatus);

                // Prepara os dados para atualização
                $updateData = [
                    'asaas_status' => $asaasStatus,
                    'status'       => $novoStatus,
                ];

                // Atualiza pdfUrl se disponível
                if ($pdfUrl) {
                    $updateData['asaas_pdf_url'] = $pdfUrl;
                }

                // Atualiza número da NF se disponível e ainda não temos
                if ($numeroNf && empty($nota->numero_nf)) {
                    $updateData['numero_nf'] = (string) $numeroNf;
                }

                // Persiste no banco
                $this->notaFiscalModel->update((int) $nota->id, $updateData);

                // Atualiza o objeto in-place para a view refletir os dados atualizados
                $nota->asaas_status = $asaasStatus;
                $nota->status       = $novoStatus;
                if ($pdfUrl) {
                    $nota->asaas_pdf_url = $pdfUrl;
                }
                if ($numeroNf && empty($nota->numero_nf)) {
                    $nota->numero_nf = (string) $numeroNf;
                }

                $this->logger->info('[Portal] NF sincronizada com Asaas', [
                    'nota_id'          => $nota->id,
                    'asaas_invoice_id' => $nota->asaas_invoice_id,
                    'asaas_status'     => $asaasStatus,
                    'status_banco'     => $novoStatus,
                    'pdf_url'          => $pdfUrl ? 'sim' : 'não',
                    'numero_nf'        => $numeroNf,
                ]);

            } catch (\Exception $e) {
                $this->logger->warning('[Portal] Falha ao sincronizar NF com Asaas', [
                    'nota_id'          => $nota->id ?? null,
                    'asaas_invoice_id' => $nota->asaas_invoice_id ?? null,
                    'error'            => $e->getMessage(),
                ]);
                // Não interrompe o loop — continua com as demais NFs
            }
        }
        unset($nota); // Limpa a referência do foreach
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

        // ---------------------------------------------------------------
        // SINCRONIZAÇÃO EM TEMPO REAL COM ASAAS
        // Verifica notas com asaas_invoice_id mas sem pdfUrl e consulta a API
        // para atualizar status e URLs (NFs que foram SCHEDULED e agora estão AUTHORIZED)
        // ---------------------------------------------------------------
        $this->sincronizarNotasPendentes($notas, $tenantId);

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
            $customerId = null; // ID do cliente no Asaas (será preenchido abaixo)

            // ---------------------------------------------------------------
            // SINCRONIZAR ENDEREÇO DO CLIENTE NO ASAAS
            // O Asaas exige endereço completo com CEP válido para emitir NFS-e
            // ---------------------------------------------------------------
            try {
                $documento    = AsaasService::formatarDocumento($portal->cpf_cnpj ?? '');
                $asaasCliente = $asaas->buscarCliente($documento, $portal->email_principal ?? null);

                $cepDigits       = $this->normalizeCep($portal->cep ?? '');
                $enderecoMissing = $this->validateEnderecoForAsaas($portal);

                // Montar dados de atualização com endereço completo
                $clienteUpdateData = [
                    'name'    => $portal->razao_social ?? $portal->nome_fantasia ?? '',
                    'email'   => $portal->email_principal ?? '',
                    'phone'   => $portal->telefone ?? $portal->celular ?? '',
                    'cpfCnpj' => $documento,
                ];
                // Se o portal tiver endereço válido, sincroniza no Asaas (endereço completo).
                if (empty($enderecoMissing)) {
                    $clienteUpdateData['postalCode']    = $cepDigits;
                    $clienteUpdateData['address']       = trim((string) ($portal->endereco ?? ''));
                    $numeroEndereco = trim((string) ($portal->numero ?? ''));
                    $clienteUpdateData['addressNumber'] = $numeroEndereco !== '' ? $numeroEndereco : 'S/N';
                    $clienteUpdateData['complement']    = trim((string) ($portal->complemento ?? ''));
                    $clienteUpdateData['province']      = trim((string) ($portal->bairro ?? ''));
                    $clienteUpdateData['city']          = trim((string) ($portal->cidade ?? ''));
                    $clienteUpdateData['state']         = strtoupper(trim((string) ($portal->estado ?? '')));
                }

                if ($asaasCliente && !empty($asaasCliente['id'])) {
                    $customerId = $asaasCliente['id'];
                    // Se o portal não tem endereço completo, tenta usar o endereço já existente no Asaas.
                    if (!empty($enderecoMissing)) {
                        $asaasPostal = preg_replace('/\D/', '', (string) ($asaasCliente['postalCode'] ?? ''));
                        $asaasHasAddress = $this->isValidCep($asaasPostal);

                        if (!$asaasHasAddress) {
                            $this->logger->error('[Portal] NFS-e: Endereço incompleto para emissão (portal e Asaas)', [
                                'portal_id'        => $portal->id,
                                'cliente_id'       => $clienteId,
                                'tenant_id'        => $tenantId,
                                'customer_id'      => $customerId,
                                'missing'          => $enderecoMissing,
                                'cep'              => $portal->cep ?? null,
                                'cep_digits'       => $cepDigits,
                                'asaas_postalCode' => $asaasCliente['postalCode'] ?? null,
                            ]);
                            http_response_code(422);
                            echo json_encode([
                                'success' => false,
                                'error'   => 'Endereço do cliente incompleto. Atualize o CEP e endereço (CEP, rua, número, bairro, cidade e UF) e tente novamente.',
                                'missing' => $enderecoMissing,
                            ]);
                            return;
                        }
                    } else {
                        $asaas->atualizarCliente($customerId, $clienteUpdateData);
                    }
                    $this->logger->info('[Portal] NFS-e: Endereço do cliente sincronizado no Asaas', [
                        'customer_id' => $customerId,
                        'cep'         => $portal->cep ?? null,
                        'cidade'      => $portal->cidade ?? null,
                    ]);
                } else {
                    // Para criar cliente no Asaas, precisamos de endereço válido (senão a emissão falhará).
                    if (!empty($enderecoMissing)) {
                        $this->logger->error('[Portal] NFS-e: Endereço incompleto para criar cliente no Asaas', [
                            'portal_id'  => $portal->id,
                            'cliente_id' => $clienteId,
                            'tenant_id'  => $tenantId,
                            'missing'    => $enderecoMissing,
                            'cep'        => $portal->cep ?? null,
                            'cep_digits' => $cepDigits,
                        ]);
                        http_response_code(422);
                        echo json_encode([
                            'success' => false,
                            'error'   => 'Endereço do cliente incompleto. Preencha CEP e endereço (CEP, rua, número, bairro, cidade e UF) para emitir a NF-s.',
                            'missing' => $enderecoMissing,
                        ]);
                        return;
                    }
                    // Cliente não existe no Asaas — criar com endereço completo
                    $novoCliente = $asaas->criarCliente($clienteUpdateData);
                    $customerId  = $novoCliente['id'] ?? null;
                    $this->logger->info('[Portal] NFS-e: Cliente criado no Asaas com endereço', [
                        'customer_id' => $customerId,
                    ]);
                }
            } catch (\Exception $eSyncCliente) {
                // Não bloquear a emissão se a sincronização falhar — apenas logar
                $this->logger->warning('[Portal] NFS-e: Falha ao sincronizar endereço do cliente no Asaas (não bloqueante)', [
                    'error'      => $eSyncCliente->getMessage(),
                    'cliente_id' => $clienteId,
                ]);
            }

            // Carregar configurações de NFS-e do tenant
            $configNfs  = $this->configNfsModel->findByUsuarioId($tenantId);
            $layoutTipo = $configNfs->layout_tipo ?? 'padrao';

            $this->logger->info('[Portal] NFS-e: Montando payload', [
                'layout_tipo'      => $layoutTipo,
                'conta_id'         => $contaId,
                'valor'            => $valor,
                'asaas_payment_id' => $conta->asaas_payment_id ?? null,
            ]);

            if ($layoutTipo === 'personalizado' && !empty($configNfs->json_template)) {
                // ---- LAYOUT PERSONALIZADO: substituir variáveis no template JSON ----
                $jsonRaw = $configNfs->json_template;
                $jsonRaw = str_replace('{{value}}',       $valor,                             $jsonRaw);
                $jsonRaw = str_replace('{{date}}',        $dataHoje,                          $jsonRaw);
                $jsonRaw = str_replace('{{payment_id}}',  $conta->asaas_payment_id ?? '',     $jsonRaw);
                $jsonRaw = str_replace('{{customer_id}}', $conta->asaas_customer_id ?? '',    $jsonRaw);
                $jsonRaw = str_replace('{{description}}', $descricao,                         $jsonRaw);

                $payload = json_decode($jsonRaw, true);
                if (!is_array($payload)) {
                    throw new \RuntimeException('Template JSON personalizado inválido. Verifique as configurações de NFS-e.');
                }
                // Garantir campos dinâmicos não sobrescritos pelo template
                $payload['value']          = $valor;
                $payload['effectiveDate']  = $payload['effectiveDate'] ?? $dataHoje;
                $payload['_layout_tipo']   = 'personalizado';
            } else {
                // ---- LAYOUT PADRÃO: montar payload com configurações salvas ----
                $taxes = ['retainIss' => (bool) ($configNfs->retain_iss ?? false)];
                if (!empty($configNfs->iss_aliquota) && $configNfs->iss_aliquota > 0) {
                    $taxes['iss'] = (float) $configNfs->iss_aliquota;
                }
                if (!empty($configNfs->pis_aliquota) && $configNfs->pis_aliquota > 0) {
                    $taxes['pis'] = (float) $configNfs->pis_aliquota;
                }
                if (!empty($configNfs->cofins_aliquota) && $configNfs->cofins_aliquota > 0) {
                    $taxes['cofins'] = (float) $configNfs->cofins_aliquota;
                }
                if (!empty($configNfs->csll_aliquota) && $configNfs->csll_aliquota > 0) {
                    $taxes['csll'] = (float) $configNfs->csll_aliquota;
                }
                if (!empty($configNfs->inss_aliquota) && $configNfs->inss_aliquota > 0) {
                    $taxes['inss'] = (float) $configNfs->inss_aliquota;
                }
                if (!empty($configNfs->ir_aliquota) && $configNfs->ir_aliquota > 0) {
                    $taxes['ir'] = (float) $configNfs->ir_aliquota;
                }

                $payload = [
                    'serviceDescription'   => $configNfs->service_description ?? $descricao,
                    'observations'         => $configNfs->observations
                                             ?? ('NF-s emitida via portal. Refêrencia: ' . $descricao),
                    'value'                => $valor,
                    'deductions'           => (float) ($configNfs->deductions ?? 0),
                    'effectiveDate'        => $dataHoje,
                    'municipalServiceName' => $configNfs->municipal_service_name ?? 'Serviços de Saúde / Radiologia',
                    'taxes'                => $taxes,
                    'externalReference'    => 'portal|cr:' . $contaId . '|u:' . $tenantId,
                    '_layout_tipo'         => 'padrao',
                ];

                // Código de serviço municipal
                if (!empty($configNfs->municipal_service_id)) {
                    $payload['municipalServiceId'] = $configNfs->municipal_service_id;
                } elseif (!empty($configNfs->municipal_service_code)) {
                    $payload['municipalServiceCode'] = $configNfs->municipal_service_code;
                }

                // CNAE para NFS-e Nacional
                if (!empty($configNfs->cnae)) {
                    $payload['cnae'] = preg_replace('/\D/', '', (string) $configNfs->cnae);
                }

                // Série da NF
                if (!empty($configNfs->serie_nf)) {
                    $payload['serie'] = $configNfs->serie_nf;
                }
            }

            // Vínculo com pagamento Asaas (sobrescreve o do template se existir)
            if (!empty($conta->asaas_payment_id)) {
                $payload['payment'] = $conta->asaas_payment_id;
            }

            // Adicionar customer_id se não há payment vinculado (emissão avulsa)
            if (empty($payload['payment']) && !empty($customerId)) {
                $payload['customer'] = $customerId;
            }

            $response = $asaas->agendarNotaFiscal($payload);

            $asaasInvoiceId = $response['id'] ?? null;
            $asaasStatus    = $response['status'] ?? 'SCHEDULED';
            $pdfUrl         = $response['pdfUrl'] ?? $response['invoiceUrl'] ?? null;
            $numeroNf       = $response['number'] ?? '';

            // Mapeia o status Asaas para o status interno do banco.
            // SCHEDULED/SYNCHRONIZED = 'agendada'; AUTHORIZED = 'emitida'
            $statusBanco = AsaasService::mapearStatusNfsParaBanco($asaasStatus);

            $nfId = $this->notaFiscalModel->create([
                'usuario_id'        => $tenantId,
                'cliente_id'        => $clienteId,
                'numero_nf'         => (string) $numeroNf,
                'serie'             => '1',
                'valor_total'       => $valor,
                'data_emissao'      => $dataHoje,
                'status'            => $statusBanco,
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
            $cepRaw    = isset($portal) ? ($portal->cep ?? null) : null;
            $cepDigits = isset($portal) ? $this->normalizeCep($cepRaw) : '';
            $missingEndereco = isset($portal) ? $this->validateEnderecoForAsaas($portal) : [];

            $this->logger->error('[Portal] Erro ao emitir NF-s via Asaas: ' . $e->getMessage(), [
                'conta_id'   => $contaId,
                'portal_id'  => isset($portal) ? ($portal->id ?? null) : null,
                'cliente_id' => isset($portal) ? ($portal->cliente_id ?? null) : null,
                'tenant_id'  => isset($portal) ? ($portal->tenant_id ?? null) : null,
                'cep'        => $cepRaw,
                'cep_digits' => $cepDigits,
                'cep_len'    => strlen($cepDigits),
                'missing'    => $missingEndereco,
            ]);
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
                        $asaasStatusAtual = $invoiceData['status'] ?? $nota->asaas_status;
                        $pdfUrl = $invoiceData['pdfUrl'] ?? $invoiceData['invoiceUrl'] ?? $nota->asaas_pdf_url ?? null;
                        $numeroNf = $invoiceData['number'] ?? null;

                        // Atualiza banco com status e pdfUrl mais recentes
                        $updateFields = [
                            'asaas_status' => $asaasStatusAtual,
                            'status'       => AsaasService::mapearStatusNfsParaBanco($asaasStatusAtual),
                        ];
                        if ($pdfUrl && $pdfUrl !== $nota->asaas_pdf_url) {
                            $updateFields['asaas_pdf_url'] = $pdfUrl;
                        }
                        if ($numeroNf && empty($nota->numero_nf)) {
                            $updateFields['numero_nf'] = (string) $numeroNf;
                        }
                        $this->notaFiscalModel->update($id, $updateFields);

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
