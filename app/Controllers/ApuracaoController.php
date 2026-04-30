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
use App\Services\EmailAlertaService;

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
            // Inclui sub-apurações geradas automaticamente pela cascata de faturamento
            'incluir_sub'   => true,
        ];
        $apuracoes = $this->apuracaoModel->findByUsuarioId($usuarioId, $filtros);
        $medicos   = (new Medico())->findByUsuarioId($usuarioId, ['status' => 'ativo']);

        View::render('apuracao/prestador', [
            'title'        => 'Apuração Prestador',
            'apuracoes'    => $apuracoes,
            'medicos'      => $medicos,
            'filtros'      => $filtros,
            'isSuperAdmin' => Auth::hasRole('superadmin'),
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
            'title'          => 'Apuração Cliente',
            'apuracoes'      => $apuracoes,
            'clientes'       => $clientes,
            'filtros'        => $filtros,
            'isSuperAdmin'   => Auth::hasRole('superadmin'),
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

        $tipo = $apuracao->tipo ?? 'prestador';
        $backUrl = $tipo === 'cliente' ? '/faturamento/apuracao-cliente' : '/faturamento/apuracao-prestador';

        if (!$apuracao || $apuracao->usuario_id != $usuarioId) {
            header("Location: {$backUrl}?error=not_found");
            exit();
        }

        $itens          = $this->itemModel->findByApuracaoId((int) $id);
        // Para apuração cliente: usa resumos com valor_calculado_venda; prestador: valor_calculado
        $resumoModal    = $tipo === 'cliente'
            ? $this->apuracaoModel->resumoPorModalidadeVenda((int) $id)
            : $this->apuracaoModel->resumoPorModalidade((int) $id);
        $resumoMedico   = $tipo === 'cliente'
            ? $this->apuracaoModel->resumoPorMedicoVenda((int) $id)
            : $this->apuracaoModel->resumoPorMedico((int) $id);
        $resumoUnidade  = $tipo === 'cliente'
            ? $this->apuracaoModel->resumoPorUnidadeVenda((int) $id)
            : $this->apuracaoModel->resumoPorUnidade((int) $id);

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

        // Sub-apurações de prestador vinculadas (apenas para apuração cliente)
        $subApuracoes = [];
        if ($tipo === 'cliente') {
            $subApuracoes = $this->apuracaoModel->findSubApuracoesByMaeId((int) $id);
        }

        // Selecionar a view correta conforme o tipo da apuração
        $viewName = $tipo === 'cliente' ? 'apuracao/visualizar_cliente' : 'apuracao/visualizar';

        View::render($viewName, [
            'title'             => 'Apuração ' . $apuracao->numero,
            'apuracao'          => $apuracao,
            'itens'             => $itens,
            'resumoModal'       => $resumoModal,
            'resumoMedico'      => $resumoMedico,
            'resumoUnidade'     => $resumoUnidade,
            'subApuracoes'      => $subApuracoes,
            'tagDicomParaExame' => $tagDicomParaExame,
            'todasTagsDicom'    => $todasTagsDicom,
            'isSuperAdmin'      => Auth::hasRole('superadmin'),
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

            // Determinar tipo da apuração para lógica de preços
            $tipoApuracao = $apuracao->tipo ?? 'prestador';

            $totalNormal     = 0;
            $totalUrgencia   = 0;
            $valorTotal      = 0.0; // valor de custo (prestador)
            $valorVendaTotal = 0.0; // valor de venda (cliente)
            $semMatch        = 0;
            $log             = [];

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

                $valorCalcVenda = 0.0;

                if ($exameMatch) {
                    $exId = (int) $exameMatch->id;

                    // -------------------------------------------------------
                    // NOVA LÓGICA DE PREÇOS:
                    // - valor_rotina / valor_urgencia = valores DIRETOS do médico (prestador)
                    // - valor_venda_rotina / valor_venda_urgencia = valores de venda (cliente)
                    // -------------------------------------------------------

                    // Valor de venda (cliente): usa valor_venda_rotina/urgencia da tabela
                    $valorCalcVenda = $isUrgencia
                        ? (float) ($exameMatch->valor_venda_urgencia ?: $exameMatch->preco_venda ?: $exameMatch->valor_urgencia ?: 0)
                        : (float) ($exameMatch->valor_venda_rotina  ?: $exameMatch->preco_venda ?: $exameMatch->valor_rotina  ?: 0);

                    if ($tipoApuracao === 'cliente') {
                        // Apuração cliente: usa valor de venda
                        $valorCalc = $valorCalcVenda;
                    } elseif (!empty($valoresMedico[$exId])) {
                        // Prestador com override do médico
                        $vm        = $valoresMedico[$exId];
                        $valorCalc = $isUrgencia ? $vm['urgencia'] : $vm['rotina'];
                        $obsItem   = ($obsItem ? $obsItem . ' | ' : '') . 'Valor: ' . $vm['fonte'];
                    } else {
                        // Prestador: usa valor_rotina/urgencia DIRETOS da tabela de exames
                        $valorCalc = $isUrgencia
                            ? (float) ($exameMatch->valor_urgencia ?: 0)
                            : (float) ($exameMatch->valor_rotina  ?: 0);
                    }

                    $statusItem = 'ok';
                    if ($obsItem === 'Sem correspondência na tabela de exames') $obsItem = null;
                } else {
                    $semMatch++;
                    $log[] = "Item #{$item->id} (linha {$item->linha_original}): sem match — modalidade={$modalidade}, exame={$studyDesc}";
                }

                if ($isUrgencia) $totalUrgencia++; else $totalNormal++;
                $valorTotal      += $valorCalc;
                $valorVendaTotal += $valorCalcVenda;

                // Atualizar apenas os campos de valor/exame do item (preserva dados originais)
                $this->itemModel->updateValores(
                    (int) $item->id,
                    $exameMatch ? (int) $exameMatch->id : null,
                    $valorCalc,
                    $valorCalcVenda,
                    $tipoPrior,
                    $statusItem,
                    $obsItem
                );
            }

            $totalExames = $totalNormal + $totalUrgencia;
            $logStr      = empty($log) ? 'Recálculo concluído sem erros.' : implode("\n", $log);

            $this->apuracaoModel->update((int) $id, [
                'usuario_id'       => $usuarioId,
                'status'           => 'concluido',
                'total_exames'     => $totalExames,
                'total_normal'     => $totalNormal,
                'total_urgencia'   => $totalUrgencia,
                'valor_total'      => $valorTotal,
                'valor_venda_total'=> $valorVendaTotal,
                'log_execucao'     => $logStr,
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
                'valor_total'       => 'R$ ' . number_format($valorTotal, 2, ',', '.'),
                'valor_venda_total' => 'R$ ' . number_format($valorVendaTotal, 2, ',', '.'),
                'sem_match'         => $semMatch,
                'log'               => $logStr,
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

        // Determinar redirect correto com base no tipo ANTES de qualquer validação
        $redirectBase = ($apuracao->tipo === 'cliente')
            ? '/faturamento/apuracao-cliente'
            : '/faturamento/apuracao-prestador';

        if ($apuracao->status !== 'concluido') {
            header("Location: {$redirectBase}?error=status_invalido");
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
                    'usuario_id'           => $usuarioId,
                    'plano_conta_id'       => null,
                    'fornecedor_id'        => null,
                    'descricao'            => $descricao,
                    'valor'                => $apuracao->valor_total,
                    'data_vencimento'      => date('Y-m-d', strtotime('+30 days')),
                    'data_pagamento'       => null,
                    'codigo_barras'        => null,
                    'recorrente'           => 0,
                    'recorrencia_tipo'     => null,
                    'recorrencia_intervalo'=> null,
                    'status'               => 'aberta',
                    'observacoes'          => "Gerado automaticamente pela apuração {$apuracao->numero}",
                ]);

                // ── Email 1: Provisionamento de pagamento (conta a pagar criada) ──
                try {
                    $this->dispararEmailProvisionamento($apuracao, $usuarioId);
                } catch (\Throwable $emailEx) {
                    $this->logger->warning('[ApuracaoController] Falha ao enviar email provisionamento: ' . $emailEx->getMessage());
                }

                // ── Email 2: Apuração concluída com PDF ──
                try {
                    $this->dispararEmailApuracao($apuracao, $usuarioId);
                } catch (\Throwable $emailEx) {
                    $this->logger->warning('[ApuracaoController] Falha ao enviar email apuração: ' . $emailEx->getMessage());
                }

                $redirect = '/faturamento/apuracao-prestador';
            } else {
                // ═══════════════════════════════════════════════════════════════
                // CASCATA DE FATURAMENTO — APURAÇÃO CLIENTE
                // 1. Gera Conta a Receber do cliente (sistema recebe)
                // 2. Para cada sub-apuração de prestador vinculada:
                //    a. Gera Conta a Pagar para o médico (sistema paga)
                //    b. Marca sub-apuração como 'faturado'
                //    c. Envia emails de notificação ao médico
                // ═══════════════════════════════════════════════════════════════

                // 1. Conta a Receber do cliente
                $contaReceberModel = new \App\Models\ContaReceber();
                $descricao = "Apuração Cliente {$apuracao->numero}";
                if ($apuracao->cliente_nome) $descricao .= " — {$apuracao->cliente_nome}";
                $descricao .= " ({$apuracao->total_exames} exames)";

                $contaReceberModel->create([
                    'usuario_id'           => $usuarioId,
                    'cliente_id'           => $apuracao->cliente_id ?? null,
                    'plano_conta_id'       => null,
                    'descricao'            => $descricao,
                    'valor'                => (float)(($apuracao->valor_venda_total ?? 0) > 0 ? $apuracao->valor_venda_total : $apuracao->valor_total),
                    'data_vencimento'      => date('Y-m-d', strtotime('+30 days')),
                    'data_recebimento'     => null,
                    'status'               => 'aberta',
                    'observacoes'          => "Gerado automaticamente pela apuração {$apuracao->numero}",
                    'meio_pagamento'       => null,
                    'recorrente'           => 0,
                    'recorrencia_tipo'     => null,
                    'recorrencia_intervalo'=> null,
                    'contrato_id'          => $apuracao->contrato_id ?? null,
                ]);

                // 2. Cascata: faturar sub-apurações de prestador vinculadas
                $subApuracoes = $this->apuracaoModel->findSubApuracoesByMaeId((int) $id);
                $contaPagarModel = new \App\Models\ContaPagar();
                $logCascata = [];

                foreach ($subApuracoes as $sub) {
                    // Só fatura sub-apurações que ainda não foram faturadas
                    if ($sub->status === 'faturado') {
                        $logCascata[] = "Sub {$sub->numero}: já faturada — ignorada.";
                        continue;
                    }

                    // Cria Conta a Pagar para o médico
                    $descSub = "Honorários Prestador {$sub->numero}";
                    if (!empty($sub->medico_nome)) $descSub .= " — {$sub->medico_nome}";
                    $descSub .= " ({$sub->total_exames} exames | Ref: {$apuracao->numero})";

                    $contaPagarModel->create([
                        'usuario_id'           => $usuarioId,
                        'plano_conta_id'       => null,
                        'fornecedor_id'        => null,
                        'descricao'            => $descSub,
                        'valor'                => (float)($sub->valor_total ?? 0),
                        'data_vencimento'      => date('Y-m-d', strtotime('+30 days')),
                        'data_pagamento'       => null,
                        'codigo_barras'        => null,
                        'recorrente'           => 0,
                        'recorrencia_tipo'     => null,
                        'recorrencia_intervalo'=> null,
                        'status'               => 'aberta',
                        'observacoes'          => "Gerado automaticamente ao faturar apuração cliente {$apuracao->numero}",
                    ]);

                    // Marca sub-apuração como faturada
                    $this->apuracaoModel->updateStatus((int) $sub->id, 'faturado', $usuarioId);

                    // Emails ao médico (silencioso — não interrompe o faturamento)
                    try { $this->dispararEmailProvisionamento($sub, $usuarioId); } catch (\Throwable $e) {}
                    try { $this->dispararEmailApuracao($sub, $usuarioId); } catch (\Throwable $e) {}

                    $logCascata[] = "Sub {$sub->numero}: Conta a Pagar criada" . (!empty($sub->medico_nome) ? " para {$sub->medico_nome}" : '') . " (R$ " . number_format((float)$sub->valor_total, 2, ',', '.') . ").";

                    AuditLogger::log('sub_apuracao_faturada', [
                        'sub_apuracao_id' => $sub->id,
                        'apuracao_mae_id' => $id,
                        'medico_id'       => $sub->medico_id ?? null,
                        'valor'           => $sub->valor_total,
                    ]);
                }

                if (!empty($logCascata)) {
                    $this->logger->info('[ApuracaoController] Cascata de faturamento', [
                        'apuracao_mae' => $apuracao->numero,
                        'sub_apuracoes'=> $logCascata,
                    ]);
                }

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
                'apuracao_id'  => $id,
                'tipo'         => $apuracao->tipo ?? 'desconhecido',
                'cliente_id'   => $apuracao->cliente_id ?? null,
                'valor_total'  => $apuracao->valor_total ?? null,
                'valor_venda'  => $apuracao->valor_venda_total ?? null,
                'error'        => $e->getMessage(),
                'trace'        => $e->getTraceAsString(),
            ]);
            header("Location: {$redirectBase}?error=faturamento_falhou");
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
    // EXCLUIR APURAÇÃO — SUPERADMIN (cascata completa)
    // GET /faturamento/apuracao/superadmin-delete/{id}
    // Exclusivo para superadmin: remove apuração + sub-apurações
    // + contas a pagar/receber vinculadas, independente do status.
    // =========================================================
    public function deleteSuperAdmin(string $id): void
    {
        $user      = Auth::user();
        $usuarioId = (int) $user->id;

        // ── Verificação de role: SOMENTE superadmin ──────────────
        if (!Auth::hasRole('superadmin')) {
            $this->logger->warning('[ApuracaoController] Tentativa de exclusão superadmin por usuário sem permissão', [
                'usuario_id'  => $usuarioId,
                'role'        => $user->role ?? 'desconhecido',
                'apuracao_id' => $id,
            ]);
            header('Location: /faturamento/apuracao-cliente?error=sem_permissao');
            exit();
        }

        $apuracao = $this->apuracaoModel->findById((int) $id);

        // Determinar URL de retorno
        $redirect = ($apuracao && $apuracao->tipo === 'cliente')
            ? '/faturamento/apuracao-cliente'
            : '/faturamento/apuracao-prestador';

        if (!$apuracao || (int) $apuracao->usuario_id !== $usuarioId) {
            header("Location: {$redirect}?error=not_found");
            exit();
        }

        try {
            $pdo               = $this->apuracaoModel->getPdo();
            $numero            = $apuracao->numero;
            $pattern           = '%' . $numero . '%';
            $logCascata        = [];

            // ── 1. Excluir contas a receber vinculadas ────────────
            $stmtCR = $pdo->prepare(
                "DELETE FROM contas_receber
                  WHERE usuario_id = ?
                    AND (observacoes LIKE ? OR descricao LIKE ?)"
            );
            $stmtCR->execute([$usuarioId, $pattern, $pattern]);
            $deletedCR = $stmtCR->rowCount();
            $logCascata[] = "Contas a Receber excluídas: {$deletedCR}";

            // ── 2. Excluir contas a pagar da apuração principal ───
            $stmtCP = $pdo->prepare(
                "DELETE FROM contas_pagar
                  WHERE usuario_id = ?
                    AND (observacoes LIKE ? OR descricao LIKE ?)"
            );
            $stmtCP->execute([$usuarioId, $pattern, $pattern]);
            $deletedCP = $stmtCP->rowCount();
            $logCascata[] = "Contas a Pagar excluídas: {$deletedCP}";

            // ── 3. Excluir sub-apurações de prestador vinculadas ──
            $subApuracoes = $this->apuracaoModel->findSubApuracoesByMaeId((int) $id);
            $deletedSub   = 0;
            foreach ($subApuracoes as $sub) {
                // Excluir contas a pagar da sub-apuração pelo número dela
                $patternSub = '%' . $sub->numero . '%';
                $stmtSubCP  = $pdo->prepare(
                    "DELETE FROM contas_pagar
                      WHERE usuario_id = ?
                        AND (observacoes LIKE ? OR descricao LIKE ?)"
                );
                $stmtSubCP->execute([$usuarioId, $patternSub, $patternSub]);

                // Excluir itens da sub-apuração (ON DELETE CASCADE cobre, mas por segurança)
                $pdo->prepare("DELETE FROM apuracao_itens WHERE apuracao_id = ?")
                    ->execute([(int) $sub->id]);

                // Excluir a sub-apuração
                $pdo->prepare("DELETE FROM apuracoes WHERE id = ? AND usuario_id = ?")
                    ->execute([(int) $sub->id, $usuarioId]);

                $deletedSub++;
                $logCascata[] = "Sub-apuração {$sub->numero} excluída"
                    . (!empty($sub->medico_nome) ? " ({$sub->medico_nome})" : '');
            }

            // ── 4. Excluir itens da apuração principal ────────────
            $pdo->prepare("DELETE FROM apuracao_itens WHERE apuracao_id = ?")
                ->execute([(int) $id]);

            // ── 5. Excluir a apuração principal ───────────────────
            $pdo->prepare("DELETE FROM apuracoes WHERE id = ? AND usuario_id = ?")
                ->execute([(int) $id, $usuarioId]);

            // ── 6. Log de auditoria completo ──────────────────────
            AuditLogger::log('apuracao_excluida_superadmin', [
                'apuracao_id'    => $id,
                'numero'         => $numero,
                'tipo'           => $apuracao->tipo,
                'status_era'     => $apuracao->status,
                'valor_total'    => $apuracao->valor_total,
                'sub_apuracoes'  => $deletedSub,
                'contas_pagar'   => $deletedCP,
                'contas_receber' => $deletedCR,
                'executado_por'  => $usuarioId,
                'executado_role' => $user->role ?? 'superadmin',
                'cascata_log'    => $logCascata,
            ]);

            $this->logger->info('[ApuracaoController] Exclusão superadmin concluída', [
                'apuracao'    => $numero,
                'cascata_log' => $logCascata,
            ]);

            header("Location: {$redirect}?success=deleted");

        } catch (\Throwable $e) {
            $this->logger->error('[ApuracaoController] Erro na exclusão superadmin', [
                'apuracao_id' => $id,
                'error'       => $e->getMessage(),
                'trace'       => $e->getTraceAsString(),
            ]);
            header("Location: {$redirect}?error=db_error");
        }
        exit();
    }

    // =========================================================
    // REVINCULAR MÉDICO — atualiza sub-apurações com medico_id NULL
    // POST /faturamento/apuracao/revincular-medico/{id}
    // Tenta vincular o médico usando a cadeia: CRM → Nome → CPF → IdMedCrm
    // =========================================================
    public function revincularMedico(string $id): void
    {
        ob_start(); ob_end_clean();
        header('Content-Type: application/json');
        try {
            $user      = Auth::user();
            $usuarioId = (int) $user->id;
            $apuracao  = $this->apuracaoModel->findById((int) $id);

            if (!$apuracao || (int) $apuracao->usuario_id !== $usuarioId) {
                echo json_encode(['success' => false, 'message' => 'Apuração não encontrada.']);
                exit();
            }

            $medicoModel = new Medico();
            $subApuracoes = $this->apuracaoModel->findSubApuracoesByMaeId((int) $id);

            $atualizados = 0;
            $log = [];

            foreach ($subApuracoes as $sub) {
                // Pular se já tem médico vinculado
                if (!empty($sub->medico_id)) {
                    continue;
                }

                $medicoObj = null;

                // Buscar o primeiro item da sub-apuração para obter CRM e nome
                $itens = $this->itemModel->findByApuracaoId((int) $sub->id);
                $primeiroItem = $itens[0] ?? null;

                $crmItem  = trim((string)($primeiroItem->medico_crm  ?? ''));
                $nomeItem = trim((string)($primeiroItem->medico_nome ?? ''));

                // P1: findByCrm
                if ($crmItem !== '') {
                    $medicoObj = $medicoModel->findByCrm($usuarioId, $crmItem);
                    if ($medicoObj) {
                        $log[] = "Sub {$sub->numero}: vinculado pelo CRM '{$crmItem}' (ID: {$medicoObj->id})";
                    }
                }

                // P2: findByNome
                if (!$medicoObj && $nomeItem !== '') {
                    $medicoObj = $medicoModel->findByNome($usuarioId, $nomeItem);
                    if ($medicoObj) {
                        $log[] = "Sub {$sub->numero}: vinculado pelo nome '{$nomeItem}' (ID: {$medicoObj->id})";
                    }
                }

                // P3: não há CPF nos itens, pular

                if ($medicoObj) {
                    $this->apuracaoModel->update((int)$sub->id, [
                        'usuario_id' => $usuarioId,
                        'medico_id'  => (int)$medicoObj->id,
                    ]);
                    $atualizados++;
                } else {
                    $log[] = "Sub {$sub->numero}: médico não encontrado (CRM='{$crmItem}', Nome='{$nomeItem}')";
                }
            }

            AuditLogger::log('revincular_medico_sub_apuracoes', [
                'apuracao_mae_id' => (int)$id,
                'atualizados'     => $atualizados,
            ]);

            echo json_encode([
                'success'     => true,
                'atualizados' => $atualizados,
                'log'         => $log,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('[ApuracaoController] Erro ao revincular médico', [
                'apuracao_id' => $id,
                'error'       => $e->getMessage(),
            ]);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
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

    // =========================================================
    // EMAILS DO CORPO CLÍNICO
    // =========================================================

    /**
     * Dispara email de provisionamento de pagamento para o médico.
     * Chamado ao faturar apuração de prestador (conta a pagar criada).
     */
    private function dispararEmailProvisionamento(object $apuracao, int $usuarioId): void
    {
        $medicoId = (int) ($apuracao->medico_id ?? 0);
        if ($medicoId <= 0) return;

        $medicoModel = new Medico();
        $medico      = $medicoModel->findById($medicoId);
        if (!$medico || empty($medico->email)) return;

        $valor       = number_format((float) $apuracao->valor_total, 2, ',', '.');
        $vencimento  = date('d/m/Y', strtotime('+30 days'));
        $periodoIni  = !empty($apuracao->periodo_inicio) ? date('d/m/Y', strtotime($apuracao->periodo_inicio)) : '---';
        $periodoFim  = !empty($apuracao->periodo_fim)    ? date('d/m/Y', strtotime($apuracao->periodo_fim))    : '---';
        $nomeMedico  = htmlspecialchars($medico->nome ?? $apuracao->medico_nome ?? 'Prezado(a)');
        $numero      = htmlspecialchars($apuracao->numero);
        $totalExames = (int) ($apuracao->total_exames ?? 0);
        $linkVis     = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'erp.inlaudo.com.br')
                       . '/faturamento/apuracao-prestador/visualizar/' . $apuracao->id;

        $corpoHtml = <<<HTML
<p>Olá, <strong>{$nomeMedico}</strong>!</p>
<p>Informamos que foi criado um <strong>provisionamento de pagamento</strong> em seu nome no sistema <strong>ERP InLaudo</strong>.</p>

<table style="border-collapse:collapse;width:100%;margin:16px 0;">
  <tr style="background:#f3f4f6;">
    <td style="padding:10px 14px;font-weight:600;color:#374151;border:1px solid #e5e7eb;">Número da Apuração</td>
    <td style="padding:10px 14px;border:1px solid #e5e7eb;">{$numero}</td>
  </tr>
  <tr>
    <td style="padding:10px 14px;font-weight:600;color:#374151;border:1px solid #e5e7eb;">Período</td>
    <td style="padding:10px 14px;border:1px solid #e5e7eb;">{$periodoIni} &rarr; {$periodoFim}</td>
  </tr>
  <tr style="background:#f3f4f6;">
    <td style="padding:10px 14px;font-weight:600;color:#374151;border:1px solid #e5e7eb;">Total de Exames</td>
    <td style="padding:10px 14px;border:1px solid #e5e7eb;">{$totalExames}</td>
  </tr>
  <tr>
    <td style="padding:10px 14px;font-weight:600;color:#374151;border:1px solid #e5e7eb;">Valor Provisionado</td>
    <td style="padding:10px 14px;border:1px solid #e5e7eb;font-size:18px;color:#059669;font-weight:700;">R$ {$valor}</td>
  </tr>
  <tr style="background:#f3f4f6;">
    <td style="padding:10px 14px;font-weight:600;color:#374151;border:1px solid #e5e7eb;">Previsão de Pagamento</td>
    <td style="padding:10px 14px;border:1px solid #e5e7eb;">{$vencimento}</td>
  </tr>
</table>

<p>Você pode visualizar os detalhes completos da apuração acessando o link abaixo:</p>
<p style="text-align:center;margin:24px 0;">
  <a href="{$linkVis}" style="background:#1a56db;color:#fff;padding:12px 28px;border-radius:6px;text-decoration:none;font-weight:600;">
    Visualizar Apuração
  </a>
</p>
<p style="color:#6b7280;font-size:13px;">Em caso de dúvidas, entre em contato com a equipe administrativa.</p>
HTML;

        $service = new EmailAlertaService($usuarioId);
        $service->disparar(
            'corpo_clinico_provisionamento_criado',
            (string) $medico->email,
            (string) ($medico->nome ?? ''),
            "Provisionamento de Pagamento Criado — Apuração {$numero}",
            $corpoHtml
        );
    }

    /**
     * Dispara email de apuração de prestador concluída para o médico,
     * com link de visualização e PDF anexado.
     */
    private function dispararEmailApuracao(object $apuracao, int $usuarioId): void
    {
        $medicoId = (int) ($apuracao->medico_id ?? 0);
        if ($medicoId <= 0) return;

        $medicoModel = new Medico();
        $medico      = $medicoModel->findById($medicoId);
        if (!$medico || empty($medico->email)) return;

        $valor         = number_format((float) $apuracao->valor_total, 2, ',', '.');
        $periodoIni    = !empty($apuracao->periodo_inicio) ? date('d/m/Y', strtotime($apuracao->periodo_inicio)) : '---';
        $periodoFim    = !empty($apuracao->periodo_fim)    ? date('d/m/Y', strtotime($apuracao->periodo_fim))    : '---';
        $nomeMedico    = htmlspecialchars($medico->nome ?? $apuracao->medico_nome ?? 'Prezado(a)');
        $numero        = htmlspecialchars($apuracao->numero);
        $totalExames   = (int) ($apuracao->total_exames ?? 0);
        $totalNormal   = (int) ($apuracao->total_normal ?? 0);
        $totalUrgencia = (int) ($apuracao->total_urgencia ?? 0);
        $crm           = htmlspecialchars($apuracao->medico_crm ?? $medico->crm ?? '');
        $linkVis       = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'erp.inlaudo.com.br')
                         . '/faturamento/apuracao-prestador/visualizar/' . $apuracao->id;

        $corpoHtml = <<<HTML
<p>Olá, <strong>{$nomeMedico}</strong>!</p>
<p>Sua <strong>Apuração de Prestador</strong> foi concluída e faturada. Confira o resumo abaixo:</p>

<table style="border-collapse:collapse;width:100%;margin:16px 0;">
  <tr style="background:#1a56db;">
    <td colspan="2" style="padding:12px 14px;color:#fff;font-weight:700;font-size:15px;">Resumo da Apuração {$numero}</td>
  </tr>
  <tr>
    <td style="padding:10px 14px;font-weight:600;color:#374151;border:1px solid #e5e7eb;">Médico</td>
    <td style="padding:10px 14px;border:1px solid #e5e7eb;">{$nomeMedico} &mdash; CRM {$crm}</td>
  </tr>
  <tr style="background:#f3f4f6;">
    <td style="padding:10px 14px;font-weight:600;color:#374151;border:1px solid #e5e7eb;">Período</td>
    <td style="padding:10px 14px;border:1px solid #e5e7eb;">{$periodoIni} &rarr; {$periodoFim}</td>
  </tr>
  <tr>
    <td style="padding:10px 14px;font-weight:600;color:#374151;border:1px solid #e5e7eb;">Total de Exames</td>
    <td style="padding:10px 14px;border:1px solid #e5e7eb;">{$totalExames} ({$totalNormal} normais + {$totalUrgencia} urgências)</td>
  </tr>
  <tr style="background:#f3f4f6;">
    <td style="padding:10px 14px;font-weight:600;color:#374151;border:1px solid #e5e7eb;">Valor Total</td>
    <td style="padding:10px 14px;border:1px solid #e5e7eb;font-size:18px;color:#059669;font-weight:700;">R$ {$valor}</td>
  </tr>
</table>

<p>Acesse o link abaixo para visualizar a apuração completa com todos os exames detalhados:</p>
<p style="text-align:center;margin:24px 0;">
  <a href="{$linkVis}" style="background:#1a56db;color:#fff;padding:12px 28px;border-radius:6px;text-decoration:none;font-weight:600;">
    Visualizar Apuração Completa
  </a>
</p>
<p style="color:#6b7280;font-size:13px;">O PDF completo da apuração está anexado a este e-mail para sua conveniência.</p>
HTML;

        // Gerar PDF da apuração via weasyprint
        $pdfPath = null;
        $pdfName = "apuracao-{$apuracao->numero}.pdf";
        try {
            $pdfPath = $this->gerarPdfApuracao($apuracao, $usuarioId);
        } catch (\Throwable $pdfEx) {
            $this->logger->warning('[ApuracaoController] Falha ao gerar PDF para email: ' . $pdfEx->getMessage());
        }

        $service = new EmailAlertaService($usuarioId);
        $service->disparar(
            'corpo_clinico_apuracao_concluida',
            (string) $medico->email,
            (string) ($medico->nome ?? ''),
            "Apuração de Prestador Concluída — {$numero}",
            $corpoHtml,
            $pdfPath,
            $pdfName
        );

        // Limpar PDF temporário
        if ($pdfPath && file_exists($pdfPath)) {
            @unlink($pdfPath);
        }
    }

    /**
     * Gera um PDF da apuração de prestador usando weasyprint.
     * Retorna o caminho do arquivo PDF temporário gerado.
     */
    private function gerarPdfApuracao(object $apuracao, int $usuarioId): string
    {
        $itens         = $this->itemModel->findByApuracaoId((int) $apuracao->id);
        $valor         = number_format((float) $apuracao->valor_total, 2, ',', '.');
        $periodoIni    = !empty($apuracao->periodo_inicio) ? date('d/m/Y', strtotime($apuracao->periodo_inicio)) : '---';
        $periodoFim    = !empty($apuracao->periodo_fim)    ? date('d/m/Y', strtotime($apuracao->periodo_fim))    : '---';
        $nomeMedico    = htmlspecialchars($apuracao->medico_nome ?? '---');
        $crm           = htmlspecialchars($apuracao->medico_crm ?? '---');
        $numero        = htmlspecialchars($apuracao->numero);
        $totalExames   = (int) ($apuracao->total_exames ?? 0);
        $totalNormal   = (int) ($apuracao->total_normal ?? 0);
        $totalUrgencia = (int) ($apuracao->total_urgencia ?? 0);
        $dataGeracao   = date('d/m/Y H:i');

        // Montar linhas da tabela de itens
        $linhasItens = '';
        foreach ($itens as $i => $item) {
            $bg        = ($i % 2 === 0) ? '#ffffff' : '#f9fafb';
            $prioridade = ($item->prioridade ?? '') === 'urgente' ? 'Urgente' : 'Normal';
            $custo      = number_format((float)($item->valor_calculado ?? 0), 2, ',', '.');
            $linhasItens .= "<tr style='background:{$bg};'>";
            $linhasItens .= "<td style='padding:7px 10px;border-bottom:1px solid #e5e7eb;'>" . ($i + 1) . "</td>";
            $linhasItens .= "<td style='padding:7px 10px;border-bottom:1px solid #e5e7eb;'>" . htmlspecialchars($item->paciente_nome ?? '') . "</td>";
            $linhasItens .= "<td style='padding:7px 10px;border-bottom:1px solid #e5e7eb;'>" . htmlspecialchars($item->exame_nome ?? $item->exame_tag ?? '') . "</td>";
            $linhasItens .= "<td style='padding:7px 10px;border-bottom:1px solid #e5e7eb;text-align:center;'>" . htmlspecialchars($item->modalidade ?? '') . "</td>";
            $linhasItens .= "<td style='padding:7px 10px;border-bottom:1px solid #e5e7eb;text-align:center;'>{$prioridade}</td>";
            $linhasItens .= "<td style='padding:7px 10px;border-bottom:1px solid #e5e7eb;text-align:right;'>R$ {$custo}</td>";
            $linhasItens .= "</tr>";
        }

        $html = <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Apuração {$numero}</title>
<style>
  body { font-family: Arial, sans-serif; font-size: 13px; color: #1f2937; margin: 0; padding: 20px; }
  .header { background: #1a56db; color: #fff; padding: 20px 24px; border-radius: 6px 6px 0 0; }
  .header h1 { margin: 0; font-size: 18px; }
  .header p  { margin: 4px 0 0; font-size: 12px; color: #c7d9ff; }
  .info-box { background: #f9fafb; border: 1px solid #e5e7eb; padding: 16px 20px; margin: 16px 0; border-radius: 6px; }
  .info-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; }
  .info-item label { display: block; font-size: 11px; color: #6b7280; margin-bottom: 2px; }
  .info-item span  { font-weight: 600; font-size: 13px; }
  .valor-destaque { color: #059669; font-size: 20px; font-weight: 700; }
  table.itens { width: 100%; border-collapse: collapse; margin-top: 16px; }
  table.itens th { background: #1a56db; color: #fff; padding: 9px 10px; text-align: left; font-size: 12px; }
  table.itens th:last-child { text-align: right; }
  .footer { margin-top: 24px; text-align: center; font-size: 11px; color: #9ca3af; border-top: 1px solid #e5e7eb; padding-top: 12px; }
</style>
</head>
<body>
  <div class="header">
    <h1>ERP InLaudo &mdash; Apuração de Prestador</h1>
    <p>Gerado em {$dataGeracao}</p>
  </div>

  <div class="info-box">
    <div class="info-grid">
      <div class="info-item"><label>Número</label><span>{$numero}</span></div>
      <div class="info-item"><label>Médico</label><span>{$nomeMedico}</span></div>
      <div class="info-item"><label>CRM</label><span>{$crm}</span></div>
      <div class="info-item"><label>Período</label><span>{$periodoIni} &rarr; {$periodoFim}</span></div>
      <div class="info-item"><label>Total Exames</label><span>{$totalExames} ({$totalNormal}N + {$totalUrgencia}U)</span></div>
      <div class="info-item"><label>Valor Total</label><span class="valor-destaque">R$ {$valor}</span></div>
    </div>
  </div>

  <table class="itens">
    <thead>
      <tr>
        <th style="width:40px;">#</th>
        <th>Paciente</th>
        <th>Exame</th>
        <th style="width:70px;text-align:center;">Mod.</th>
        <th style="width:70px;text-align:center;">Prioridade</th>
        <th style="width:90px;text-align:right;">Custo</th>
      </tr>
    </thead>
    <tbody>
      {$linhasItens}
    </tbody>
    <tfoot>
      <tr style="background:#f3f4f6;">
        <td colspan="5" style="padding:10px;font-weight:700;text-align:right;">Total:</td>
        <td style="padding:10px;font-weight:700;text-align:right;color:#059669;">R$ {$valor}</td>
      </tr>
    </tfoot>
  </table>

  <div class="footer">
    ERP InLaudo &mdash; Documento gerado automaticamente em {$dataGeracao}
  </div>
</body>
</html>
HTML;

        // Salvar HTML temporário
        $tmpHtml = sys_get_temp_dir() . '/apuracao_' . $apuracao->id . '_' . time() . '.html';
        $tmpPdf  = sys_get_temp_dir() . '/apuracao_' . $apuracao->id . '_' . time() . '.pdf';
        file_put_contents($tmpHtml, $html);

        // Gerar PDF via weasyprint
        $cmd = 'weasyprint ' . escapeshellarg($tmpHtml) . ' ' . escapeshellarg($tmpPdf) . ' 2>&1';
        shell_exec($cmd);

        // Limpar HTML temporário
        @unlink($tmpHtml);

        if (!file_exists($tmpPdf) || filesize($tmpPdf) < 100) {
            throw new \RuntimeException('weasyprint não gerou o PDF corretamente.');
        }

        return $tmpPdf;
    }
}
