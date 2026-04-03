<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\View;
use App\Core\Logger;
use App\Core\Audit\AuditLogger;
use App\Models\Apuracao;
use App\Models\ApuracaoItem;
use App\Models\Medico;
use App\Models\Cliente;
use App\Models\TabelaExame;

class ApuracaoController extends Controller
{
    private Apuracao     $apuracaoModel;
    private ApuracaoItem $itemModel;
    private Logger       $logger;

    public function __construct()
    {
        $this->apuracaoModel = new Apuracao();
        $this->itemModel     = new ApuracaoItem();
        $this->logger        = new Logger();
    }

    // =========================================================
    // APURAÇÃO PRESTADOR (médico)
    // =========================================================
    public function prestador(): void
    {
        $user      = Auth::user();
        $usuarioId = (int) $user->id;

        $filtros = [
            'tipo'          => 'prestador',
            'medico_id'     => (int) ($_GET['medico_id'] ?? 0) ?: null,
            'status'        => $_GET['status'] ?? '',
            'periodo_inicio'=> $_GET['periodo_inicio'] ?? '',
            'periodo_fim'   => $_GET['periodo_fim'] ?? '',
        ];

        $apuracoes = $this->apuracaoModel->findByUsuarioId($usuarioId, $filtros);
        $medicos   = (new Medico())->findByUsuarioId($usuarioId, ['status' => 'ativo']);

        View::render('apuracao/prestador', [
            'title'     => 'Apuração Prestador',
            'apuracoes' => $apuracoes,
            'medicos'   => $medicos,
            'filtros'   => $filtros,
            '_layout' => 'erp',
        ]);
    }

    // =========================================================
    // APURAÇÃO CLIENTE
    // =========================================================
    public function cliente(): void
    {
        $user      = Auth::user();
        $usuarioId = (int) $user->id;

        $filtros = [
            'tipo'          => 'cliente',
            'cliente_id'    => (int) ($_GET['cliente_id'] ?? 0) ?: null,
            'status'        => $_GET['status'] ?? '',
            'periodo_inicio'=> $_GET['periodo_inicio'] ?? '',
            'periodo_fim'   => $_GET['periodo_fim'] ?? '',
        ];

        $apuracoes = $this->apuracaoModel->findByUsuarioId($usuarioId, $filtros);
        $clientes  = (new Cliente())->findByUsuarioId($usuarioId);

        View::render('apuracao/cliente', [
            'title'     => 'Apuração Cliente',
            'apuracoes' => $apuracoes,
            'clientes'  => $clientes,
            'filtros'   => $filtros,
            '_layout' => 'erp',
        ]);
    }

    // =========================================================
    // VISUALIZAR APURAÇÃO (detalhe)
    // =========================================================
    public function visualizar(string $id): void
    {
        $user      = Auth::user();
        $usuarioId = (int) $user->id;
        $apuracao  = $this->apuracaoModel->findById((int) $id);

        if (!$apuracao || $apuracao->usuario_id != $usuarioId) {
            header("Location: /faturamento/apuracao-prestador?error=not_found");
            exit();
        }

        $itens          = $this->itemModel->findByApuracaoId((int) $id);
        $resumoModal    = $this->apuracaoModel->resumoPorModalidade((int) $id);
        $resumoMedico   = $this->apuracaoModel->resumoPorMedico((int) $id);
        $resumoUnidade  = $this->apuracaoModel->resumoPorUnidade((int) $id);

        // Buscar exames com TAGs DICOM para exibir na view
        $tabelaExameModel = new TabelaExame();
        $examesComTags    = $tabelaExameModel->findAllWithTagsByUsuarioId($usuarioId);
        // Montar mapa: tag_valor => nome_exame (para exibir na view)
        $tagDicomParaExame = [];
        foreach ($examesComTags as $ex) {
            foreach ($ex->tags_dicom as $tagVal) {
                $tagDicomParaExame[$tagVal][] = $ex->nome_exame;
            }
        }
        // Todas as TAGs DICOM cadastradas (para exibir no cabeçalho)
        $todasTagsDicom = array_keys($tagDicomParaExame);
        sort($todasTagsDicom);

        View::render('apuracao/visualizar', [
            'title'             => 'Apuração ' . $apuracao->numero,
            'apuracao'          => $apuracao,
            'itens'             => $itens,
            'resumoModal'       => $resumoModal,
            'resumoMedico'      => $resumoMedico,
            'resumoUnidade'     => $resumoUnidade,
            'tagDicomParaExame' => $tagDicomParaExame,
            'todasTagsDicom'    => $todasTagsDicom,
            '_layout' => 'erp',
        ]);
    }

