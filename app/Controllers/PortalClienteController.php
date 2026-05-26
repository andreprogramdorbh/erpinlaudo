<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\View;
use App\Core\Logger;
use App\Core\Audit\AuditLogger;
use App\Models\PortalCliente;
use App\Models\ContaReceber;
use App\Models\NotaFiscal;
use App\Models\CrmProposta;
use App\Models\PedidoVenda;

/**
 * Controller principal do Portal do Cliente.
 * Gerencia: dashboard, perfil, alteração de senha.
 */
class PortalClienteController extends Controller
{
    private PortalCliente $portalModel;
    private ContaReceber $contaReceberModel;
    private NotaFiscal $notaFiscalModel;
    private CrmProposta $propostaModel;
    private PedidoVenda $pedidoVendaModel;
    private Logger $logger;

    public function __construct()
    {
        $this->portalModel       = new PortalCliente();
        $this->contaReceberModel = new ContaReceber();
        $this->notaFiscalModel    = new NotaFiscal();
        $this->propostaModel      = new CrmProposta();
        $this->pedidoVendaModel   = new PedidoVenda();
        $this->logger             = new Logger();
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
    // GET /portal/dashboard
    // ---------------------------------------------------------------
    public function dashboard(): void
    {
        $portal   = $this->getPortalCliente();
        $tenantId = (int) $portal->tenant_id;
        $clienteId = (int) $portal->cliente_id;

        $this->logger->info('[Portal] Dashboard acessado', ['portal_id' => $portal->id, 'cliente_id' => $clienteId]);

        // Resumo financeiro
        $contasAbertas  = $this->contaReceberModel->findByClienteIdAndTenantId($clienteId, $tenantId, ['status' => 'aberta']);
        $contasVencidas = array_filter($contasAbertas, fn($c) => ($c->data_vencimento ?? '') < date('Y-m-d'));
        $totalAberto    = array_sum(array_column($contasAbertas, 'valor'));

        // Grupos de parcelas recorrentes
        $gruposParcelas = $this->contaReceberModel->findGruposByClienteId($clienteId, $tenantId);

        // Próximas parcelas a vencer (30 dias)
        $todasContas = $this->contaReceberModel->findByClienteIdAndTenantId($clienteId, $tenantId);
        $hoje = date('Y-m-d');
        $em30dias = date('Y-m-d', strtotime('+30 days'));
        $proximasVencer = array_filter($todasContas, fn($c) =>
            ($c->status ?? '') === 'aberta' &&
            ($c->data_vencimento ?? '') >= $hoje &&
            ($c->data_vencimento ?? '') <= $em30dias
        );
        usort($proximasVencer, fn($a, $b) => strcmp($a->data_vencimento ?? '', $b->data_vencimento ?? ''));

        View::render('portal/dashboard', [
            'title'           => 'Meu Painel',
            '_layout'         => 'portal',
            'portal'          => $portal,
            'contasAbertas'   => count($contasAbertas),
            'contasVencidas'  => count($contasVencidas),
            'totalAberto'     => $totalAberto,
            'welcome'         => !empty($_GET['welcome']),
            'gruposParcelas'  => $gruposParcelas,
            'proximasVencer'  => array_values($proximasVencer),
        ]);
    }

    // ---------------------------------------------------------------
    // GET /portal/perfil
    // ---------------------------------------------------------------
    public function perfil(): void
    {
        $portal = $this->getPortalCliente();

        View::render('portal/perfil', [
            'title'   => 'Meu Perfil',
            '_layout' => 'portal',
            'portal'  => $portal,
        ]);
    }

    // ---------------------------------------------------------------
    // POST /portal/perfil/alterar-senha
    // ---------------------------------------------------------------
    public function alterarSenha(): void
    {
        $portal       = $this->getPortalCliente();
        $senhaAtual   = $_POST['senha_atual']        ?? '';
        $novaSenha    = $_POST['nova_senha']          ?? '';
        $confirmacao  = $_POST['nova_senha_confirm']  ?? '';

        // Verifica senha atual
        if (!password_verify($senhaAtual, $portal->password_hash)) {
            $this->logger->warning('[Portal] Alteração de senha falhou — senha atual incorreta', ['portal_id' => $portal->id]);
            header('Location: /portal/perfil?error=senha_atual_incorreta');
            exit();
        }

        if (strlen($novaSenha) < 8) {
            header('Location: /portal/perfil?error=senha_curta');
            exit();
        }

        if ($novaSenha !== $confirmacao) {
            header('Location: /portal/perfil?error=senhas_diferentes');
            exit();
        }

        $hash = password_hash($novaSenha, PASSWORD_ARGON2ID);
        $this->portalModel->definirSenha((int) $portal->id, $hash);

        AuditLogger::log('portal_senha_alterada', ['portal_id' => $portal->id, 'cliente_id' => $portal->cliente_id]);
        $this->logger->info('[Portal] Senha alterada com sucesso', ['portal_id' => $portal->id]);

        header('Location: /portal/perfil?success=senha_alterada');
        exit();
    }

    // ---------------------------------------------------------------
    // GET /portal/pagamentos/dashboard
    // ---------------------------------------------------------------
    public function dashboardPagamentos(): void
    {
        $portal    = $this->getPortalCliente();
        $tenantId  = (int) $portal->tenant_id;
        $clienteId = (int) $portal->cliente_id;

        $this->logger->info('[Portal] Dashboard Pagamentos acessado', [
            'portal_id'  => $portal->id,
            'cliente_id' => $clienteId,
        ]);

        $dashData = $this->contaReceberModel->getDashboardDataByClienteId($clienteId, $tenantId);

        $statusTotais  = ['aberta' => 0, 'recebida' => 0, 'cancelada' => 0];
        $statusValores = ['aberta' => 0.0, 'recebida' => 0.0, 'cancelada' => 0.0];
        foreach ($dashData['por_status'] as $row) {
            $statusTotais[$row->status]  = (int)   $row->total;
            $statusValores[$row->status] = (float) $row->valor_total;
        }

        $meioPagLabels = [
            'pix' => 'PIX', 'boleto' => 'Boleto', 'cartao' => 'Cartao',
            'checkout' => 'Checkout', 'dinheiro' => 'Dinheiro',
            'transferencia' => 'Transferencia', 'outro' => 'Outro',
        ];
        $meioLabels  = [];
        $meioCounts  = [];
        $meioValores = [];
        foreach ($dashData['por_meio'] as $row) {
            $meioLabels[]  = $meioPagLabels[$row->meio] ?? ucfirst($row->meio);
            $meioCounts[]  = (int)   $row->total;
            $meioValores[] = (float) $row->valor_total;
        }

        $meses = [];
        for ($i = 11; $i >= 0; $i--) {
            $meses[] = date('Y-m', strtotime("-{$i} months"));
        }
        $mensalAberta   = array_fill(0, 12, 0.0);
        $mensalRecebida = array_fill(0, 12, 0.0);
        foreach ($dashData['mensal'] as $row) {
            $idx = array_search($row->mes, $meses, true);
            if ($idx !== false) {
                if ($row->status === 'aberta')   $mensalAberta[$idx]   = (float) $row->valor_total;
                if ($row->status === 'recebida') $mensalRecebida[$idx] = (float) $row->valor_total;
            }
        }
        $mesesLabels = array_map(function($m) {
            [$y, $mo] = explode('-', $m);
            $nomes = ['', 'Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
            return $nomes[(int)$mo] . '/' . substr($y, 2);
        }, $meses);

        View::render('portal/pagamentos/dashboard', [
            'title'          => 'Meu Financeiro',
            '_layout'        => 'portal',
            'portal'         => $portal,
            'statusTotais'   => $statusTotais,
            'statusValores'  => $statusValores,
            'meioLabels'     => $meioLabels,
            'meioCounts'     => $meioCounts,
            'meioValores'    => $meioValores,
            'mesesLabels'    => $mesesLabels,
            'mensalAberta'   => $mensalAberta,
            'mensalRecebida' => $mensalRecebida,
            'vencidas'       => $dashData['vencidas'],
        ]);
    }

    // ---------------------------------------------------------------
    // GET /portal/negociacoes/propostas
    // ---------------------------------------------------------------
    public function propostas(): void
    {
        $portal    = $this->getPortalCliente();
        $tenantId  = (int) $portal->tenant_id;
        $clienteId = (int) $portal->cliente_id;
        $status    = $_GET['status'] ?? '';
        $filtros   = $status ? ['status' => $status] : [];
        $propostas = $this->propostaModel->findByClienteIdAndTenantId($clienteId, $tenantId, $filtros);
        $this->logger->info('[Portal] Propostas acessadas', ['portal_id' => $portal->id, 'cliente_id' => $clienteId]);
        View::render('portal/negociacoes/propostas', [
            'title'    => 'Minhas Propostas',
            '_layout'  => 'portal',
            'portal'   => $portal,
            'propostas' => $propostas,
            'statusFiltro' => $status,
        ]);
    }

    // ---------------------------------------------------------------
    // GET /portal/negociacoes/propostas/{id}/aceitar
    // Página de aceite via portal (cliente já logado)
    // ---------------------------------------------------------------
    public function aceitarProposta(int $id): void
    {
        $portal    = $this->getPortalCliente();
        $tenantId  = (int) $portal->tenant_id;
        $clienteId = (int) $portal->cliente_id;
        $proposta  = $this->propostaModel->findById($id);
        if (!$proposta || (int) $proposta->usuario_id !== $tenantId) {
            header('Location: /portal/negociacoes/propostas?error=not_found');
            exit();
        }
        // Verificar se a proposta pertence ao cliente
        $itens = $this->propostaModel->getItens($id);
        View::render('portal/negociacoes/proposta_aceite', [
            'title'    => 'Aceitar Proposta ' . $proposta->numero,
            '_layout'  => 'portal',
            'portal'   => $portal,
            'proposta' => $proposta,
            'itens'    => $itens,
        ]);
    }

    // ---------------------------------------------------------------
    // POST /portal/negociacoes/propostas/{id}/aceitar
    // ---------------------------------------------------------------
    public function registrarAceiteProposta(int $id): void
    {
        ob_start(); ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        $portal    = $this->getPortalCliente();
        $tenantId  = (int) $portal->tenant_id;
        $proposta  = $this->propostaModel->findById($id);
        if (!$proposta || (int) $proposta->usuario_id !== $tenantId) {
            echo json_encode(['success' => false, 'error' => 'Proposta não encontrada.']);
            exit();
        }
        if (in_array($proposta->status, ['aceita', 'recusada'], true)) {
            echo json_encode(['success' => false, 'error' => 'Esta proposta já foi ' . $proposta->status . '.']);
            exit();
        }
        $acao       = $_POST['acao'] ?? 'aceitar';
        $nomeAssina = trim($_POST['nome_assinante'] ?? ($portal->razao_social ?? $portal->nome_fantasia ?? ''));
        $tipo       = $_POST['assinatura_tipo'] ?? 'portal';
        $rubrImg    = $_POST['assinatura_imagem'] ?? '';
        $motivo     = trim($_POST['motivo_recusa'] ?? '');
        $ip         = $_SERVER['REMOTE_ADDR'] ?? '';
        $ua         = $_SERVER['HTTP_USER_AGENT'] ?? '';

        if ($acao === 'recusar') {
            $this->propostaModel->update($id, [
                'status'          => 'recusada',
                'recusado_em'     => date('Y-m-d H:i:s'),
                'recusado_motivo' => $motivo,
            ]);
            $this->propostaModel->registrarEventoAceite($id, 'recusado', [
                'nome_assinante' => $nomeAssina,
                'ip'             => $ip,
                'user_agent'     => $ua,
                'motivo_recusa'  => $motivo,
            ]);
            $this->propostaModel->updateStatus($id, 'recusada', (int) $proposta->usuario_id, 'Proposta recusada pelo cliente via portal.');
            echo json_encode(['success' => true, 'acao' => 'recusada']);
            exit();
        }

        // Salvar rubrica se enviada
        $imgPath = null;
        if (!empty($rubrImg) && $tipo === 'rubrica') {
            $imgData = preg_replace('#^data:image/\w+;base64,#i', '', $rubrImg);
            $imgData = base64_decode($imgData);
            if ($imgData !== false) {
                $dir = BASE_PATH . '/storage/uploads/crm/assinaturas/' . $proposta->usuario_id;
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                $fname    = 'assinatura_prop_' . $id . '_portal_' . time() . '.png';
                file_put_contents($dir . '/' . $fname, $imgData);
                $imgPath = 'storage/uploads/crm/assinaturas/' . $proposta->usuario_id . '/' . $fname;
            }
        }

        $this->propostaModel->update($id, [
            'status'                 => 'aceita',
            'aceito_em'              => date('Y-m-d H:i:s'),
            'aceito_por_nome'        => $nomeAssina,
            'aceito_por_ip'          => $ip,
            'assinatura_tipo'        => 'portal',
            'assinatura_imagem_path' => $imgPath,
        ]);
        $this->propostaModel->registrarEventoAceite($id, 'aceito', [
            'nome_assinante'         => $nomeAssina,
            'ip'                     => $ip,
            'user_agent'             => $ua,
            'assinatura_tipo'        => 'portal',
            'assinatura_imagem_path' => $imgPath,
        ]);
        $this->propostaModel->updateStatus($id, 'aceita', (int) $proposta->usuario_id, 'Proposta aceita pelo cliente via portal.');
        $this->logger->info("[Portal] Proposta #{$proposta->numero} aceita via portal por {$nomeAssina}");
        echo json_encode(['success' => true, 'acao' => 'aceita']);
        exit();
    }

    // ---------------------------------------------------------------
    // GET /portal/negociacoes/pedidos-venda
    // ---------------------------------------------------------------
    public function pedidosVenda(): void
    {
        $portal    = $this->getPortalCliente();
        $tenantId  = (int) $portal->tenant_id;
        $clienteId = (int) $portal->cliente_id;
        $status    = $_GET['status'] ?? '';
        $filtros   = $status ? ['status' => $status] : [];
        $pedidos   = $this->pedidoVendaModel->findByClienteIdAndTenantId($clienteId, $tenantId, $filtros);
        $this->logger->info('[Portal] Pedidos de Venda acessados', ['portal_id' => $portal->id, 'cliente_id' => $clienteId]);
        View::render('portal/negociacoes/pedidos_venda', [
            'title'        => 'Meus Pedidos de Venda',
            '_layout'      => 'portal',
            'portal'       => $portal,
            'pedidos'      => $pedidos,
            'statusFiltro' => $status,
        ]);
    }


}