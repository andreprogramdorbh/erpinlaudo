<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\View;
use App\Core\Logger;
use App\Core\Auth;
use App\Core\Audit\AuditLogger;
use App\Models\NotaFiscal;
use App\Models\NotaFiscalAnexo;
use App\Models\NotaFiscalImportacao;
use App\Models\Cliente;

class NotasFiscaisController extends Controller
{
    private NotaFiscal $model;
    private NotaFiscalAnexo $anexoModel;
    private NotaFiscalImportacao $importModel;
    private Cliente $clienteModel;
    private Logger $logger;

    public function __construct()
    {
        $this->model       = new NotaFiscal();
        $this->anexoModel  = new NotaFiscalAnexo();
        $this->importModel = new NotaFiscalImportacao();
        $this->clienteModel = new Cliente();
        $this->logger      = new Logger();
    }

    public function index(): void
    {
        try {
            $usuarioId = Auth::user()->id;

            $filtros = [
                'status' => $_GET['status'] ?? '',
                'pesquisa' => $_GET['q'] ?? '',
            ];

            $notas = $this->model->findByUsuarioId($usuarioId, $filtros);

            View::render('notas_fiscais/index', [
                '_layout' => 'erp',
                'title' => 'Notas Fiscais',
                'breadcrumb' => [
                    'Faturamento' => '/faturamento/notas-fiscais',
                    0 => 'Notas Fiscais',
                ],
                'notas' => $notas,
                'filtros' => $filtros,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erro ao listar notas fiscais: ' . $e->getMessage());
            header('Location: /dashboard?error=1');
            exit();
        }
    }

    public function create(): void
    {
        $usuarioId = Auth::user()->id;
        $clientes = $this->clienteModel->findByUsuarioId($usuarioId, ['status' => 'ativo', 'pesquisa' => '', 'uf' => '']);

        View::render('notas_fiscais/form-enterprise', [
            '_layout' => 'erp',
            'title' => 'Nova Nota Fiscal',
            'nota' => null,
            'clientes' => $clientes,
            'anexos' => [],
            'tab' => 'geral',
        ]);
    }

    public function store(): void
    {
        try {
            $usuarioId = Auth::user()->id;

            $clienteId   = (int)($_POST['cliente_id'] ?? 0);
            $numeroNf    = trim($_POST['numero_nf'] ?? '');
            $serie       = trim($_POST['serie'] ?? '');
            $valorTotal  = trim($_POST['valor_total'] ?? '');
            $dataEmissao = $_POST['data_emissao'] ?? '';

            if ($clienteId <= 0 || $numeroNf === '' || $serie === '' || $valorTotal === '' || $dataEmissao === '') {
                header('Location: /faturamento/notas-fiscais/create?error=missing_fields');
                exit();
            }

            $cliente = $this->clienteModel->findById($clienteId);
            if (!$cliente || (int)$cliente->usuario_id !== (int)$usuarioId) {
                header('Location: /faturamento/notas-fiscais/create?error=invalid_cliente');
                exit();
            }

            $status = $_POST['status'] ?? 'rascunho';

            $dados = [
                'usuario_id'  => $usuarioId,
                'cliente_id'  => $clienteId,
                'numero_nf'   => $numeroNf,
                'serie'       => $serie,
                'valor_total' => $valorTotal,
                'data_emissao'=> $dataEmissao,
                'status'      => $status,
            ];

            $id = $this->model->create($dados);
            if ($id) {
                AuditLogger::log('create_nota_fiscal', ['id' => $id, 'numero_nf' => $numeroNf, 'serie' => $serie]);
                header("Location: /faturamento/notas-fiscais/edit/{$id}?success=created");
            } else {
                header('Location: /faturamento/notas-fiscais/create?error=db_failure');
            }
        } catch (\Exception $e) {
            $this->logger->error('Erro ao criar nota fiscal: ' . $e->getMessage());
            header('Location: /faturamento/notas-fiscais/create?error=fatal');
        }
        exit();
    }

    public function edit($id): void
    {
        $usuarioId = Auth::user()->id;
        $nota = $this->model->findById((int)$id);

        if (!$nota || (int)$nota->usuario_id !== (int)$usuarioId) {
            header('Location: /faturamento/notas-fiscais?error=not_found');
            exit();
        }

        $clientes = $this->clienteModel->findByUsuarioId($usuarioId, ['status' => 'ativo', 'pesquisa' => '', 'uf' => '']);
        $anexos   = $this->anexoModel->findByNotaId((int)$id, $usuarioId);

        View::render('notas_fiscais/form-enterprise', [
            '_layout' => 'erp',
            'title'   => 'Editar Nota Fiscal',
            'nota'    => $nota,
            'clientes'=> $clientes,
            'anexos'  => $anexos,
            'tab'     => $_GET['tab'] ?? 'geral',
        ]);
    }

    public function update($id): void
    {
        try {
            $usuarioId = Auth::user()->id;
            $nota = $this->model->findById((int)$id);

            if (!$nota || (int)$nota->usuario_id !== (int)$usuarioId) {
                header('Location: /faturamento/notas-fiscais?error=unauthorized');
                exit();
            }

            $clienteId   = (int)($_POST['cliente_id'] ?? 0);
            $numeroNf    = trim($_POST['numero_nf'] ?? '');
            $serie       = trim($_POST['serie'] ?? '');
            $valorTotal  = trim($_POST['valor_total'] ?? '');
            $dataEmissao = $_POST['data_emissao'] ?? '';

            if ($clienteId <= 0 || $numeroNf === '' || $serie === '' || $valorTotal === '' || $dataEmissao === '') {
                header("Location: /faturamento/notas-fiscais/edit/{$id}?error=missing_fields");
                exit();
            }

            $cliente = $this->clienteModel->findById($clienteId);
            if (!$cliente || (int)$cliente->usuario_id !== (int)$usuarioId) {
                header("Location: /faturamento/notas-fiscais/edit/{$id}?error=invalid_cliente");
                exit();
            }

            $dados = [
                'cliente_id'  => $clienteId,
                'numero_nf'   => $numeroNf,
                'serie'       => $serie,
                'valor_total' => $valorTotal,
                'data_emissao'=> $dataEmissao,
                'status'      => $_POST['status'] ?? ($nota->status ?? 'rascunho'),
            ];

            if ($this->model->update((int)$id, $dados)) {
                AuditLogger::log('update_nota_fiscal', ['id' => (int)$id, 'numero_nf' => $numeroNf, 'serie' => $serie]);
                header("Location: /faturamento/notas-fiscais/edit/{$id}?success=updated");
            } else {
                header("Location: /faturamento/notas-fiscais/edit/{$id}?error=db_failure");
            }
        } catch (\Exception $e) {
            $this->logger->error('Erro ao atualizar nota fiscal: ' . $e->getMessage());
            header("Location: /faturamento/notas-fiscais/edit/{$id}?error=fatal");
        }
        exit();
    }

    public function delete($id): void
    {
        try {
            $usuarioId = Auth::user()->id;
            $nota = $this->model->findById((int)$id);

            if (!$nota || (int)$nota->usuario_id !== (int)$usuarioId) {
                header('Location: /faturamento/notas-fiscais?error=unauthorized');
                exit();
            }

            if ($this->model->cancel((int)$id)) {
                AuditLogger::log('delete_nota_fiscal', ['id' => (int)$id, 'numero_nf' => $nota->numero_nf ?? null, 'serie' => $nota->serie ?? null]);
                header('Location: /faturamento/notas-fiscais?success=deleted');
            } else {
                header('Location: /faturamento/notas-fiscais?error=db_failure');
            }
        } catch (\Exception $e) {
            $this->logger->error('Erro ao cancelar nota fiscal: ' . $e->getMessage());
            header('Location: /faturamento/notas-fiscais?error=fatal');
        }
        exit();
    }

    // ---------------------------------------------------------------
    // POST /faturamento/notas-fiscais/anexos/upload
    // Upload de anexo (PDF, XML, JPG) — máximo 10 MB
    // ---------------------------------------------------------------
    public function uploadAnexo(): void
    {
        $notaId = 0;
        try {
            $usuarioId = Auth::user()->id;
            $notaId    = (int)($_POST['nota_fiscal_id'] ?? 0);

            // LOG DETALHADO PARA DEBUG
            $this->logger->info('uploadAnexo_NF_debug', [
                'usuario_id'         => $usuarioId,
                'nota_fiscal_id_raw' => $_POST['nota_fiscal_id'] ?? 'NAO_ENVIADO',
                'nota_id_parsed'     => $notaId,
                'files_error'        => $_FILES['anexo']['error'] ?? 'SEM_ARQUIVO',
                'files_size'         => $_FILES['anexo']['size'] ?? 0,
                'files_name'         => $_FILES['anexo']['name'] ?? 'SEM_NOME',
                'post_keys'          => implode(',', array_keys($_POST)),
            ]);

            if ($notaId <= 0) {
                $this->logger->error('uploadAnexo_NF: nota_fiscal_id invalido');
                header('Location: /faturamento/notas-fiscais?error=invalid_nota');
                exit();
            }

            // Verifica se a nota pertence ao tenant
            $nota = $this->model->findById($notaId);
            $this->logger->info('uploadAnexo_NF_findById', [
                'nota_encontrada'    => $nota ? 'SIM' : 'NAO',
                'nota_usuario_id'    => $nota ? $nota->usuario_id : 'N/A',
                'usuario_id_sessao'  => $usuarioId,
                'match'              => $nota ? ((int)$nota->usuario_id === (int)$usuarioId ? 'SIM' : 'NAO') : 'N/A',
            ]);
            if (!$nota || (int)$nota->usuario_id !== (int)$usuarioId) {
                $this->logger->error('uploadAnexo_NF: unauthorized', ['nota_id' => $notaId, 'usuario_id' => $usuarioId]);
                header("Location: /faturamento/notas-fiscais/edit/{$notaId}?error=unauthorized&tab=anexos");
                exit();
            }

            if (!isset($_FILES['anexo']) || $_FILES['anexo']['error'] !== UPLOAD_ERR_OK) {
                header("Location: /faturamento/notas-fiscais/edit/{$notaId}?error=upload_failed&tab=anexos");
                exit();
            }

            $file    = $_FILES['anexo'];
            $maxSize = 10 * 1024 * 1024; // 10 MB

            if (($file['size'] ?? 0) > $maxSize) {
                header("Location: /faturamento/notas-fiscais/edit/{$notaId}?error=file_too_large&tab=anexos");
                exit();
            }

            $tmpPath = $file['tmp_name'];
            $finfo   = new \finfo(FILEINFO_MIME_TYPE);
            $mime    = $finfo->file($tmpPath) ?: '';

            $allowed = [
                'application/pdf' => 'pdf',
                'image/jpeg'      => 'jpg',
                'image/jpg'       => 'jpg',
                'text/xml'        => 'xml',
                'application/xml' => 'xml',
                // Excel (legacy + OpenXML)
                'application/vnd.ms-excel' => 'xls',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
                'application/vnd.ms-excel.sheet.macroEnabled.12' => 'xlsm',
                'application/vnd.ms-excel.sheet.binary.macroEnabled.12' => 'xlsb',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.template' => 'xltx',
                'application/vnd.ms-excel.template.macroEnabled.12' => 'xltm',
            ];

            $excelExts = ['xls', 'xlsx', 'xlsm', 'xlsb', 'xlt', 'xltx', 'xltm'];
            $excelFallbackMimes = [
                'application/zip',
                'application/octet-stream',
                'application/vnd.ms-office',
                'application/x-ole-storage',
                'application/cdfv2',
            ];

            $ext = $allowed[$mime] ?? null;
            if ($ext === null) {
                $origName = (string) ($file['name'] ?? '');
                $origExt = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
                if (in_array($origExt, $excelExts, true)) {
                    if (in_array($mime, $excelFallbackMimes, true) ||
                        str_starts_with($mime, 'application/vnd.ms-excel') ||
                        str_starts_with($mime, 'application/vnd.openxmlformats')) {
                        $ext = $origExt;
                    }
                }
            }

            if ($ext === null) {
                $this->logger->warning('uploadAnexo_NF: invalid_file_type', [
                    'nota_fiscal_id' => $notaId,
                    'mime' => $mime,
                    'original_name' => $file['name'] ?? '',
                ]);
                header("Location: /faturamento/notas-fiscais/edit/{$notaId}?error=invalid_file_type&tab=anexos");
                exit();
            }

            $baseDir = BASE_PATH . '/storage/uploads/notas_fiscais_anexos/' . $usuarioId . '/' . $notaId;
            if (!is_dir($baseDir)) {
                if (!mkdir($baseDir, 0755, true) && !is_dir($baseDir)) {
                    $this->logger->error('Falha ao criar diretório de upload (notas_fiscais_anexos): ' . $baseDir . ' | BASE_PATH=' . BASE_PATH);
                    header("Location: /faturamento/notas-fiscais/edit/{$notaId}?error=upload_failed&tab=anexos");
                    exit();
                }
            }

            $safeName = bin2hex(random_bytes(16)) . '.' . $ext;
            $destPath = $baseDir . '/' . $safeName;

            if (!move_uploaded_file($tmpPath, $destPath)) {
                header("Location: /faturamento/notas-fiscais/edit/{$notaId}?error=upload_failed&tab=anexos");
                exit();
            }

            $relativePath = 'storage/uploads/notas_fiscais_anexos/' . $usuarioId . '/' . $notaId . '/' . $safeName;

            $anexoId = $this->anexoModel->create([
                'usuario_id'     => $usuarioId,
                'nota_fiscal_id' => $notaId,
                'file_path'      => $relativePath,
                'original_name'  => $file['name'] ?? 'anexo',
                'mime_type'      => $mime,
                'file_size'      => $file['size'] ?? null,
            ]);

            if ($anexoId) {
                AuditLogger::log('upload_nota_fiscal_anexo', ['id' => $anexoId, 'nota_fiscal_id' => $notaId]);
                header("Location: /faturamento/notas-fiscais/edit/{$notaId}?success=upload&tab=anexos");
            } else {
                @unlink($destPath);
                $this->logger->error('Falha ao salvar anexo no banco (nota_fiscal_id=' . $notaId . ')');
                header("Location: /faturamento/notas-fiscais/edit/{$notaId}?error=db_failure&tab=anexos");
            }
        } catch (\Exception $e) {
            $this->logger->error('Erro ao enviar anexo (notas fiscais): ' . $e->getMessage());
            if ($notaId > 0) {
                header("Location: /faturamento/notas-fiscais/edit/{$notaId}?error=fatal&tab=anexos");
            } else {
                header('Location: /faturamento/notas-fiscais?error=fatal');
            }
        }
        exit();
    }

    // ---------------------------------------------------------------
    // POST /faturamento/notas-fiscais/anexos/delete/{id}
    // ---------------------------------------------------------------
    public function deleteAnexo($id): void
    {
        try {
            $usuarioId = Auth::user()->id;
            $anexo     = $this->anexoModel->findById((int)$id);

            if (!$anexo || (int)$anexo->usuario_id !== (int)$usuarioId) {
                header('Location: /faturamento/notas-fiscais?error=unauthorized');
                exit();
            }

            $notaId  = (int)($anexo->nota_fiscal_id ?? 0);
            $filePath = BASE_PATH . '/' . ltrim((string)($anexo->file_path ?? ''), '/');

            if ($this->anexoModel->delete((int)$id)) {
                if (is_file($filePath)) {
                    @unlink($filePath);
                }
                AuditLogger::log('delete_nota_fiscal_anexo', ['id' => (int)$id, 'nota_fiscal_id' => $notaId]);
                header("Location: /faturamento/notas-fiscais/edit/{$notaId}?success=deleted_anexo&tab=anexos");
            } else {
                $this->logger->error('Falha ao excluir anexo do banco (id=' . $id . ')');
                header("Location: /faturamento/notas-fiscais/edit/{$notaId}?error=db_failure&tab=anexos");
            }
        } catch (\Exception $e) {
            $this->logger->error('Erro ao remover anexo (notas fiscais): ' . $e->getMessage());
            header('Location: /faturamento/notas-fiscais?error=fatal');
        }
        exit();
    }

    // ---------------------------------------------------------------
    // GET /faturamento/notas-fiscais/anexos/download/{id}
    // ---------------------------------------------------------------
    public function downloadAnexo($id): void
    {
        try {
            $usuarioId = Auth::user()->id;
            $anexo     = $this->anexoModel->findById((int)$id);

            if (!$anexo || (int)$anexo->usuario_id !== (int)$usuarioId) {
                http_response_code(403);
                echo '403 - Acesso Negado';
                exit();
            }

            $fileRel = (string)($anexo->file_path ?? '');
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
            $this->logger->error('Erro ao baixar anexo (notas fiscais): ' . $e->getMessage());
            http_response_code(500);
            echo 'Erro ao baixar arquivo';
            exit();
        }
    }

    public function importForm(): void
    {
        View::render('notas_fiscais/importar', [
            '_layout' => 'erp',
            'title' => 'Importar XML de NF-e',
            'breadcrumb' => [
                'Faturamento' => '/faturamento/notas-fiscais',
                'Notas Fiscais' => '/faturamento/notas-fiscais',
                0 => 'Importar XML',
            ],
        ]);
    }

    public function importStore(): void
    {
        $usuarioId = Auth::user()->id;

        $importId = null;
        $destRel  = null;
        $notaId   = null;

        try {
            if (!isset($_FILES['xml']) || $_FILES['xml']['error'] !== UPLOAD_ERR_OK) {
                header('Location: /faturamento/notas-fiscais/importar?error=upload_failed');
                exit();
            }

            $file    = $_FILES['xml'];
            $maxSize = 5 * 1024 * 1024;
            if (($file['size'] ?? 0) > $maxSize) {
                header('Location: /faturamento/notas-fiscais/importar?error=file_too_large');
                exit();
            }

            $tmpPath = $file['tmp_name'];
            $finfo   = new \finfo(FILEINFO_MIME_TYPE);
            $mime    = $finfo->file($tmpPath) ?: '';

            $allowedMimes = [
                'text/xml',
                'application/xml',
                'application/octet-stream',
            ];

            if (!in_array($mime, $allowedMimes, true)) {
                header('Location: /faturamento/notas-fiscais/importar?error=invalid_file_type');
                exit();
            }

            $baseDir = BASE_PATH . '/storage/uploads/notas_fiscais_importacoes/' . $usuarioId;
            if (!is_dir($baseDir)) {
                mkdir($baseDir, 0755, true);
            }

            $safeName = bin2hex(random_bytes(16)) . '.xml';
            $destAbs  = $baseDir . '/' . $safeName;

            if (!move_uploaded_file($tmpPath, $destAbs)) {
                header('Location: /faturamento/notas-fiscais/importar?error=upload_failed');
                exit();
            }

            $destRel = 'storage/uploads/notas_fiscais_importacoes/' . $usuarioId . '/' . $safeName;

            $importId = $this->importModel->create([
                'usuario_id'       => $usuarioId,
                'arquivo_xml_path' => $destRel,
                'status'           => 'falha',
                'mensagem'         => 'Processando',
            ]);

            $parsed = $this->parseNfeXml($destAbs);

            $doc = preg_replace('/\D/', '', (string)($parsed['documento'] ?? ''));
            if ($doc === '') {
                throw new \RuntimeException('Documento do destinatário não encontrado no XML.');
            }

            $cliente = $this->clienteModel->findByCpfCnpjAndUsuarioId($doc, $usuarioId);
            if (!$cliente) {
                throw new \RuntimeException('Cliente não encontrado para o CPF/CNPJ ' . $doc . '.');
            }

            $dados = [
                'usuario_id'   => $usuarioId,
                'cliente_id'   => (int)$cliente->id,
                'numero_nf'    => $parsed['numero_nf'] ?? '',
                'serie'        => $parsed['serie'] ?? '',
                'valor_total'  => $parsed['valor_total'] ?? '0.00',
                'data_emissao' => $parsed['data_emissao'] ?? '',
                'status'       => 'importada',
                'xml_path'     => $destRel,
            ];

            if (trim($dados['numero_nf']) === '' || trim($dados['serie']) === '' || trim($dados['data_emissao']) === '') {
                throw new \RuntimeException('XML inválido: não foi possível extrair número, série ou data de emissão.');
            }

            $notaId = $this->model->create($dados);
            if (!$notaId) {
                throw new \RuntimeException('Falha ao salvar a Nota Fiscal no banco de dados.');
            }

            if ($importId) {
                $this->importModel->updateStatus((int)$importId, 'sucesso', 'NF importada com sucesso (ID ' . $notaId . ').');
            }

            AuditLogger::log('import_nota_fiscal', [
                'import_id'      => $importId,
                'nota_fiscal_id' => $notaId,
                'cliente_id'     => (int)$cliente->id,
            ]);

            header("Location: /faturamento/notas-fiscais/edit/{$notaId}?success=imported");
            exit();
        } catch (\Exception $e) {
            $this->logger->error('Erro ao importar XML de nota fiscal: ' . $e->getMessage());

            if ($importId) {
                $this->importModel->updateStatus((int)$importId, 'falha', $e->getMessage());
            }

            if ($destRel) {
                AuditLogger::log('import_nota_fiscal_failed', ['import_id' => $importId, 'xml_path' => $destRel, 'error' => $e->getMessage()]);
            } else {
                AuditLogger::log('import_nota_fiscal_failed', ['import_id' => $importId, 'error' => $e->getMessage()]);
            }

            header('Location: /faturamento/notas-fiscais/importar?error=import_failed');
            exit();
        }
    }

    private function parseNfeXml(string $absPath): array
    {
        $doc = new \DOMDocument();
        $old = libxml_use_internal_errors(true);
        $loaded = $doc->load($absPath);
        libxml_clear_errors();
        libxml_use_internal_errors($old);

        if (!$loaded) {
            throw new \RuntimeException('Não foi possível ler o XML.');
        }

        $xpath = new \DOMXPath($doc);
        $xpath->registerNamespace('nfe', 'http://www.portalfiscal.inf.br/nfe');

        $numero  = trim((string)$xpath->evaluate('string(//nfe:infNFe/nfe:ide/nfe:nNF)'));
        $serie   = trim((string)$xpath->evaluate('string(//nfe:infNFe/nfe:ide/nfe:serie)'));
        $dhEmi   = trim((string)$xpath->evaluate('string(//nfe:infNFe/nfe:ide/nfe:dhEmi)'));
        $dEmi    = trim((string)$xpath->evaluate('string(//nfe:infNFe/nfe:ide/nfe:dEmi)'));
        $data    = $dhEmi !== '' ? substr($dhEmi, 0, 10) : ($dEmi !== '' ? $dEmi : '');
        $valor   = trim((string)$xpath->evaluate('string(//nfe:infNFe/nfe:total/nfe:ICMSTot/nfe:vNF)'));
        $docDest = trim((string)$xpath->evaluate('string(//nfe:infNFe/nfe:dest/nfe:CNPJ)'));
        if ($docDest === '') {
            $docDest = trim((string)$xpath->evaluate('string(//nfe:infNFe/nfe:dest/nfe:CPF)'));
        }

        if ($numero === '') {
            $numero = trim((string)$xpath->evaluate("string(//*[local-name()='nNF'])"));
        }
        if ($serie === '') {
            $serie = trim((string)$xpath->evaluate("string(//*[local-name()='serie'])"));
        }
        if ($data === '') {
            $alt = trim((string)$xpath->evaluate("string(//*[local-name()='dhEmi'])"));
            if ($alt !== '') {
                $data = substr($alt, 0, 10);
            } else {
                $data = trim((string)$xpath->evaluate("string(//*[local-name()='dEmi'])"));
            }
        }
        if ($valor === '') {
            $valor = trim((string)$xpath->evaluate("string(//*[local-name()='vNF'])"));
        }
        if ($docDest === '') {
            $docDest = trim((string)$xpath->evaluate("string(//*[local-name()='dest']/*[local-name()='CNPJ'])"));
            if ($docDest === '') {
                $docDest = trim((string)$xpath->evaluate("string(//*[local-name()='dest']/*[local-name()='CPF'])"));
            }
        }

        $valor = str_replace(',', '.', $valor);
        if ($valor === '') {
            $valor = '0.00';
        }

        return [
            'numero_nf'    => $numero,
            'serie'        => $serie,
            'data_emissao' => $data,
            'valor_total'  => $valor,
            'documento'    => $docDest,
        ];
    }
}
