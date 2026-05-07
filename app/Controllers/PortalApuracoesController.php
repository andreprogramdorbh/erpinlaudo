<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\View;
use App\Core\Logger;
use App\Models\PortalCliente;
use App\Models\Apuracao;
use App\Models\ApuracaoItem;
use App\Models\ContaReceber;

/**
 * Controller de Apurações do Portal do Cliente.
 *
 * Exibe todas as apurações do tipo 'cliente' vinculadas ao cliente logado,
 * com relatório detalhado por modalidade, unidade e médico (valores de venda).
 */
class PortalApuracoesController extends Controller
{
    private PortalCliente $portalModel;
    private Apuracao      $apuracaoModel;
    private ContaReceber  $contaModel;
    private Logger        $logger;

    public function __construct()
    {
        $this->portalModel   = new PortalCliente();
        $this->apuracaoModel = new Apuracao();
        $this->contaModel    = new ContaReceber();
        $this->logger        = new Logger();
    }

    // ---------------------------------------------------------------
    // Helper: obtém o cliente logado no portal
    // ---------------------------------------------------------------
    private function getPortalCliente(): object
    {
        $id     = (int) ($_SESSION['portal_cliente_id'] ?? 0);
        $portal = $this->portalModel->findById($id);
        if (!$portal) {
            session_unset();
            header('Location: /login?error=sessao_expirada');
            exit();
        }
        return $portal;
    }

    // ---------------------------------------------------------------
    // GET /portal/apuracoes
    // Listagem de todas as apurações do cliente
    // ---------------------------------------------------------------
    public function index(): void
    {
        $portal    = $this->getPortalCliente();
        $clienteId = (int) $portal->cliente_id;
        $tenantId  = (int) $portal->tenant_id;

        $this->logger->info('[Portal] Apurações acessadas', [
            'portal_id'  => $portal->id,
            'cliente_id' => $clienteId,
        ]);

        // Filtros via GET
        $filtroStatus = $_GET['status'] ?? '';
        $filtroPeriodo = $_GET['periodo'] ?? '';

        // Busca apurações do tipo 'cliente' para este cliente
        $filtros = [
            'tipo'       => 'cliente',
            'cliente_id' => $clienteId,
        ];
        if ($filtroStatus !== '') {
            $filtros['status'] = $filtroStatus;
        }

        // Busca todas as apurações do tenant filtrando por cliente
        $todasApuracoes = $this->apuracaoModel->findByUsuarioId($tenantId, $filtros);

        // Filtro de período (mês/ano)
        if ($filtroPeriodo !== '') {
            $todasApuracoes = array_filter($todasApuracoes, function ($a) use ($filtroPeriodo) {
                $inicio = $a->periodo_inicio ?? '';
                return $inicio !== '' && substr($inicio, 0, 7) === $filtroPeriodo;
            });
            $todasApuracoes = array_values($todasApuracoes);
        }

        // Totalizadores
        $totalApuracoes   = count($todasApuracoes);
        $totalFaturadas   = count(array_filter($todasApuracoes, fn($a) => $a->status === 'faturado'));
        $totalConcluidas  = count(array_filter($todasApuracoes, fn($a) => $a->status === 'concluido'));
        $totalValorVenda  = array_sum(array_map(fn($a) => (float)(($a->valor_venda_total ?? 0) > 0 ? $a->valor_venda_total : $a->valor_total), $todasApuracoes));
        $totalExames      = array_sum(array_map(fn($a) => (int)($a->total_exames ?? 0), $todasApuracoes));

        View::render('portal/apuracoes/index', [
            'title'           => 'Minhas Apurações',
            '_layout'         => 'portal',
            'portal'          => $portal,
            'apuracoes'       => $todasApuracoes,
            'totalApuracoes'  => $totalApuracoes,
            'totalFaturadas'  => $totalFaturadas,
            'totalConcluidas' => $totalConcluidas,
            'totalValorVenda' => $totalValorVenda,
            'totalExames'     => $totalExames,
            'filtroStatus'    => $filtroStatus,
            'filtroPeriodo'   => $filtroPeriodo,
        ]);
    }

    // ---------------------------------------------------------------
    // GET /portal/apuracoes/{id}
    // Detalhe/relatório de uma apuração específica
    // ---------------------------------------------------------------
    public function show(int $id): void
    {
        $portal    = $this->getPortalCliente();
        $clienteId = (int) $portal->cliente_id;
        $tenantId  = (int) $portal->tenant_id;

        // Busca a apuração e valida que pertence ao cliente logado
        $apuracao = $this->apuracaoModel->findById($id);

        if (!$apuracao
            || (int) $apuracao->cliente_id !== $clienteId
            || (int) $apuracao->usuario_id !== $tenantId
            || $apuracao->tipo !== 'cliente'
        ) {
            header('Location: /portal/apuracoes?error=nao_autorizado');
            exit();
        }

        // Resumos com valores de venda (para apuração de cliente)
        $resumoModalidade = $this->apuracaoModel->resumoPorModalidadeVenda($id);
        $resumoMedico     = $this->apuracaoModel->resumoPorMedicoVenda($id);
        $resumoUnidade    = $this->apuracaoModel->resumoPorUnidadeVenda($id);

        // Conta a receber vinculada (busca por observações que contêm o número da apuração)
        $contaVinculada = $this->buscarContaVinculada($apuracao->numero, $clienteId, $tenantId);

        $this->logger->info('[Portal] Detalhe de apuração acessado', [
            'portal_id'   => $portal->id,
            'cliente_id'  => $clienteId,
            'apuracao_id' => $id,
        ]);

        View::render('portal/apuracoes/show', [
            'title'            => 'Apuração ' . $apuracao->numero,
            '_layout'          => 'portal',
            'portal'           => $portal,
            'apuracao'         => $apuracao,
            'resumoModalidade' => $resumoModalidade,
            'resumoMedico'     => $resumoMedico,
            'resumoUnidade'    => $resumoUnidade,
            'contaVinculada'   => $contaVinculada,
        ]);
    }

    // ---------------------------------------------------------------
    // Helper: busca a conta a receber vinculada à apuração pelo número
    // ---------------------------------------------------------------
    private function buscarContaVinculada(string $numero, int $clienteId, int $tenantId): ?object
    {
        try {
            $pdo = \App\Core\Database::getInstance()->getConnection();
            $stmt = $pdo->prepare(
                "SELECT cr.*
                 FROM contas_receber cr
                 WHERE cr.cliente_id = :cliente_id
                   AND cr.usuario_id = :tenant_id
                   AND (cr.observacoes LIKE :num OR cr.descricao LIKE :num)
                 ORDER BY cr.created_at DESC
                 LIMIT 1"
            );
            $stmt->execute([
                ':cliente_id' => $clienteId,
                ':tenant_id'  => $tenantId,
                ':num'        => '%' . $numero . '%',
            ]);
            $row = $stmt->fetch(\PDO::FETCH_OBJ);
            return $row ?: null;
        } catch (\Throwable $e) {
            $this->logger->error('[Portal] Erro ao buscar conta vinculada: ' . $e->getMessage());
            return null;
        }
    }
}