    // =========================================================
    // FATURAR APURAÇÃO → gera conta a pagar (prestador) ou receber (cliente)
    // =========================================================
    public function faturar(string $id): void
    {
        $user      = Auth::user();
        $usuarioId = (int) $user->id;
        $apuracao  = $this->apuracaoModel->findById((int) $id);

        if (!$apuracao || $apuracao->usuario_id != $usuarioId) {
            header("Location: /faturamento/apuracao-prestador?error=not_found");
            exit();
        }

        if ($apuracao->status !== 'concluido') {
            header("Location: /faturamento/apuracao-prestador?error=status_invalido");
            exit();
        }

        try {
            if ($apuracao->tipo === 'prestador') {
                // Gera Conta a Pagar para o médico
                $contaPagarModel = new \App\Models\ContaPagar();
                $descricao = "Apuração Prestador {$apuracao->numero}";
                if ($apuracao->medico_nome) $descricao .= " — {$apuracao->medico_nome}";
                $descricao .= " ({$apuracao->total_exames} exames)";

                $contaPagarModel->create([
                    'usuario_id'    => $usuarioId,
                    'descricao'     => $descricao,
                    'valor'         => $apuracao->valor_total,
                    'data_vencimento' => date('Y-m-d', strtotime('+30 days')),
                    'status'        => 'pendente',
                    'categoria'     => 'Honorários Médicos',
                    'observacoes'   => "Gerado automaticamente pela apuração {$apuracao->numero}",
                    'fornecedor_id' => null,
                ]);

                $redirect = '/faturamento/apuracao-prestador';
            } else {
                // Gera Conta a Receber do cliente
                $contaReceberModel = new \App\Models\ContaReceber();
                $descricao = "Apuração Cliente {$apuracao->numero}";
                if ($apuracao->cliente_nome) $descricao .= " — {$apuracao->cliente_nome}";
                $descricao .= " ({$apuracao->total_exames} exames)";

                $contaReceberModel->create([
                    'usuario_id'    => $usuarioId,
                    'descricao'     => $descricao,
                    'valor'         => $apuracao->valor_total,
                    'data_vencimento' => date('Y-m-d', strtotime('+30 days')),
                    'status'        => 'pendente',
                    'categoria'     => 'Serviços de Teleradiologia',
                    'observacoes'   => "Gerado automaticamente pela apuração {$apuracao->numero}",
                    'cliente_id'    => $apuracao->cliente_id,
                ]);

                $redirect = '/faturamento/apuracao-cliente';
            }

            // Atualizar status da apuração para faturado
            $this->apuracaoModel->update((int) $id, [
                'usuario_id' => $usuarioId,
                'status'     => 'faturado',
            ]);

            AuditLogger::log('apuracao_faturada', [
                'apuracao_id' => $id,
                'tipo'        => $apuracao->tipo,
                'valor'       => $apuracao->valor_total,
            ]);

            header("Location: {$redirect}?success=faturado");

        } catch (\Throwable $e) {
            $this->logger->error('[ApuracaoController] Erro ao faturar', [
                'apuracao_id' => $id,
                'error'       => $e->getMessage(),
                'trace'       => $e->getTraceAsString(),
            ]);
            $redirect = $apuracao->tipo === 'prestador'
                ? '/faturamento/apuracao-prestador'
                : '/faturamento/apuracao-cliente';
            header("Location: {$redirect}?error=faturamento_falhou");
        }
        exit();
    }

    // =========================================================
    // EXCLUIR APURAÇÃO (apenas rascunho/erro)
    // =========================================================
    public function delete(string $id): void
    {
        $user      = Auth::user();
        $usuarioId = (int) $user->id;
        $apuracao  = $this->apuracaoModel->findById((int) $id);

        $redirect = ($apuracao && $apuracao->tipo === 'cliente')
            ? '/faturamento/apuracao-cliente'
            : '/faturamento/apuracao-prestador';

        $this->apuracaoModel->delete((int) $id, $usuarioId);
        AuditLogger::log('apuracao_excluida', ['apuracao_id' => $id]);
        header("Location: {$redirect}?success=deleted");
        exit();
    }
}
