<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\View;
use App\Core\Logger;
use App\Core\Audit\AuditLogger;
use App\Models\PortalCliente;
use App\Models\ContaReceber;
use App\Models\NotaFiscal;

/**
 * Controller principal do Portal do Cliente.
 * Gerencia: dashboard, perfil, alteração de senha.
 */
class PortalClienteController extends Controller
{
    private PortalCliente $portalModel;
    private ContaReceber $contaReceberModel;
    private NotaFiscal $notaFiscalModel;
    private Logger $logger;

    public function __construct()
    {
        $this->portalModel       = new PortalCliente();
        $this->contaReceberModel = new ContaReceber();
        $this->notaFiscalModel   = new NotaFiscal();
        $this->logger            = new Logger();
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
        $contasVencidas = array_filter($contasAbertas, fn($c) => $c->data_vencimento < date('Y-m-d'));
        $totalAberto    = array_sum(array_column($contasAbertas, 'valor'));

        View::render('portal/dashboard', [
            'title'          => 'Meu Painel',
            '_layout'        => 'portal',
            'portal'         => $portal,
            'contasAbertas'  => count($contasAbertas),
            'contasVencidas' => count($contasVencidas),
            'totalAberto'    => $totalAberto,
            'welcome'        => !empty($_GET['welcome']),
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
}
