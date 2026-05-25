<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\View;
use App\Core\Logger;
use App\Models\CrmProposta;
use App\Models\CrmOportunidade;
use App\Models\Cliente;
use App\Models\User;
use App\Services\MailService;

class CrmPropostasController extends Controller
{
    private CrmProposta    $propostaModel;
    private CrmOportunidade $opModel;
    private Cliente        $clienteModel;
    private User           $userModel;
    private Logger         $logger;

    public function __construct()
    {
        $this->propostaModel = new CrmProposta();
        $this->opModel       = new CrmOportunidade();
        $this->clienteModel  = new Cliente();
        $this->userModel     = new User();
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
            $body      = $this->montarEmailProposta($proposta, $user);

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

            $this->logger->info("[CrmProposta] Proposta #{$proposta->numero} enviada para {$proposta->cliente_email}");

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
            header('Content-Disposition: attachment; filename="Proposta_' . $proposta->numero . '.pdf"');
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
        $q = trim($_GET['q'] ?? '');

        // Placeholder — quando o módulo de estoque for criado,
        // este método buscará na tabela de produtos
        $produtos = [];

        ob_start(); ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data'    => $produtos,
            'aviso'   => 'Módulo de estoque ainda não disponível. Cadastre os itens manualmente.',
        ]);
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
        $numero       = htmlspecialchars($proposta->numero);
        $titulo       = htmlspecialchars($proposta->titulo);
        $dataGeracao  = date('d/m/Y H:i');
        $validade     = !empty($proposta->validade_proposta)
                        ? date('d/m/Y', strtotime($proposta->validade_proposta))
                        : '—';
        $dataCriacao  = date('d/m/Y', strtotime($proposta->created_at));

        // Dados do fornecedor (empresa do usuário)
        $fornecedorNome  = htmlspecialchars(($user !== false ? $user->name  : null) ?? 'Empresa');
        $fornecedorEmail = htmlspecialchars(($user !== false ? $user->email : null) ?? '');

        // Dados do tomador (cliente)
        $clienteNome     = htmlspecialchars($proposta->cliente_nome);
        $clienteDoc      = htmlspecialchars($proposta->cliente_cnpj_cpf ?? '');
        $clienteEmail    = htmlspecialchars($proposta->cliente_email    ?? '');
        $clienteTel      = htmlspecialchars($proposta->cliente_telefone ?? '');
        $clienteEnd      = htmlspecialchars(
            trim(($proposta->cliente_endereco ?? '') . ', ' .
                 ($proposta->cliente_cidade   ?? '') . ' - ' .
                 ($proposta->cliente_estado   ?? ''), ', -')
        );
        $clienteResp     = htmlspecialchars($proposta->cliente_responsavel ?? '');

        // Itens
        $linhasItens = '';
        foreach ($itens as $i => $item) {
            $bg       = ($i % 2 === 0) ? '#ffffff' : '#f8fafc';
            $qtd      = number_format((float) $item->quantidade, 2, ',', '.');
            $preco    = number_format((float) $item->preco_unitario, 2, ',', '.');
            $total    = number_format((float) $item->total_item, 2, ',', '.');
            $margem   = $item->margem_lucro > 0 ? "<small style='color:#6b7280'> ({$item->margem_lucro}% mg)</small>" : '';
            $linhasItens .= "
            <tr style='background:{$bg}'>
              <td style='padding:8px 10px;border-bottom:1px solid #e5e7eb;font-size:12px;color:#6b7280'>" . ($i + 1) . "</td>
              <td style='padding:8px 10px;border-bottom:1px solid #e5e7eb;font-size:12px'>" . htmlspecialchars($item->codigo ?? '') . "</td>
              <td style='padding:8px 10px;border-bottom:1px solid #e5e7eb;font-size:12px'>" . htmlspecialchars($item->descricao) . "</td>
              <td style='padding:8px 10px;border-bottom:1px solid #e5e7eb;font-size:12px;text-align:center'>" . htmlspecialchars($item->unidade ?? 'un') . "</td>
              <td style='padding:8px 10px;border-bottom:1px solid #e5e7eb;font-size:12px;text-align:right'>{$qtd}</td>
              <td style='padding:8px 10px;border-bottom:1px solid #e5e7eb;font-size:12px;text-align:right'>R$ {$preco}{$margem}</td>
              <td style='padding:8px 10px;border-bottom:1px solid #e5e7eb;font-size:12px;text-align:right;font-weight:600'>R$ {$total}</td>
            </tr>";
        }

        // Totais
        $subtotal   = number_format((float) $proposta->subtotal,       2, ',', '.');
        $descTotal  = number_format((float) $proposta->desconto_total,  2, ',', '.');
        $frete      = number_format((float) ($proposta->frete_valor ?? 0), 2, ',', '.');
        $total      = number_format((float) $proposta->total,           2, ',', '.');

        $linhaDesconto = '';
        if ((float) $proposta->desconto_total > 0) {
            $linhaDesconto = "<tr><td colspan='6' style='text-align:right;padding:6px 10px;font-size:12px'>Desconto:</td><td style='text-align:right;padding:6px 10px;font-size:12px;color:#dc2626'>- R$ {$descTotal}</td></tr>";
        }
        $linhaFrete = '';
        if ((float) ($proposta->frete_valor ?? 0) > 0) {
            $linhaFrete = "<tr><td colspan='6' style='text-align:right;padding:6px 10px;font-size:12px'>Frete:</td><td style='text-align:right;padding:6px 10px;font-size:12px'>R$ {$frete}</td></tr>";
        }

        // Condições
        $prazo    = htmlspecialchars($proposta->prazo_entrega      ?? '—');
        $pagamento = htmlspecialchars($proposta->condicao_pagamento ?? '—');
        $obs      = nl2br(htmlspecialchars($proposta->observacoes  ?? ''));

        $html = <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Proposta {$numero}</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: Arial, sans-serif; font-size: 13px; color: #1f2937; background: #fff; }
  .header { background: linear-gradient(135deg, #1a56db 0%, #0e3a8c 100%); color: #fff; padding: 28px 32px; }
  .header-top { display: flex; justify-content: space-between; align-items: flex-start; }
  .header h1 { font-size: 22px; font-weight: 700; margin-bottom: 4px; }
  .header .sub { font-size: 13px; opacity: .85; }
  .numero-badge { background: rgba(255,255,255,.2); border-radius: 8px; padding: 8px 16px; text-align: center; }
  .numero-badge .label { font-size: 10px; text-transform: uppercase; letter-spacing: 1px; opacity: .8; }
  .numero-badge .num { font-size: 18px; font-weight: 700; }
  .section { padding: 20px 32px; }
  .section-title { font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #6b7280; font-weight: 700; margin-bottom: 12px; border-bottom: 2px solid #e5e7eb; padding-bottom: 6px; }
  .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
  .info-block { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 14px 16px; }
  .info-block .label { font-size: 10px; text-transform: uppercase; letter-spacing: .8px; color: #9ca3af; margin-bottom: 4px; }
  .info-block .value { font-size: 13px; color: #1f2937; font-weight: 600; }
  .info-block .sub-value { font-size: 12px; color: #6b7280; margin-top: 2px; }
  table { width: 100%; border-collapse: collapse; }
  th { background: #1a56db; color: #fff; padding: 10px; font-size: 11px; text-align: left; }
  th:last-child, th:nth-last-child(-n+3) { text-align: right; }
  .totals-row { background: #f0f7ff; }
  .total-final { background: #1a56db; color: #fff; font-size: 15px; font-weight: 700; }
  .total-final td { padding: 12px 10px; }
  .conditions { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
  .cond-item { background: #f9fafb; border-left: 3px solid #1a56db; padding: 10px 14px; border-radius: 0 6px 6px 0; }
  .cond-label { font-size: 10px; text-transform: uppercase; letter-spacing: .8px; color: #9ca3af; margin-bottom: 4px; }
  .cond-value { font-size: 13px; color: #1f2937; font-weight: 600; }
  .validade-box { background: #fef3c7; border: 1px solid #f59e0b; border-radius: 8px; padding: 12px 16px; display: flex; align-items: center; gap: 10px; }
  .validade-box .icon { font-size: 20px; }
  .validade-box .text { font-size: 13px; color: #92400e; }
  .validade-box .text strong { display: block; font-size: 15px; }
  .signatures { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-top: 20px; }
  .sig-block { text-align: center; }
  .sig-line { border-top: 1.5px solid #374151; margin-bottom: 8px; padding-top: 8px; }
  .sig-name { font-weight: 700; font-size: 13px; }
  .sig-role { font-size: 11px; color: #6b7280; }
  .footer { background: #f9fafb; border-top: 1px solid #e5e7eb; padding: 12px 32px; font-size: 11px; color: #9ca3af; display: flex; justify-content: space-between; }
  .obs-box { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 14px 16px; font-size: 12px; color: #374151; line-height: 1.6; }
  .divider { height: 1px; background: #e5e7eb; margin: 0 32px; }
</style>
</head>
<body>

<!-- CABEÇALHO -->
<div class="header">
  <div class="header-top">
    <div>
      <h1>{$fornecedorNome}</h1>
      <div class="sub">{$fornecedorEmail}</div>
      <div class="sub" style="margin-top:6px">Data: {$dataCriacao}</div>
    </div>
    <div class="numero-badge">
      <div class="label">Proposta</div>
      <div class="num">{$numero}</div>
    </div>
  </div>
  <div style="margin-top:16px;font-size:16px;font-weight:600">{$titulo}</div>
</div>

<!-- PARTES -->
<div class="section">
  <div class="section-title">Partes Envolvidas</div>
  <div class="grid-2">
    <div class="info-block">
      <div class="label">Fornecedor</div>
      <div class="value">{$fornecedorNome}</div>
      <div class="sub-value">{$fornecedorEmail}</div>
    </div>
    <div class="info-block">
      <div class="label">Tomador / Cliente</div>
      <div class="value">{$clienteNome}</div>
      <div class="sub-value">{$clienteDoc}</div>
      <div class="sub-value">{$clienteEmail}</div>
      <div class="sub-value">{$clienteTel}</div>
      <div class="sub-value">{$clienteEnd}</div>
      {$this->ifNotEmpty($clienteResp, "<div class='sub-value'>Resp: {$clienteResp}</div>")}
    </div>
  </div>
</div>

<div class="divider"></div>

<!-- ITENS -->
<div class="section">
  <div class="section-title">Itens da Proposta</div>
  <table>
    <thead>
      <tr>
        <th style="width:40px">#</th>
        <th style="width:80px">Código</th>
        <th>Descrição</th>
        <th style="width:50px;text-align:center">Un.</th>
        <th style="width:70px;text-align:right">Qtd.</th>
        <th style="width:110px;text-align:right">Preço Unit.</th>
        <th style="width:110px;text-align:right">Total</th>
      </tr>
    </thead>
    <tbody>
      {$linhasItens}
    </tbody>
    <tfoot>
      <tr class="totals-row">
        <td colspan="6" style="text-align:right;padding:8px 10px;font-size:12px;font-weight:600">Subtotal:</td>
        <td style="text-align:right;padding:8px 10px;font-size:12px;font-weight:600">R$ {$subtotal}</td>
      </tr>
      {$linhaDesconto}
      {$linhaFrete}
      <tr class="total-final">
        <td colspan="6" style="text-align:right">TOTAL GERAL:</td>
        <td style="text-align:right">R$ {$total}</td>
      </tr>
    </tfoot>
  </table>
</div>

<div class="divider"></div>

<!-- CONDIÇÕES -->
<div class="section">
  <div class="section-title">Condições Comerciais</div>
  <div class="conditions">
    <div class="cond-item">
      <div class="cond-label">Prazo de Entrega</div>
      <div class="cond-value">{$prazo}</div>
    </div>
    <div class="cond-item">
      <div class="cond-label">Condição de Pagamento</div>
      <div class="cond-value">{$pagamento}</div>
    </div>
  </div>
</div>

HTML;

        if (!empty($proposta->observacoes)) {
            $html .= <<<HTML
<div class="divider"></div>
<div class="section">
  <div class="section-title">Observações</div>
  <div class="obs-box">{$obs}</div>
</div>
HTML;
        }

        $html .= <<<HTML
<div class="divider"></div>

<!-- VALIDADE -->
<div class="section">
  <div class="validade-box">
    <div class="icon">⏰</div>
    <div class="text">
      Esta proposta é válida até <strong>{$validade}</strong>
      Após esta data, os valores e condições poderão ser revisados.
    </div>
  </div>
</div>

<div class="divider"></div>

<!-- ASSINATURAS -->
<div class="section">
  <div class="section-title">Aceite e Assinaturas</div>
  <div class="signatures">
    <div class="sig-block">
      <div style="height:50px"></div>
      <div class="sig-line"></div>
      <div class="sig-name">{$fornecedorNome}</div>
      <div class="sig-role">Fornecedor / Responsável</div>
    </div>
    <div class="sig-block">
      <div style="height:50px"></div>
      <div class="sig-line"></div>
      <div class="sig-name">{$clienteNome}</div>
      <div class="sig-role">Tomador / Cliente</div>
    </div>
  </div>
</div>

<!-- RODAPÉ -->
<div class="footer">
  <span>Proposta {$numero} — Gerada em {$dataGeracao}</span>
  <span>Documento gerado pelo ERP InLaudo</span>
</div>

</body>
</html>
HTML;

        // Gerar PDF via mPDF (puro PHP, sem dependência de binários externos)
        $tmpPdf = sys_get_temp_dir() . '/proposta_' . $proposta->id . '_' . time() . '.pdf';

        $mpdf = new \Mpdf\Mpdf([
            'mode'          => 'utf-8',
            'format'        => 'A4',
            'margin_top'    => 0,
            'margin_bottom' => 0,
            'margin_left'   => 0,
            'margin_right'  => 0,
            'tempDir'       => sys_get_temp_dir(),
        ]);
        $mpdf->SetTitle('Proposta ' . $proposta->numero);
        $mpdf->WriteHTML($html);
        $mpdf->Output($tmpPdf, \Mpdf\Output\Destination::FILE);

        if (!file_exists($tmpPdf) || filesize($tmpPdf) < 100) {
            throw new \RuntimeException('mPDF não gerou o PDF.');
        }

        return $tmpPdf;
    }

    private function montarEmailProposta(object $proposta, object|false $user): string
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
}
