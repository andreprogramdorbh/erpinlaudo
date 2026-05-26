<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\View;
use App\Core\Logger;
use App\Models\CrmProposta;
use App\Models\CrmOportunidade;
use App\Models\Cliente;
use App\Models\User;
use App\Models\Produto;
use App\Services\MailService;

class CrmPropostasController extends Controller
{
    private CrmProposta     $propostaModel;
    private CrmOportunidade  $opModel;
    private Cliente          $clienteModel;
    private User             $userModel;
    private Produto          $produtoModel;
    private Logger           $logger;
    public function __construct()
    {
        $this->propostaModel = new CrmProposta();
        $this->opModel       = new CrmOportunidade();
        $this->clienteModel  = new Cliente();
        $this->userModel     = new User();
        $this->produtoModel  = new Produto();
        $this->logger        = new Logger();
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

    // =========================================================================
    // GET /crm/propostas
    // =========================================================================
    public function index(): void
    {
        $uid     = $this->usuarioId();
        $filtros = [
            'status' => $_GET['status'] ?? '',
            'busca'  => $_GET['busca']  ?? '',
        ];

        $propostas = $this->propostaModel->findByUsuarioId($uid, $filtros);
        $kpis      = $this->propostaModel->kpisByUsuarioId($uid);

        View::render('crm/propostas/index', [
            '_layout'   => 'erp',
            'title'     => 'Propostas',
            'breadcrumb'=> ['CRM' => '/crm/funil', 'Propostas'],
            'propostas' => $propostas,
            'kpis'      => $kpis,
            'filtros'   => $filtros,
            'isAdmin'   => $this->isAdmin(),
        ]);
    }

    // =========================================================================
    // GET /crm/propostas/create
    // =========================================================================
    public function create(): void
    {
        $uid      = $this->usuarioId();
        $clientes = $this->clienteModel->findByUsuarioId($uid);

        View::render('crm/propostas/form', [
            '_layout'   => 'erp',
            'title'     => 'Nova Proposta',
            'breadcrumb'=> ['CRM' => '/crm/funil', 'Propostas' => '/crm/propostas', 'Nova Proposta'],
            'proposta'  => null,
            'itens'     => [],
            'historico' => [],
            'clientes'  => $clientes,
            'isEdit'    => false,
        ]);
    }

    // =========================================================================
    // POST /crm/propostas
    // =========================================================================
    public function store(): void
    {
        $uid  = $this->usuarioId();
        $data = $_POST;

        try {
            // Validações básicas
            if (empty($data['cliente_nome'])) {
                $this->jsonError('O nome do cliente é obrigatório.');
            }
            if (empty($data['titulo'])) {
                $this->jsonError('O título da proposta é obrigatório.');
            }
            if (empty($data['validade_proposta'])) {
                $this->jsonError('A validade da proposta é obrigatória.');
            }

            $numero = $this->propostaModel->gerarNumero($uid);
            $token  = bin2hex(random_bytes(32));

            $propostaId = $this->propostaModel->create([
                'usuario_id'           => $uid,
                'numero'               => $numero,
                'oportunidade_id'      => !empty($data['oportunidade_id']) ? (int) $data['oportunidade_id'] : null,
                'lead_id'              => !empty($data['lead_id'])         ? (int) $data['lead_id']         : null,
                'cliente_id'           => !empty($data['cliente_id'])      ? (int) $data['cliente_id']      : null,
                'cliente_nome'         => $data['cliente_nome']         ?? '',
                'cliente_razao_social' => $data['cliente_razao_social'] ?? '',
                'cliente_cnpj_cpf'     => $data['cliente_cnpj_cpf']     ?? '',
                'cliente_email'        => $data['cliente_email']        ?? '',
                'cliente_telefone'     => $data['cliente_telefone']     ?? '',
                'cliente_endereco'     => $data['cliente_endereco']     ?? '',
                'cliente_cidade'       => $data['cliente_cidade']       ?? '',
                'cliente_estado'       => $data['cliente_estado']       ?? '',
                'cliente_cep'          => $data['cliente_cep']          ?? '',
                'cliente_responsavel'  => $data['cliente_responsavel']  ?? '',
                'titulo'               => $data['titulo'],
                'descricao'            => $data['descricao']            ?? '',
                'validade_proposta'    => $data['validade_proposta'],
                'status'               => 'gerada',
                'prazo_entrega'        => $data['prazo_entrega']        ?? '',
                'condicao_pagamento'   => $data['condicao_pagamento']   ?? '',
                'frete_tipo'           => $data['frete_tipo']           ?? 'a_calcular',
                'frete_valor'          => (float) ($data['frete_valor'] ?? 0),
                'local_entrega'        => $data['local_entrega']        ?? '',
                'desconto_tipo'        => !empty($data['desconto_tipo']) ? $data['desconto_tipo'] : null,
                'desconto_valor'       => (float) ($data['desconto_valor'] ?? 0),
                'observacoes'          => $data['observacoes']          ?? '',
                'notas_internas'       => $data['notas_internas']       ?? '',
                'token_acesso'         => $token,
            ]);

            if (!$propostaId) {
                $this->jsonError('Falha ao criar proposta.');
            }

            // Salvar itens
            $itens = $this->extrairItens($data);
            if (!empty($itens)) {
                $this->propostaModel->salvarItens((int) $propostaId, $itens);
            }

            // Recalcular totais
            $this->propostaModel->recalcularTotais((int) $propostaId);

            // Registrar no histórico
            $this->propostaModel->updateStatus((int) $propostaId, 'gerada', $uid, 'Proposta criada.');

            $this->logger->info("[CrmProposta] Proposta #{$numero} criada. ID={$propostaId}");

            ob_start(); ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode([
                'success'    => true,
                'proposta_id' => $propostaId,
                'numero'     => $numero,
                'redirect'   => "/crm/propostas/{$propostaId}",
            ]);
            exit();

        } catch (\Throwable $e) {
            $this->logger->error('[CrmProposta] Erro ao criar: ' . $e->getMessage());
            $this->jsonError('Erro interno: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // GET /crm/propostas/{id}
    // =========================================================================
    public function show(int $id): void
    {
        $uid      = $this->usuarioId();
        $proposta = $this->propostaModel->findById($id);

        if (!$proposta || (int) $proposta->usuario_id !== $uid && !$this->isAdmin()) {
            header('Location: /crm/propostas');
            exit();
        }

        $itens     = $this->propostaModel->getItens($id);
        $historico = $this->propostaModel->getHistorico($id);
        $user      = $this->userModel->findById($uid);

        View::render('crm/propostas/show', [
            '_layout'   => 'erp',
            'title'     => 'Proposta ' . $proposta->numero,
            'breadcrumb'=> ['CRM' => '/crm/funil', 'Propostas' => '/crm/propostas', $proposta->numero],
            'proposta'  => $proposta,
            'itens'     => $itens,
            'historico' => $historico,
            'user'      => $user,
            'isAdmin'   => $this->isAdmin(),
        ]);
    }

    // =========================================================================
    // GET /crm/propostas/{id}/edit
    // =========================================================================
    public function edit(int $id): void
    {
        $uid      = $this->usuarioId();
        $proposta = $this->propostaModel->findById($id);

        if (!$proposta || (int) $proposta->usuario_id !== $uid && !$this->isAdmin()) {
            header('Location: /crm/propostas');
            exit();
        }

        $itens    = $this->propostaModel->getItens($id);
        $historico = $this->propostaModel->getHistorico($id);
        $clientes = $this->clienteModel->findByUsuarioId($uid);

        View::render('crm/propostas/form', [
            '_layout'   => 'erp',
            'title'     => 'Editar Proposta',
            'breadcrumb'=> ['CRM' => '/crm/funil', 'Propostas' => '/crm/propostas', 'Editar'],
            'proposta'  => $proposta,
            'itens'     => $itens,
            'historico' => $historico,
            'clientes'  => $clientes,
            'isEdit'    => true,
        ]);
    }

    // =========================================================================
    // POST /crm/propostas/{id}/update
    // =========================================================================
    public function update(int $id): void
    {
        $uid      = $this->usuarioId();
        $proposta = $this->propostaModel->findById($id);

        if (!$proposta || (int) $proposta->usuario_id !== $uid && !$this->isAdmin()) {
            $this->jsonError('Proposta não encontrada ou sem permissão.');
        }

        $data = $_POST;

        try {
            $this->propostaModel->update($id, [
                'cliente_nome'         => $data['cliente_nome']         ?? $proposta->cliente_nome,
                'cliente_razao_social' => $data['cliente_razao_social'] ?? '',
                'cliente_cnpj_cpf'     => $data['cliente_cnpj_cpf']     ?? '',
                'cliente_email'        => $data['cliente_email']        ?? '',
                'cliente_telefone'     => $data['cliente_telefone']     ?? '',
                'cliente_endereco'     => $data['cliente_endereco']     ?? '',
                'cliente_cidade'       => $data['cliente_cidade']       ?? '',
                'cliente_estado'       => $data['cliente_estado']       ?? '',
                'cliente_cep'          => $data['cliente_cep']          ?? '',
                'cliente_responsavel'  => $data['cliente_responsavel']  ?? '',
                'titulo'               => $data['titulo']               ?? $proposta->titulo,
                'descricao'            => $data['descricao']            ?? '',
                'validade_proposta'    => $data['validade_proposta']    ?? $proposta->validade_proposta,
                'prazo_entrega'        => $data['prazo_entrega']        ?? '',
                'condicao_pagamento'   => $data['condicao_pagamento']   ?? '',
                'frete_tipo'           => $data['frete_tipo']           ?? 'a_calcular',
                'frete_valor'          => (float) ($data['frete_valor'] ?? 0),
                'local_entrega'        => $data['local_entrega']        ?? '',
                'desconto_tipo'        => !empty($data['desconto_tipo']) ? $data['desconto_tipo'] : null,
                'desconto_valor'       => (float) ($data['desconto_valor'] ?? 0),
                'observacoes'          => $data['observacoes']          ?? '',
                'notas_internas'       => $data['notas_internas']       ?? '',
            ]);

            // Atualizar itens
            $itens = $this->extrairItens($data);
            $this->propostaModel->salvarItens($id, $itens);
            $this->propostaModel->recalcularTotais($id);

            ob_start(); ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode([
                'success'  => true,
                'redirect' => "/crm/propostas/{$id}",
            ]);
            exit();

        } catch (\Throwable $e) {
            $this->logger->error('[CrmProposta] Erro ao atualizar: ' . $e->getMessage());
            $this->jsonError('Erro interno: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // POST /crm/propostas/{id}/delete
    // =========================================================================
    public function delete(int $id): void
    {
        $uid      = $this->usuarioId();
        $proposta = $this->propostaModel->findById($id);

        if (!$proposta || (int) $proposta->usuario_id !== $uid && !$this->isAdmin()) {
            $this->jsonError('Proposta não encontrada ou sem permissão.');
        }

        $this->propostaModel->delete($id);

        ob_start(); ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'redirect' => '/crm/propostas']);
        exit();
    }

    // =========================================================================
    // POST /crm/propostas/{id}/enviar
    // =========================================================================
    public function enviar(int $id): void
    {
        $uid      = $this->usuarioId();
        $proposta = $this->propostaModel->findById($id);

        if (!$proposta || (int) $proposta->usuario_id !== $uid && !$this->isAdmin()) {
            $this->jsonError('Proposta não encontrada ou sem permissão.');
        }

        if (empty($proposta->cliente_email)) {
            $this->jsonError('O cliente não possui e-mail cadastrado. Edite a proposta e adicione o e-mail antes de enviar.');
        }

        try {
            $itens     = $this->propostaModel->getItens($id);
            $user      = $this->userModel->findById($uid);
            $pdfPath   = $this->gerarPdf($proposta, $itens, $user);

            $mail      = new MailService();
            $subject   = "Proposta Comercial {$proposta->numero} — {$proposta->titulo}";
            // Gerar ou reutilizar token de aceite
            $token = $proposta->token_acesso ?? null;
            if (empty($token)) {
                $token = bin2hex(random_bytes(32)); // 64 chars hex
                $this->propostaModel->update($id, ['token_acesso' => $token]);
                // Recarregar para garantir token salvo
                $proposta = $this->propostaModel->findById($id);
            }
            $baseUrl    = defined('BASE_URL') ? rtrim(BASE_URL, '/') : ('https://' . ($_SERVER['HTTP_HOST'] ?? 'erp.inlaudo.com.br'));
            $linkAceite = $baseUrl . '/proposta/aceite/' . $token;

            $body      = $this->montarEmailProposta($proposta, $user, $linkAceite);

            $mail->sendWithAttachment(
                $proposta->cliente_email,
                $subject,
                $body,
                $pdfPath,
                "Proposta_{$proposta->numero}.pdf",
                $proposta->cliente_nome
            );

            // Atualizar status
            $this->propostaModel->updateStatus($id, 'enviada', $uid, 'Proposta enviada por e-mail para ' . $proposta->cliente_email);

            // Salvar PDF
            $pdfDir  = BASE_PATH . '/storage/uploads/crm/propostas/' . $uid;
            if (!is_dir($pdfDir)) mkdir($pdfDir, 0755, true);
            $pdfDest = $pdfDir . '/proposta_' . $id . '.pdf';
            copy($pdfPath, $pdfDest);
            @unlink($pdfPath);

            $this->propostaModel->update($id, [
                'pdf_path' => 'storage/uploads/crm/propostas/' . $uid . '/proposta_' . $id . '.pdf',
            ]);

            $this->logger->info("[CrmProposta] Proposta #{$proposta->numero} enviada para {$proposta->cliente_email} | link aceite: {$linkAceite}");

            ob_start(); ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Proposta enviada com sucesso para ' . $proposta->cliente_email]);
            exit();

        } catch (\Throwable $e) {
            $this->logger->error('[CrmProposta] Erro ao enviar: ' . $e->getMessage());
            $this->jsonError('Erro ao enviar e-mail: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // GET /crm/propostas/{id}/pdf
    // =========================================================================
    public function pdf(int $id): void
    {
        $uid      = $this->usuarioId();
        $proposta = $this->propostaModel->findById($id);

        if (!$proposta || (int) $proposta->usuario_id !== $uid && !$this->isAdmin()) {
            http_response_code(403);
            echo 'Sem permissão.';
            exit();
        }

        try {
            $itens   = $this->propostaModel->getItens($id);
            $user    = $this->userModel->findById($uid);
            $pdfPath = $this->gerarPdf($proposta, $itens, $user);

            header('Content-Type: application/pdf');
            // inline: abre no visualizador do navegador; o usuario pode baixar pelo proprio viewer
            header('Content-Disposition: inline; filename="Proposta_' . $proposta->numero . '.pdf"');
            header('Content-Length: ' . filesize($pdfPath));
            readfile($pdfPath);
            @unlink($pdfPath);
            exit();

        } catch (\Throwable $e) {
            $this->logger->error('[CrmProposta] Erro ao gerar PDF: ' . $e->getMessage());
            http_response_code(500);
            echo 'Erro ao gerar PDF: ' . $e->getMessage();
            exit();
        }
    }

    // =========================================================================
    // POST /crm/propostas/{id}/status
    // =========================================================================
    public function atualizarStatus(int $id): void
    {
        $uid      = $this->usuarioId();
        $proposta = $this->propostaModel->findById($id);

        if (!$proposta || (int) $proposta->usuario_id !== $uid && !$this->isAdmin()) {
            $this->jsonError('Proposta não encontrada ou sem permissão.');
        }

        $status = $_POST['status'] ?? '';
        $obs    = $_POST['observacao'] ?? '';

        $validos = ['gerada', 'enviada', 'visualizada', 'aceita', 'recusada', 'expirada'];
        if (!in_array($status, $validos, true)) {
            $this->jsonError('Status inválido.');
        }

        $this->propostaModel->updateStatus($id, $status, $uid, $obs);

        ob_start(); ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit();
    }

    // =========================================================================
    // GET /crm/propostas/buscar-oportunidade  (AJAX)
    // =========================================================================
    public function buscarOportunidade(): void
    {
        $uid  = $this->usuarioId();
        $opId = (int) ($_GET['id'] ?? 0);

        if (!$opId) {
            $this->jsonError('ID da oportunidade não informado.');
        }

        $op = $this->propostaModel->buscarOportunidade($opId, $uid);
        if (!$op) {
            $this->jsonError('Oportunidade não encontrada.');
        }

        ob_start(); ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $op]);
        exit();
    }

    // =========================================================================
    // GET /crm/propostas/buscar-cliente  (AJAX)
    // =========================================================================
    public function buscarCliente(): void
    {
        $uid  = $this->usuarioId();
        $q    = trim($_GET['q'] ?? '');

        if (strlen($q) < 2) {
            ob_start(); ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'data' => []]);
            exit();
        }

        $clientes = $this->clienteModel->findByUsuarioId($uid, ['busca' => $q]);

        ob_start(); ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => array_slice($clientes, 0, 10)]);
        exit();
    }

    // =========================================================================
    // GET /crm/propostas/buscar-produto  (AJAX — placeholder para módulo estoque)
    // =========================================================================
    public function buscarProduto(): void
    {
        $uid = $this->usuarioId();
        $q   = trim($_GET['q'] ?? '');
        ob_start(); ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        if (strlen($q) < 1) {
            echo json_encode(['success' => true, 'data' => []]);
            exit();
        }
        try {
            $like = '%' . $q . '%';
            $stmt = $this->produtoModel->getPdo()->prepare(
                "SELECT id, codigo, nome, unidade_medida AS unidade,
                        preco_venda AS preco_unitario, preco_custo, markup_percentual AS margem_lucro,
                        estoque_atual, marca, modelo
                 FROM produtos
                 WHERE usuario_id = ?
                   AND status = 'ativo'
                   AND (nome LIKE ? OR codigo LIKE ?
                        OR (nome_tecnico IS NOT NULL AND nome_tecnico LIKE ?)
                        OR (marca IS NOT NULL AND marca LIKE ?)
                        OR (modelo IS NOT NULL AND modelo LIKE ?))
                 ORDER BY nome ASC
                 LIMIT 30"
            );
            $stmt->execute([$uid, $like, $like, $like, $like, $like]);
            $produtos = $stmt->fetchAll(\PDO::FETCH_OBJ);
            echo json_encode(['success' => true, 'data' => $produtos]);
        } catch (\Throwable $e) {
            // Fallback sem nome_tecnico (coluna pode nao existir ainda)
            try {
                $like = '%' . $q . '%';
                $stmt = $this->produtoModel->getPdo()->prepare(
                    "SELECT id, codigo, nome, unidade_medida AS unidade,
                            preco_venda AS preco_unitario, preco_custo, markup_percentual AS margem_lucro,
                            estoque_atual
                     FROM produtos
                     WHERE usuario_id = ?
                       AND status = 'ativo'
                       AND (nome LIKE ? OR codigo LIKE ?)
                     ORDER BY nome ASC
                     LIMIT 30"
                );
                $stmt->execute([$uid, $like, $like]);
                $produtos = $stmt->fetchAll(\PDO::FETCH_OBJ);
                echo json_encode(['success' => true, 'data' => $produtos]);
            } catch (\Throwable $e2) {
                $this->logger->error('[CrmProposta] buscarProduto: ' . $e2->getMessage());
                echo json_encode(['success' => false, 'data' => [], 'error' => 'Erro ao buscar produtos.']);
            }
        }
        exit();
    }

    // =========================================================================
    // Helpers privados
    // =========================================================================

    private function extrairItens(array $data): array
    {
        $itens = [];
        $descricoes = $data['item_descricao'] ?? [];

        foreach ($descricoes as $i => $desc) {
            if (empty(trim($desc))) continue;
            $itens[] = [
                'produto_id'     => $data['item_produto_id'][$i]    ?? null,
                'codigo'         => $data['item_codigo'][$i]         ?? '',
                'descricao'      => $desc,
                'unidade'        => $data['item_unidade'][$i]        ?? 'un',
                'quantidade'     => (float) ($data['item_quantidade'][$i]    ?? 1),
                'preco_custo'    => (float) ($data['item_preco_custo'][$i]   ?? 0),
                'margem_lucro'   => (float) ($data['item_margem'][$i]        ?? 0),
                'preco_unitario' => (float) ($data['item_preco_unitario'][$i] ?? 0),
                'desconto_item'  => (float) ($data['item_desconto'][$i]      ?? 0),
            ];
        }

        return $itens;
    }

    private function gerarPdf(object $proposta, array $itens, object|false $user): string
    {
        // ── FPDF: biblioteca PHP pura embutida no repositório ─────────────────
        // Compatível com qualquer hospedagem compartilhada (HostGator, cPanel etc.)
        // Não requer Composer, binários externos ou instalação de pacotes.
        require_once BASE_PATH . '/app/Lib/fpdf/fpdf.php';

        // Dados básicos
        $numero      = $proposta->numero;
        $titulo      = $proposta->titulo;
        $dataGeracao = date('d/m/Y H:i');
        $validade    = !empty($proposta->validade_proposta)
                       ? date('d/m/Y', strtotime($proposta->validade_proposta))
                       : '-';
        $dataCriacao = date('d/m/Y', strtotime($proposta->created_at));

        // ── Dados do Fornecedor: usa EmpresaConfig se cadastrado, senão usa User ──
        $empresaModel  = new \App\Models\EmpresaConfig();
        $empresaCfg    = $empresaModel->findByUsuarioId((int) $proposta->usuario_id);
        if ($empresaCfg && !empty($empresaCfg->razao_social)) {
            $fornecedorNome  = !empty($empresaCfg->nome_fantasia)
                               ? $empresaCfg->nome_fantasia
                               : $empresaCfg->razao_social;
            $fornecedorEmail = $empresaCfg->email_responsavel ?? '';
            $fornecedorDoc   = $empresaCfg->cpf_cnpj ?? '';
            if (strlen($fornecedorDoc) === 14) {
                $fornecedorDoc = preg_replace('/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/', '$1.$2.$3/$4-$5', $fornecedorDoc);
            } elseif (strlen($fornecedorDoc) === 11) {
                $fornecedorDoc = preg_replace('/^(\d{3})(\d{3})(\d{3})(\d{2})$/', '$1.$2.$3-$4', $fornecedorDoc);
            }
            $fornecedorEnd  = trim(
                ($empresaCfg->logradouro ?? '') . ', ' .
                ($empresaCfg->numero     ?? '') . ' - ' .
                ($empresaCfg->cidade     ?? '') . '/' .
                ($empresaCfg->estado     ?? ''), ', -/'
            );
            $fornecedorTel  = $empresaCfg->telefone ?? '';
            $fornecedorLogo = !empty($empresaCfg->logo_path)
                              ? BASE_PATH . '/' . ltrim($empresaCfg->logo_path, '/')
                              : '';
        } else {
            $fornecedorNome  = ($user !== false ? $user->name  : null) ?? 'Empresa';
            $fornecedorEmail = ($user !== false ? $user->email : null) ?? '';
            $fornecedorDoc   = '';
            $fornecedorEnd   = '';
            $fornecedorTel   = '';
            $fornecedorLogo  = '';
        }

        $clienteNome  = $proposta->cliente_nome ?? '';
        $clienteDoc   = $proposta->cliente_cnpj_cpf  ?? '';
        $clienteEmail = $proposta->cliente_email     ?? '';
        $clienteTel   = $proposta->cliente_telefone  ?? '';
        $clienteEnd   = trim(
            ($proposta->cliente_endereco ?? '') . ', ' .
            ($proposta->cliente_cidade   ?? '') . ' - ' .
            ($proposta->cliente_estado   ?? ''), ', -'
        );
        $clienteResp  = $proposta->cliente_responsavel ?? '';

        $subtotal  = number_format((float) $proposta->subtotal,            2, ',', '.');
        $descTotal = number_format((float) $proposta->desconto_total,      2, ',', '.');
        $frete     = number_format((float) ($proposta->frete_valor ?? 0),  2, ',', '.');
        $total     = number_format((float) $proposta->total,               2, ',', '.');
        $prazo     = $proposta->prazo_entrega      ?? '-';
        $pagamento = $proposta->condicao_pagamento ?? '-';
        $obs       = $proposta->observacoes        ?? '';

        // ── Instância FPDF ────────────────────────────────────────────────────
        $pdf = new \FPDF('P', 'mm', 'A4');
        $pdf->SetAutoPageBreak(true, 15);
        $pdf->AddPage();
        $pdf->SetMargins(15, 15, 15);

        // Função auxiliar para texto UTF-8 → Latin-1 (FPDF padrão)
        $enc = fn(string $s): string => iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $s);

        // ── CABEÇALHO ─────────────────────────────────────────────────────────
        $pdf->SetFillColor(26, 86, 219);   // azul
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Rect(0, 0, 210, 32, 'F');
        // Logo (se existir e for imagem válida)
        $logoX = 15;
        if (!empty($fornecedorLogo) && file_exists($fornecedorLogo)) {
            try {
                $pdf->Image($fornecedorLogo, 15, 4, 0, 24);
                $logoX = 50;
            } catch (\Throwable $__le) { $logoX = 15; }
        }
        $pdf->SetFont('Arial', 'B', 13);
        $pdf->SetXY($logoX, 6);
        $pdf->Cell(130 - ($logoX - 15), 8, $enc($fornecedorNome), 0, 0, 'L');
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetXY($logoX, 15);
        $infoLinha = trim(implode('  |  ', array_filter([$fornecedorEmail, $fornecedorDoc, $fornecedorTel])));
        $pdf->Cell(130 - ($logoX - 15), 5, $enc($infoLinha), 0, 0, 'L');
        if (!empty($fornecedorEnd)) {
            $pdf->SetXY($logoX, 21);
            $pdf->Cell(130 - ($logoX - 15), 5, $enc($fornecedorEnd), 0, 0, 'L');
        }
        // Badge número
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetTextColor(26, 86, 219);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Rect(148, 5, 47, 20, 'F');
        $pdf->SetXY(148, 7);
        $pdf->Cell(47, 5, 'PROPOSTA', 0, 2, 'C');
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->SetX(148);
        $pdf->Cell(47, 8, $enc($numero), 0, 0, 'C');

        // Título da proposta
        $pdf->SetTextColor(30, 30, 30);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->SetXY(15, 34);
        $pdf->MultiCell(180, 7, $enc($titulo), 0, 'L');
        $pdf->Ln(2);

        // ── PARTES ENVOLVIDAS ─────────────────────────────────────────────────
        $y = $pdf->GetY();
        $pdf->SetFillColor(240, 247, 255);
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetTextColor(26, 86, 219);
        $pdf->SetXY(15, $y);
        $pdf->Cell(180, 7, 'PARTES ENVOLVIDAS', 0, 1, 'L', true);
        $pdf->Ln(1);

        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->SetX(15); $pdf->Cell(85, 5, 'FORNECEDOR', 0, 0);
        $pdf->Cell(95, 5, 'TOMADOR / CLIENTE', 0, 1);

         $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetTextColor(30, 30, 30);
        $yPartes = $pdf->GetY();
        $pdf->SetX(15); $pdf->Cell(85, 5, $enc($fornecedorNome), 0, 0);
        $pdf->Cell(95, 5, $enc($clienteNome), 0, 1);
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(80, 80, 80);
        // Fornecedor: doc
        if (!empty($fornecedorDoc)) {
            $pdf->SetX(15); $pdf->Cell(85, 4, $enc($fornecedorDoc), 0, 0);
        } else {
            $pdf->SetX(15); $pdf->Cell(85, 4, '', 0, 0);
        }
        $pdf->Cell(95, 4, $enc($clienteDoc), 0, 1);
        // Fornecedor: email | Cliente: email
        if (!empty($fornecedorEmail)) {
            $pdf->SetX(15); $pdf->Cell(85, 4, $enc($fornecedorEmail), 0, 0);
        } else {
            $pdf->SetX(15); $pdf->Cell(85, 4, '', 0, 0);
        }
        $pdf->Cell(95, 4, $enc($clienteEmail), 0, 1);
        // Fornecedor: telefone | Cliente: telefone
        if (!empty($fornecedorTel)) {
            $pdf->SetX(15); $pdf->Cell(85, 4, $enc($fornecedorTel), 0, 0);
        } else {
            $pdf->SetX(15); $pdf->Cell(85, 4, '', 0, 0);
        }
        $pdf->Cell(95, 4, $enc($clienteTel), 0, 1);
        // Fornecedor: endereço | Cliente: endereço
        if (!empty($fornecedorEnd)) {
            $pdf->SetX(15); $pdf->MultiCell(85, 4, $enc($fornecedorEnd), 0, 'L');
        }
        if ($clienteEnd) {
            $pdf->SetXY(100, $yPartes + 5 + 4 + 4 + 4); // alinhar com a linha de endereço
            $pdf->MultiCell(95, 4, $enc($clienteEnd), 0, 'L');
        }
        if ($clienteResp) {
            $pdf->SetX(15); $pdf->Cell(85, 4, '', 0, 0);
            $pdf->Cell(95, 4, 'Resp: ' . $enc($clienteResp), 0, 1);
        }
        $pdf->Ln(3);

        // ── ITENS ─────────────────────────────────────────────────────────────
        $y = $pdf->GetY();
        $pdf->SetFillColor(240, 247, 255);
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetTextColor(26, 86, 219);
        $pdf->SetXY(15, $y);
        $pdf->Cell(180, 7, 'ITENS DA PROPOSTA', 0, 1, 'L', true);

        // Cabeçalho da tabela
        $pdf->SetFillColor(26, 86, 219);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetX(15);
        $pdf->Cell(8,  6, '#',          1, 0, 'C', true);
        $pdf->Cell(22, 6, 'Codigo',     1, 0, 'C', true);
        $pdf->Cell(72, 6, 'Descricao',  1, 0, 'L', true);
        $pdf->Cell(12, 6, 'Un.',        1, 0, 'C', true);
        $pdf->Cell(18, 6, 'Qtd.',       1, 0, 'R', true);
        $pdf->Cell(24, 6, 'Preco Un.',  1, 0, 'R', true);
        $pdf->Cell(24, 6, 'Total',      1, 1, 'R', true);

        // Linhas dos itens
        $pdf->SetFont('Arial', '', 8);
        foreach ($itens as $i => $item) {
            $fill = ($i % 2 === 0);
            $pdf->SetFillColor(248, 250, 252);
            $pdf->SetTextColor(30, 30, 30);
            $qtd   = number_format((float) $item->quantidade,     2, ',', '.');
            $preco = number_format((float) $item->preco_unitario, 2, ',', '.');
            $tot   = number_format((float) $item->total_item,     2, ',', '.');
            $pdf->SetX(15);
            $pdf->Cell(8,  6, ($i + 1),                       1, 0, 'C', $fill);
            $pdf->Cell(22, 6, $enc($item->codigo ?? ''),       1, 0, 'C', $fill);
            $pdf->Cell(72, 6, $enc($item->descricao),          1, 0, 'L', $fill);
            $pdf->Cell(12, 6, $enc($item->unidade ?? 'un'),    1, 0, 'C', $fill);
            $pdf->Cell(18, 6, $qtd,                            1, 0, 'R', $fill);
            $pdf->Cell(24, 6, 'R$ ' . $preco,                  1, 0, 'R', $fill);
            $pdf->Cell(24, 6, 'R$ ' . $tot,                    1, 1, 'R', $fill);
        }

        // Totais
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(30, 30, 30);
        $pdf->SetX(15);
        $pdf->Cell(156, 6, 'Subtotal:', 0, 0, 'R');
        $pdf->Cell(24,  6, 'R$ ' . $subtotal, 1, 1, 'R');
        if ((float) $proposta->desconto_total > 0) {
            $pdf->SetTextColor(220, 38, 38);
            $pdf->SetX(15);
            $pdf->Cell(156, 6, 'Desconto:', 0, 0, 'R');
            $pdf->Cell(24,  6, '- R$ ' . $descTotal, 1, 1, 'R');
            $pdf->SetTextColor(30, 30, 30);
        }
        if ((float) ($proposta->frete_valor ?? 0) > 0) {
            $pdf->SetX(15);
            $pdf->Cell(156, 6, 'Frete:', 0, 0, 'R');
            $pdf->Cell(24,  6, 'R$ ' . $frete, 1, 1, 'R');
        }
        $pdf->SetFillColor(26, 86, 219);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetX(15);
        $pdf->Cell(156, 7, 'TOTAL GERAL:', 0, 0, 'R', true);
        $pdf->Cell(24,  7, 'R$ ' . $total, 1, 1, 'R', true);
        $pdf->SetTextColor(30, 30, 30);
        $pdf->Ln(3);

        // ── CONDIÇÕES COMERCIAIS ──────────────────────────────────────────────
        $y = $pdf->GetY();
        $pdf->SetFillColor(240, 247, 255);
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetTextColor(26, 86, 219);
        $pdf->SetXY(15, $y);
        $pdf->Cell(180, 7, 'CONDICOES COMERCIAIS', 0, 1, 'L', true);
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(30, 30, 30);
        $pdf->SetX(15); $pdf->Cell(40, 5, 'Prazo de Entrega:', 0, 0);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(140, 5, $enc($prazo), 0, 1);
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetX(15); $pdf->Cell(40, 5, 'Cond. de Pagamento:', 0, 0);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(140, 5, $enc($pagamento), 0, 1);
        $pdf->Ln(2);

        // ── OBSERVAÇÕES ───────────────────────────────────────────────────────
        if (!empty(trim($obs))) {
            $y = $pdf->GetY();
            $pdf->SetFillColor(240, 247, 255);
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->SetTextColor(26, 86, 219);
            $pdf->SetXY(15, $y);
            $pdf->Cell(180, 7, 'OBSERVACOES', 0, 1, 'L', true);
            $pdf->SetFont('Arial', '', 8);
            $pdf->SetTextColor(30, 30, 30);
            $pdf->SetX(15);
            $pdf->MultiCell(180, 5, $enc($obs), 0, 'L');
            $pdf->Ln(2);
        }

        // ── VALIDADE ──────────────────────────────────────────────────────────
        $pdf->SetFillColor(254, 243, 199);
        $pdf->SetTextColor(120, 80, 0);
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetX(15);
        $pdf->Cell(180, 8, $enc('Esta proposta e valida ate: ' . $validade), 1, 1, 'C', true);
        $pdf->Ln(4);

        // ── ASSINATURAS ───────────────────────────────────────────────────────
        $y = $pdf->GetY();
        $pdf->SetFillColor(240, 247, 255);
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetTextColor(26, 86, 219);
        $pdf->SetXY(15, $y);
        $pdf->Cell(180, 7, 'ACEITE E ASSINATURAS', 0, 1, 'L', true);
        $pdf->Ln(12);
        $pdf->SetDrawColor(100, 100, 100);
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(30, 30, 30);
        $pdf->SetX(15);
        $pdf->Cell(80, 0, '', 'T', 0, 'C');
        $pdf->SetX(115);
        $pdf->Cell(80, 0, '', 'T', 1, 'C');
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetX(15); $pdf->Cell(80, 5, $enc($fornecedorNome), 0, 0, 'C');
        $pdf->SetX(115); $pdf->Cell(80, 5, $enc($clienteNome), 0, 1, 'C');
        $pdf->SetFont('Arial', '', 7);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->SetX(15); $pdf->Cell(80, 4, 'Fornecedor / Responsavel', 0, 0, 'C');
        $pdf->SetX(115); $pdf->Cell(80, 4, 'Tomador / Cliente', 0, 1, 'C');
        $pdf->Ln(4);

        // ── RODAPÉ ────────────────────────────────────────────────────────────
        $pdf->SetFillColor(249, 250, 251);
        $pdf->SetFont('Arial', '', 7);
        $pdf->SetTextColor(150, 150, 150);
        $pdf->SetX(15);
        $pdf->Cell(90, 5, $enc('Proposta ' . $numero . ' - Gerada em ' . $dataGeracao), 0, 0, 'L');
        $pdf->Cell(90, 5, 'Documento gerado pelo ERP InLaudo', 0, 1, 'R');

        // ── SALVAR ────────────────────────────────────────────────────────────
        $tmpPdf = sys_get_temp_dir() . '/proposta_' . $proposta->id . '_' . time() . '.pdf';
        $pdf->Output('F', $tmpPdf);

        if (!file_exists($tmpPdf) || filesize($tmpPdf) < 100) {
            throw new \RuntimeException('FPDF nao gerou o PDF.');
        }

        return $tmpPdf;
    }

    private function montarEmailProposta(object $proposta, object|false $user, string $linkAceite = ''): string
    {
        $numero   = htmlspecialchars($proposta->numero);
        $titulo   = htmlspecialchars($proposta->titulo);
        $total    = number_format((float) $proposta->total, 2, ',', '.');
        $validade = !empty($proposta->validade_proposta)
                    ? date('d/m/Y', strtotime($proposta->validade_proposta))
                    : '—';
        $remetente = htmlspecialchars(($user !== false ? $user->name : null) ?? 'Equipe Comercial');

        return <<<HTML
<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto">
  <div style="background:linear-gradient(135deg,#1a56db,#0e3a8c);color:#fff;padding:28px 32px;border-radius:8px 8px 0 0">
    <h1 style="margin:0;font-size:20px">Proposta Comercial</h1>
    <p style="margin:6px 0 0;opacity:.85">{$numero} — {$titulo}</p>
  </div>
  <div style="background:#fff;padding:28px 32px;border:1px solid #e5e7eb;border-top:none">
    <p style="color:#374151">Prezado(a) <strong>{$proposta->cliente_nome}</strong>,</p>
    <p style="color:#374151;margin-top:12px">
      Segue em anexo a proposta comercial <strong>{$numero}</strong> conforme solicitado.
    </p>
    <div style="background:#f0f7ff;border-radius:8px;padding:16px 20px;margin:20px 0">
      <div style="display:flex;justify-content:space-between;margin-bottom:8px">
        <span style="color:#6b7280;font-size:13px">Valor Total:</span>
        <span style="font-weight:700;color:#1a56db;font-size:16px">R$ {$total}</span>
      </div>
      <div style="display:flex;justify-content:space-between">
        <span style="color:#6b7280;font-size:13px">Válida até:</span>
        <span style="font-weight:600;color:#374151">{$validade}</span>
      </div>
    </div>
    <p style="color:#374151">
      Para aceitar esta proposta ou tirar dúvidas, entre em contato conosco.
    </p>
    {$linkBotao}
    <p style="color:#374151;margin-top:20px">Atenciosamente,<br><strong>{$remetente}</strong></p>
  </div>
  <div style="background:#f9fafb;padding:12px 32px;border:1px solid #e5e7eb;border-top:none;border-radius:0 0 8px 8px;font-size:11px;color:#9ca3af;text-align:center">
    Este e-mail foi enviado automaticamente pelo ERP InLaudo.
  </div>
</div>
HTML;
    }

    private function ifNotEmpty(string $val, string $html): string
    {
        return !empty(trim($val)) ? $html : '';
    }

    private function jsonError(string $msg): void
    {
        ob_start(); ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $msg]);
        exit();
    }
    // =========================================================================
    // GET /proposta/aceite/{token}  — página pública de aceite/assinatura
    // =========================================================================
    public function aceitePublico(string $token): void
    {
        $proposta = $this->propostaModel->findByToken($token);
        if (!$proposta) {
            http_response_code(404);
            View::render('crm/propostas/aceite_invalido', ['title' => 'Link inválido', '_layout' => 'public']);
            return;
        }
        // Registrar visualização (apenas uma vez por sessão)
        $sessKey = 'prop_view_' . $proposta->id;
        if (empty($_SESSION[$sessKey])) {
            $_SESSION[$sessKey] = 1;
            $this->propostaModel->registrarEventoAceite((int) $proposta->id, 'visualizado', [
                'ip'         => $_SERVER['REMOTE_ADDR'] ?? '',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            ]);
        }
        $itens = $this->propostaModel->getItens((int) $proposta->id);
        View::render('crm/propostas/aceite_publico', [
            'title'    => 'Proposta ' . $proposta->numero,
            '_layout'  => 'public',
            'proposta' => $proposta,
            'itens'    => $itens,
            'token'    => $token,
        ]);
    }

    // =========================================================================
    // POST /proposta/aceite/{token}  — salvar assinatura e aceite
    // =========================================================================
    public function registrarAceite(string $token): void
    {
        ob_start(); ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        $proposta = $this->propostaModel->findByToken($token);
        if (!$proposta) {
            echo json_encode(['success' => false, 'error' => 'Proposta não encontrada.']);
            exit();
        }
        if (in_array($proposta->status, ['aceita', 'recusada'], true)) {
            echo json_encode(['success' => false, 'error' => 'Esta proposta já foi ' . $proposta->status . '.']);
            exit();
        }
        $acao       = $_POST['acao'] ?? 'aceitar'; // aceitar | recusar
        $nomeAssina = trim($_POST['nome_assinante'] ?? '');
        $tipo       = $_POST['assinatura_tipo'] ?? 'nome_digitado'; // rubrica | nome_digitado
        $rubrImg    = $_POST['assinatura_imagem'] ?? ''; // base64 PNG
        $motivo     = trim($_POST['motivo_recusa'] ?? '');
        $ip         = $_SERVER['REMOTE_ADDR'] ?? '';
        $ua         = $_SERVER['HTTP_USER_AGENT'] ?? '';

        if ($acao === 'recusar') {
            $this->propostaModel->update((int) $proposta->id, [
                'status'          => 'recusada',
                'recusado_em'     => date('Y-m-d H:i:s'),
                'recusado_motivo' => $motivo,
            ]);
            $this->propostaModel->registrarEventoAceite((int) $proposta->id, 'recusado', [
                'nome_assinante' => $nomeAssina,
                'ip'             => $ip,
                'user_agent'     => $ua,
                'motivo_recusa'  => $motivo,
            ]);
            $this->propostaModel->updateStatus((int) $proposta->id, 'recusada', (int) $proposta->usuario_id, 'Proposta recusada pelo cliente via link público.');
            echo json_encode(['success' => true, 'acao' => 'recusada']);
            exit();
        }

        // Salvar imagem da rubrica se enviada
        $imgPath = null;
        if (!empty($rubrImg) && $tipo === 'rubrica') {
            $imgData = preg_replace('#^data:image/\w+;base64,#i', '', $rubrImg);
            $imgData = base64_decode($imgData);
            if ($imgData !== false) {
                $dir = BASE_PATH . '/storage/uploads/crm/assinaturas/' . $proposta->usuario_id;
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                $fname   = 'assinatura_prop_' . $proposta->id . '_' . time() . '.png';
                $fullPath = $dir . '/' . $fname;
                file_put_contents($fullPath, $imgData);
                $imgPath = 'storage/uploads/crm/assinaturas/' . $proposta->usuario_id . '/' . $fname;
            }
        }

        // Atualizar proposta
        $this->propostaModel->update((int) $proposta->id, [
            'status'                 => 'aceita',
            'aceito_em'              => date('Y-m-d H:i:s'),
            'aceito_por_nome'        => $nomeAssina,
            'aceito_por_ip'          => $ip,
            'assinatura_tipo'        => $tipo,
            'assinatura_imagem_path' => $imgPath,
        ]);
        $this->propostaModel->registrarEventoAceite((int) $proposta->id, 'aceito', [
            'nome_assinante'         => $nomeAssina,
            'ip'                     => $ip,
            'user_agent'             => $ua,
            'assinatura_tipo'        => $tipo,
            'assinatura_imagem_path' => $imgPath,
        ]);
        $this->propostaModel->updateStatus((int) $proposta->id, 'aceita', (int) $proposta->usuario_id, 'Proposta aceita e assinada pelo cliente via link público.');

        // Disparar alerta de e-mail para o responsável (best-effort)
        try {
            $user = $this->userModel->findById((int) $proposta->usuario_id);
            if ($user && !empty($user->email)) {
                $mail    = new MailService();
                $dataAce = date('d/m/Y H:i');
                $body    = "<p>A proposta <strong>{$proposta->numero}</strong> foi aceita e assinada por <strong>" . htmlspecialchars($nomeAssina ?: $proposta->cliente_nome) . "</strong> em {$dataAce}.</p><p>Acesse o ERP para gerar o Pedido de Venda.</p>";
                $mail->send($user->email, "Proposta {$proposta->numero} aceita por {$proposta->cliente_nome}", $body, $user->name ?? null);
            }
        } catch (\Throwable $eAlert) {
            $this->logger->warning('[CrmProposta] Alerta aceite não enviado: ' . $eAlert->getMessage());
        }

        $this->logger->info("[CrmProposta] Proposta #{$proposta->numero} aceita por {$nomeAssina} ({$ip})");
        echo json_encode(['success' => true, 'acao' => 'aceita', 'nome' => $nomeAssina]);
        exit();
    }


}