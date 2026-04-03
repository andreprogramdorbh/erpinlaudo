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
use App\Models\MedicoExame;

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
    // RECALCULAR APURAÇÃO → reprocessa valores sem reimportar arquivo
    // POST /faturamento/apuracao/recalcular/{id}
    // =========================================================
    public function recalcular(string $id): void
    {
        $user      = Auth::user();
        $usuarioId = (int) $user->id;
        $apuracao  = $this->apuracaoModel->findById((int) $id);

        if (!$apuracao || $apuracao->usuario_id != $usuarioId) {
            $this->jsonError('Apuração não encontrada');
            return;
        }

        // Bloqueio: apuração faturada não pode ser recalculada
        if ($apuracao->status === 'faturado') {
            $this->jsonError('Apuração faturada não pode ser recalculada.');
            return;
        }

        // Buscar itens já importados
        $itens = $this->itemModel->findByApuracaoId((int) $id);
        if (empty($itens)) {
            $this->jsonError('Nenhum item encontrado para recalcular. Importe o arquivo primeiro.');
            return;
        }

        try {
            // Montar índices de exames (mesma lógica do executarApuracao)
            $tabelaExameModel = new TabelaExame();
            $exames           = $tabelaExameModel->findAllWithTagsByUsuarioId($usuarioId);

            $examesPorModalidade = [];
            $examesPorTagDicom   = [];
            foreach ($exames as $ex) {
                $mod = strtoupper(trim($ex->modalidade ?? ''));
                $examesPorModalidade[$mod][] = $ex;
                foreach ($ex->tags_dicom as $tagVal) {
                    $examesPorTagDicom[$tagVal][] = $ex;
                }
            }

            // Valores específicos do médico (PRIORIDADE 0 — máxima)
            $medicoExameModel = new MedicoExame();
            $medicoId         = (int) ($apuracao->medico_id ?? 0);
            $valoresMedico    = [];
            if ($medicoId > 0) {
                $examesMedico = $medicoExameModel->findByMedicoId($medicoId);
                foreach ($examesMedico as $me) {
                    $exId = (int) $me->tabela_exame_id;
                    if ($me->usa_valor_custom) {
                        $valoresMedico[$exId] = [
                            'rotina'   => (float) $me->valor_rotina,
                            'urgencia' => (float) $me->valor_urgencia,
                            'fonte'    => 'medico_custom',
                        ];
                    } else {
                        $valoresMedico[$exId] = [
                            'rotina'   => (float) $me->tabela_valor_rotina,
                            'urgencia' => (float) $me->tabela_valor_urgencia,
                            'fonte'    => 'medico_tabela',
                        ];
                    }
                }
            }

            $totalNormal   = 0;
            $totalUrgencia = 0;
            $valorTotal    = 0.0;
            $semMatch      = 0;
            $log           = [];

            foreach ($itens as $item) {
                $modalidade = strtoupper(trim($item->modalidade ?? ''));
                $studyDesc  = trim($item->study_description ?? '');
                $prioridade = strtolower(trim($item->prioridade ?? 'normal'));
                $isUrgencia = str_contains($prioridade, 'urgent') || $prioridade === 'urgente';
                $tipoPrior  = $isUrgencia ? 'urgencia' : 'normal';

                $exameMatch = null;
                $valorCalc  = 0.0;
                $statusItem = 'sem_match';
                $obsItem    = 'Sem correspondência na tabela de exames';

                // PRIORIDADE 1: Match por TAG DICOM
                if (!empty($examesPorTagDicom[$modalidade])) {
                    $candidatos = $examesPorTagDicom[$modalidade];
                    foreach ($candidatos as $ex) {
                        $nomeEx = strtolower(trim($ex->nome_exame ?? ''));
                        $nomeSD = strtolower($studyDesc);
                        if ($nomeEx && $nomeSD && (str_contains($nomeSD, $nomeEx) || str_contains($nomeEx, $nomeSD))) {
                            $exameMatch = $ex;
                            $obsItem    = 'Match por TAG DICOM + nome';
                            break;
                        }
                    }
                    if (!$exameMatch) {
                        $exameMatch = $candidatos[0];
                        $obsItem    = 'Match por TAG DICOM (modalidade=' . $modalidade . ')';
                    }
                }

                // PRIORIDADE 2: Match por modalidade da tabela
                if (!$exameMatch && !empty($examesPorModalidade[$modalidade])) {
                    foreach ($examesPorModalidade[$modalidade] as $ex) {
                        $nomeEx = strtolower(trim($ex->nome_exame ?? ''));
                        $nomeSD = strtolower($studyDesc);
                        if ($nomeEx && $nomeSD && (str_contains($nomeSD, $nomeEx) || str_contains($nomeEx, $nomeSD))) {
                            $exameMatch = $ex;
                            $obsItem    = 'Match por modalidade + nome';
                            break;
                        }
                    }
                    if (!$exameMatch) {
                        $exameMatch = $examesPorModalidade[$modalidade][0];
                        $obsItem    = 'Match por modalidade (sem correspondência exata de nome)';
                    }
                }

                if ($exameMatch) {
                    $exId = (int) $exameMatch->id;
                    // PRIORIDADE 0: Valores específicos do médico
                    if (!empty($valoresMedico[$exId])) {
                        $vm        = $valoresMedico[$exId];
                        $valorCalc = $isUrgencia ? $vm['urgencia'] : $vm['rotina'];
                        $obsItem   = ($obsItem ? $obsItem . ' | ' : '') . 'Valor: ' . $vm['fonte'];
                    } else {
                        $valorCalc = $isUrgencia
                            ? (float) ($exameMatch->valor_urgencia ?: $exameMatch->valor_padrao ?? 0)
                            : (float) ($exameMatch->valor_rotina  ?: $exameMatch->valor_padrao ?? 0);
                    }
                    $statusItem = 'ok';
                    if ($obsItem === 'Sem correspondência na tabela de exames') $obsItem = null;
                } else {
                    $semMatch++;
                    $log[] = "Item #{$item->id} (linha {$item->linha_original}): sem match — modalidade={$modalidade}, exame={$studyDesc}";
                }

                if ($isUrgencia) $totalUrgencia++; else $totalNormal++;
                $valorTotal += $valorCalc;

                // Atualizar apenas os campos de valor/exame do item (preserva dados originais)
                $this->itemModel->updateValores(
                    (int) $item->id,
                    $exameMatch ? (int) $exameMatch->id : null,
                    $valorCalc,
                    $tipoPrior,
                    $statusItem,
                    $obsItem
                );
            }

            $totalExames = $totalNormal + $totalUrgencia;
            $logStr      = empty($log) ? 'Recálculo concluído sem erros.' : implode("\n", $log);

            $this->apuracaoModel->update((int) $id, [
                'usuario_id'     => $usuarioId,
                'status'         => 'concluido',
                'total_exames'   => $totalExames,
                'total_normal'   => $totalNormal,
                'total_urgencia' => $totalUrgencia,
                'valor_total'    => $valorTotal,
                'log_execucao'   => $logStr,
            ]);

            AuditLogger::log('apuracao_recalculada', [
                'apuracao_id'  => $id,
                'total_exames' => $totalExames,
                'valor_total'  => $valorTotal,
                'sem_match'    => $semMatch,
            ]);

            header('Content-Type: application/json');
            echo json_encode([
                'success'        => true,
                'total_exames'   => $totalExames,
                'total_normal'   => $totalNormal,
                'total_urgencia' => $totalUrgencia,
                'valor_total'    => 'R$ ' . number_format($valorTotal, 2, ',', '.'),
                'sem_match'      => $semMatch,
                'log'            => $logStr,
            ]);

        } catch (\Throwable $e) {
            $this->logger->error('[ApuracaoController] Erro ao recalcular', [
                'apuracao_id' => $id,
                'error'       => $e->getMessage(),
                'trace'       => $e->getTraceAsString(),
            ]);
            $this->jsonError('Erro ao recalcular: ' . $e->getMessage());
        }
        exit();
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
    // EXCLUIR APURAÇÃO (apenas rascunho/erro — nunca após faturamento)
    // =========================================================
    public function delete(string $id): void
    {
        $user      = Auth::user();
        $usuarioId = (int) $user->id;
        $apuracao  = $this->apuracaoModel->findById((int) $id);

        // Determinar URL de retorno com base no tipo da apuração
        $redirect = ($apuracao && $apuracao->tipo === 'cliente')
            ? '/faturamento/apuracao-cliente'
            : '/faturamento/apuracao-prestador';

        // Validação: apuração deve existir e pertencer ao tenant
        if (!$apuracao || (int) $apuracao->usuario_id !== $usuarioId) {
            header("Location: {$redirect}?error=not_found");
            exit();
        }

        // Regra de negócio: não pode excluir após concluído ou faturado
        $statusBloqueados = ['concluido', 'faturado'];
        if (in_array($apuracao->status, $statusBloqueados, true)) {
            $this->logger->warning('[ApuracaoController] Tentativa de excluir apuração bloqueada', [
                'apuracao_id' => $id,
                'status'      => $apuracao->status,
                'usuario_id'  => $usuarioId,
            ]);
            header("Location: {$redirect}?error=exclusao_bloqueada");
            exit();
        }

        try {
            $excluiu = $this->apuracaoModel->delete((int) $id, $usuarioId);

            if (!$excluiu) {
                // O DELETE retornou 0 linhas afetadas (status não permitido no banco)
                header("Location: {$redirect}?error=exclusao_bloqueada");
                exit();
            }

            AuditLogger::log('apuracao_excluida', [
                'apuracao_id' => $id,
                'numero'      => $apuracao->numero,
                'status_era'  => $apuracao->status,
                'tipo'        => $apuracao->tipo,
            ]);

            header("Location: {$redirect}?success=deleted");

        } catch (\Throwable $e) {
            $this->logger->error('[ApuracaoController] Erro ao excluir apuração', [
                'apuracao_id' => $id,
                'error'       => $e->getMessage(),
                'trace'       => $e->getTraceAsString(),
            ]);
            header("Location: {$redirect}?error=db_error");
        }

        exit();
    }

    // =========================================================
    // HELPER: JSON error response
    // =========================================================
    private function jsonError(string $msg): void
    {
        ob_start(); ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $msg]);
        exit();
    }
}
