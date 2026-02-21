<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\View;
use App\Core\Logger;
use App\Core\Auth;
use App\Core\Audit\AuditLogger;
use App\Models\NotaFiscal;
use App\Models\NotaFiscalImportacao;
use App\Models\Cliente;

class NotasFiscaisController extends Controller
{
    private NotaFiscal $model;
    private NotaFiscalImportacao $importModel;
    private Cliente $clienteModel;
    private Logger $logger;

    public function __construct()
    {
        $this->model = new NotaFiscal();
        $this->importModel = new NotaFiscalImportacao();
        $this->clienteModel = new Cliente();
        $this->logger = new Logger();
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
            'tab' => 'geral',
        ]);
    }

    public function store(): void
    {
        try {
            $usuarioId = Auth::user()->id;

            $clienteId = (int)($_POST['cliente_id'] ?? 0);
            $numeroNf = trim($_POST['numero_nf'] ?? '');
            $serie = trim($_POST['serie'] ?? '');
            $valorTotal = trim($_POST['valor_total'] ?? '');
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
                'usuario_id' => $usuarioId,
                'cliente_id' => $clienteId,
                'numero_nf' => $numeroNf,
                'serie' => $serie,
                'valor_total' => $valorTotal,
                'data_emissao' => $dataEmissao,
                'status' => $status,
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

        View::render('notas_fiscais/form-enterprise', [
            '_layout' => 'erp',
            'title' => 'Editar Nota Fiscal',
            'nota' => $nota,
            'clientes' => $clientes,
            'tab' => $_GET['tab'] ?? 'geral',
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

            $clienteId = (int)($_POST['cliente_id'] ?? 0);
            $numeroNf = trim($_POST['numero_nf'] ?? '');
            $serie = trim($_POST['serie'] ?? '');
            $valorTotal = trim($_POST['valor_total'] ?? '');
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
                'cliente_id' => $clienteId,
                'numero_nf' => $numeroNf,
                'serie' => $serie,
                'valor_total' => $valorTotal,
                'data_emissao' => $dataEmissao,
                'status' => $_POST['status'] ?? ($nota->status ?? 'rascunho'),
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
        $destRel = null;
        $notaId = null;

        try {
            if (!isset($_FILES['xml']) || $_FILES['xml']['error'] !== UPLOAD_ERR_OK) {
                header('Location: /faturamento/notas-fiscais/importar?error=upload_failed');
                exit();
            }

            $file = $_FILES['xml'];
            $maxSize = 5 * 1024 * 1024;
            if (($file['size'] ?? 0) > $maxSize) {
                header('Location: /faturamento/notas-fiscais/importar?error=file_too_large');
                exit();
            }

            $tmpPath = $file['tmp_name'];
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($tmpPath) ?: '';

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
            $destAbs = $baseDir . '/' . $safeName;

            if (!move_uploaded_file($tmpPath, $destAbs)) {
                header('Location: /faturamento/notas-fiscais/importar?error=upload_failed');
                exit();
            }

            $destRel = 'storage/uploads/notas_fiscais_importacoes/' . $usuarioId . '/' . $safeName;

            $importId = $this->importModel->create([
                'usuario_id' => $usuarioId,
                'arquivo_xml_path' => $destRel,
                'status' => 'falha',
                'mensagem' => 'Processando',
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
                'usuario_id' => $usuarioId,
                'cliente_id' => (int)$cliente->id,
                'numero_nf' => $parsed['numero_nf'] ?? '',
                'serie' => $parsed['serie'] ?? '',
                'valor_total' => $parsed['valor_total'] ?? '0.00',
                'data_emissao' => $parsed['data_emissao'] ?? '',
                'status' => 'importada',
                'xml_path' => $destRel,
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
                'import_id' => $importId,
                'nota_fiscal_id' => $notaId,
                'cliente_id' => (int)$cliente->id,
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

        $numero = trim((string)$xpath->evaluate('string(//nfe:infNFe/nfe:ide/nfe:nNF)'));
        $serie = trim((string)$xpath->evaluate('string(//nfe:infNFe/nfe:ide/nfe:serie)'));
        $dhEmi = trim((string)$xpath->evaluate('string(//nfe:infNFe/nfe:ide/nfe:dhEmi)'));
        $dEmi = trim((string)$xpath->evaluate('string(//nfe:infNFe/nfe:ide/nfe:dEmi)'));

        $data = $dhEmi !== '' ? substr($dhEmi, 0, 10) : ($dEmi !== '' ? $dEmi : '');

        $valor = trim((string)$xpath->evaluate('string(//nfe:infNFe/nfe:total/nfe:ICMSTot/nfe:vNF)'));
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
            'numero_nf' => $numero,
            'serie' => $serie,
            'data_emissao' => $data,
            'valor_total' => $valor,
            'documento' => $docDest,
        ];
    }
}
