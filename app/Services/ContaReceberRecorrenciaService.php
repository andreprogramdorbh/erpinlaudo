<?php

namespace App\Services;

use App\Core\Audit\AuditLogger;
use App\Core\Logger;
use App\Models\ContaReceber;

/**
 * ContaReceberRecorrenciaService
 *
 * Responsável por toda a lógica de geração de parcelas recorrentes.
 *
 * Dois modos de operação:
 *
 * 1. ROLLING (comportamento original):
 *    - Ao marcar uma conta como "recebida", gera automaticamente a próxima parcela.
 *    - Ideal para contratos de prazo indeterminado.
 *
 * 2. ANTECIPADO (novo):
 *    - Ao criar a conta com recorrência + intervalo definido, gera TODAS as parcelas
 *      de uma vez (ex: 12 mensais a partir de uma data de vencimento).
 *    - Cada parcela recebe numero_parcela, total_parcelas e grupo_parcelas.
 *    - Ideal para contratos de prazo determinado.
 */
class ContaReceberRecorrenciaService
{
    private ContaReceber $model;
    private Logger $logger;

    public function __construct()
    {
        $this->model  = new ContaReceber();
        $this->logger = new Logger();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // MODO ANTECIPADO — gera todas as parcelas de uma vez
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Gera todas as parcelas de uma conta recorrente de uma vez.
     *
     * @param int   $usuarioId       ID do usuário/tenant
     * @param int   $contaRaizId     ID da primeira conta já criada (parcela 1)
     * @param int   $totalParcelas   Número total de parcelas (ex: 12)
     * @param string $tipo           Tipo de recorrência: mensal|semanal|anual|customizada
     * @param int   $intervalo       Intervalo (ex: 1 para mensal, 3 para trimestral)
     * @param int|null $contratoId   Contrato vinculado (opcional)
     * @return array{geradas: int, ids: int[], erros: string[]}
     */
    public function gerarTodasParcelas(
        int $usuarioId,
        int $contaRaizId,
        int $totalParcelas,
        string $tipo,
        int $intervalo,
        ?int $contratoId = null
    ): array {
        $resultado = ['geradas' => 0, 'ids' => [], 'erros' => []];

        try {
            if ($totalParcelas <= 1) {
                // Apenas 1 parcela = a própria conta raiz, nada a gerar
                return $resultado;
            }

            $contaRaiz = $this->model->findById($contaRaizId);
            if (!$contaRaiz || (int)($contaRaiz->usuario_id ?? 0) !== $usuarioId) {
                $resultado['erros'][] = 'Conta raiz não encontrada ou sem permissão.';
                return $resultado;
            }

            // Gerar identificador único do grupo
            $grupoParcelas = 'grp:' . $contaRaizId . ':' . date('YmdHis') . ':' . $usuarioId;

            // Atualizar a conta raiz com os dados do grupo (parcela 1 de N)
            $this->model->update($contaRaizId, [
                'numero_parcela'   => 1,
                'total_parcelas'   => $totalParcelas,
                'grupo_parcelas'   => $grupoParcelas,
                'recorrencia_modo' => 'antecipado',
                'contrato_id'      => $contratoId,
                'external_reference' => 'cr:' . $contaRaizId . '|u:' . $usuarioId . '|root:' . $contaRaizId . '|grp:' . $grupoParcelas,
            ]);

            $resultado['ids'][] = $contaRaizId;

            // Gerar parcelas 2 até N
            $dataBase = (string)($contaRaiz->data_vencimento ?? '');

            for ($i = 2; $i <= $totalParcelas; $i++) {
                $dataVencimento = $this->calcularVencimento($dataBase, $tipo, $intervalo, $i - 1);

                if ($dataVencimento === '') {
                    $resultado['erros'][] = "Não foi possível calcular a data da parcela {$i}.";
                    continue;
                }

                // Verificar se já existe parcela para este grupo e data
                if ($this->existeParcelaNoGrupo($usuarioId, $grupoParcelas, $dataVencimento)) {
                    $resultado['erros'][] = "Parcela {$i} ({$dataVencimento}) já existe no grupo.";
                    continue;
                }

                $descricaoBase = (string)($contaRaiz->descricao ?? '');
                // Formatar descrição: "Serviço X — Parcela 2/12"
                $descricao = $this->formatarDescricaoParcela($descricaoBase, $i, $totalParcelas);

                $novoId = $this->model->create([
                    'usuario_id'           => $usuarioId,
                    'cliente_id'           => (int)($contaRaiz->cliente_id ?? 0),
                    'plano_conta_id'       => (int)($contaRaiz->plano_conta_id ?? 0),
                    'descricao'            => $descricao,
                    'valor'                => (string)($contaRaiz->valor ?? '0.00'),
                    'data_vencimento'      => $dataVencimento,
                    'data_recebimento'     => null,
                    'status'               => 'aberta',
                    'observacoes'          => $contaRaiz->observacoes ?? null,
                    'meio_pagamento'       => $contaRaiz->meio_pagamento ?? null,
                    'recorrente'           => 1,
                    'recorrencia_tipo'     => $tipo,
                    'recorrencia_intervalo'=> $intervalo,
                    'recorrencia_modo'     => 'antecipado',
                    'numero_parcela'       => $i,
                    'total_parcelas'       => $totalParcelas,
                    'grupo_parcelas'       => $grupoParcelas,
                    'contrato_id'          => $contratoId,
                    'asaas_payment_id'     => null,
                    'asaas_subscription_id'=> null,
                    'external_reference'   => null,
                ]);

                if (!$novoId) {
                    $resultado['erros'][] = "Falha ao criar parcela {$i} no banco de dados.";
                    $this->logger->error("ContaReceberRecorrenciaService::gerarTodasParcelas - falha ao criar parcela {$i}", [
                        'usuario_id'    => $usuarioId,
                        'conta_raiz_id' => $contaRaizId,
                        'parcela'       => $i,
                        'vencimento'    => $dataVencimento,
                    ]);
                    continue;
                }

                // Atualizar external_reference com o ID real
                $this->model->update((int)$novoId, [
                    'external_reference' => 'cr:' . (int)$novoId . '|u:' . $usuarioId . '|root:' . $contaRaizId . '|grp:' . $grupoParcelas . '|prev:' . $contaRaizId,
                ]);

                $resultado['ids'][]  = (int)$novoId;
                $resultado['geradas']++;
            }

            // Auditoria
            AuditLogger::log('conta_receber_parcelas_geradas', [
                'usuario_id'     => $usuarioId,
                'conta_raiz_id'  => $contaRaizId,
                'grupo_parcelas' => $grupoParcelas,
                'total_parcelas' => $totalParcelas,
                'geradas'        => $resultado['geradas'],
                'tipo'           => $tipo,
                'intervalo'      => $intervalo,
                'contrato_id'    => $contratoId,
            ]);

        } catch (\Throwable $e) {
            $this->logger->error('ContaReceberRecorrenciaService::gerarTodasParcelas - ' . $e->getMessage(), [
                'usuario_id'    => $usuarioId,
                'conta_raiz_id' => $contaRaizId,
            ]);
            $resultado['erros'][] = 'Erro interno: ' . $e->getMessage();
        }

        return $resultado;
    }

    /**
     * Gera contas a receber a partir de um contrato.
     * Cria a primeira parcela e depois chama gerarTodasParcelas().
     *
     * @param int    $usuarioId
     * @param object $contrato   Objeto do contrato (com id, cliente_id, valor, recorrencia, etc.)
     * @param int    $planoContaId
     * @param string $meioPagamento
     * @param int    $totalParcelas
     * @param string $dataVencimentoInicial  Data do primeiro vencimento (YYYY-MM-DD)
     * @return array{sucesso: bool, geradas: int, ids: int[], erros: string[], grupo: string}
     */
    public function gerarParcelasDeContrato(
        int $usuarioId,
        object $contrato,
        int $planoContaId,
        string $meioPagamento,
        int $totalParcelas,
        string $dataVencimentoInicial
    ): array {
        $resultado = ['sucesso' => false, 'geradas' => 0, 'ids' => [], 'erros' => [], 'grupo' => ''];

        try {
            if ($totalParcelas <= 0) {
                $resultado['erros'][] = 'Número de parcelas inválido.';
                return $resultado;
            }

            $tipo      = $this->mapearRecorrenciaContrato((string)($contrato->recorrencia ?? 'mensal'));
            $intervalo = 1; // contratos sempre geram 1 período por vez

            // Formatar descrição base
            $descricaoBase = 'Contrato ' . ($contrato->numero ?? '#' . $contrato->id) . ' — ' . ($contrato->objeto ?? 'Serviço');
            $descricaoParcela1 = $this->formatarDescricaoParcela($descricaoBase, 1, $totalParcelas);

            // Criar a primeira parcela
            $primeiroId = $this->model->create([
                'usuario_id'           => $usuarioId,
                'cliente_id'           => (int)($contrato->cliente_id ?? 0),
                'plano_conta_id'       => $planoContaId,
                'descricao'            => $descricaoParcela1,
                'valor'                => (string)($contrato->valor ?? '0.00'),
                'data_vencimento'      => $dataVencimentoInicial,
                'data_recebimento'     => null,
                'status'               => 'aberta',
                'observacoes'          => 'Gerado automaticamente do contrato ' . ($contrato->numero ?? $contrato->id),
                'meio_pagamento'       => $meioPagamento ?: null,
                'recorrente'           => 1,
                'recorrencia_tipo'     => $tipo,
                'recorrencia_intervalo'=> $intervalo,
                'recorrencia_modo'     => 'antecipado',
                'numero_parcela'       => 1,
                'total_parcelas'       => $totalParcelas,
                'grupo_parcelas'       => null, // será preenchido por gerarTodasParcelas
                'contrato_id'          => (int)$contrato->id,
                'asaas_payment_id'     => null,
                'asaas_subscription_id'=> null,
                'external_reference'   => null,
            ]);

            if (!$primeiroId) {
                $resultado['erros'][] = 'Falha ao criar a primeira parcela no banco de dados.';
                $this->logger->error('ContaReceberRecorrenciaService::gerarParcelasDeContrato - falha ao criar parcela 1', [
                    'usuario_id'  => $usuarioId,
                    'contrato_id' => $contrato->id,
                ]);
                return $resultado;
            }

            $resultado['ids'][] = (int)$primeiroId;

            // Gerar todas as demais parcelas
            $subResultado = $this->gerarTodasParcelas(
                $usuarioId,
                (int)$primeiroId,
                $totalParcelas,
                $tipo,
                $intervalo,
                (int)$contrato->id
            );

            $resultado['geradas'] = $subResultado['geradas'] + 1; // +1 pela parcela raiz
            $resultado['ids']     = array_merge($resultado['ids'], $subResultado['ids']);
            $resultado['erros']   = array_merge($resultado['erros'], $subResultado['erros']);
            $resultado['sucesso'] = true;

            // Recuperar o grupo gerado
            $contaRaizAtualizada = $this->model->findById((int)$primeiroId);
            $resultado['grupo']  = (string)($contaRaizAtualizada->grupo_parcelas ?? '');

        } catch (\Throwable $e) {
            $this->logger->error('ContaReceberRecorrenciaService::gerarParcelasDeContrato - ' . $e->getMessage(), [
                'usuario_id'  => $usuarioId,
                'contrato_id' => $contrato->id ?? null,
            ]);
            $resultado['erros'][] = 'Erro interno: ' . $e->getMessage();
        }

        return $resultado;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // MODO ROLLING — gera apenas a próxima parcela ao receber a atual
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Gera a próxima parcela quando a atual é marcada como recebida.
     * Só age se a conta for recorrente e modo = 'rolling' (ou sem modo definido).
     */
    public function gerarProximaSeRecorrente(int $usuarioId, int $contaReceberId): void
    {
        try {
            $conta = $this->model->findById($contaReceberId);
            if (!$conta || (int)($conta->usuario_id ?? 0) !== (int)$usuarioId) {
                return;
            }

            if ((int)($conta->recorrente ?? 0) !== 1) {
                return;
            }

            if (($conta->status ?? '') !== 'recebida') {
                return;
            }

            // Não gerar próxima se foi gerado em modo antecipado
            $modo = (string)($conta->recorrencia_modo ?? 'rolling');
            if ($modo === 'antecipado') {
                return;
            }

            $tipo = (string)($conta->recorrencia_tipo ?? '');
            if ($tipo === '') {
                return;
            }

            $intervalo = (int)($conta->recorrencia_intervalo ?? 0);
            if ($intervalo <= 0) {
                $intervalo = 1;
            }

            $dataBase = (string)($conta->data_vencimento ?? '');
            if ($dataBase === '') {
                return;
            }

            $proximoVenc = $this->calcularVencimento($dataBase, $tipo, $intervalo, 1);
            if ($proximoVenc === '') {
                return;
            }

            $rootId = $this->extrairIntTag((string)($conta->external_reference ?? ''), 'root');
            if ($rootId <= 0) {
                $rootId = (int)$contaReceberId;
            }

            if ($this->model->existsRecorrenciaGerada($usuarioId, $contaReceberId, $rootId, $proximoVenc)) {
                return;
            }

            $novoId = $this->model->create([
                'usuario_id'           => $usuarioId,
                'cliente_id'           => (int)($conta->cliente_id ?? 0),
                'plano_conta_id'       => (int)($conta->plano_conta_id ?? 0),
                'descricao'            => (string)($conta->descricao ?? ''),
                'valor'                => (string)($conta->valor ?? '0.00'),
                'data_vencimento'      => $proximoVenc,
                'data_recebimento'     => null,
                'status'               => 'aberta',
                'observacoes'          => $conta->observacoes ?? null,
                'meio_pagamento'       => $conta->meio_pagamento ?? null,
                'recorrente'           => 1,
                'recorrencia_tipo'     => $tipo,
                'recorrencia_intervalo'=> $intervalo,
                'recorrencia_modo'     => 'rolling',
                'asaas_payment_id'     => null,
                'asaas_subscription_id'=> $conta->asaas_subscription_id ?? null,
                'external_reference'   => null,
            ]);

            if (!$novoId) {
                return;
            }

            $this->model->update((int)$novoId, [
                'external_reference' => 'cr:' . (int)$novoId . '|u:' . (int)$usuarioId . '|root:' . (int)$rootId . '|prev:' . (int)$contaReceberId,
            ]);

            AuditLogger::log('create_conta_receber_recorrencia', [
                'usuario_id'           => (int)$usuarioId,
                'conta_receber_id'     => (int)$contaReceberId,
                'nova_conta_receber_id'=> (int)$novoId,
                'root_id'              => (int)$rootId,
                'data_vencimento'      => $proximoVenc,
            ]);

        } catch (\Throwable $e) {
            $this->logger->error('ContaReceberRecorrenciaService::gerarProximaSeRecorrente - ' . $e->getMessage());
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Calcula a data de vencimento de uma parcela específica.
     *
     * @param string $dataBase     Data base (YYYY-MM-DD) — vencimento da parcela 1
     * @param string $tipo         mensal|semanal|anual|customizada
     * @param int    $intervalo    Quantidade de períodos entre parcelas
     * @param int    $offset       Quantos períodos avançar a partir da dataBase
     */
    public function calcularVencimento(string $dataBase, string $tipo, int $intervalo, int $offset): string
    {
        try {
            $dt = new \DateTimeImmutable($dataBase);
            $total = $intervalo * $offset;

            switch ($tipo) {
                case 'semanal':
                    $dt2 = $dt->modify("+{$total} week");
                    break;
                case 'mensal':
                    $dt2 = $dt->modify("+{$total} month");
                    break;
                case 'trimestral':
                    $dt2 = $dt->modify('+' . ($total * 3) . ' month');
                    break;
                case 'semestral':
                    $dt2 = $dt->modify('+' . ($total * 6) . ' month');
                    break;
                case 'anual':
                    $dt2 = $dt->modify("+{$total} year");
                    break;
                case 'customizada':
                    $dt2 = $dt->modify("+{$total} day");
                    break;
                default:
                    return '';
            }

            return $dt2->format('Y-m-d');
        } catch (\Throwable $e) {
            return '';
        }
    }

    /**
     * Formata a descrição de uma parcela.
     * Ex: "Serviço de Laudo — Parcela 2/12"
     * Se a descrição já contém "Parcela X/Y", substitui.
     */
    private function formatarDescricaoParcela(string $descricaoBase, int $numero, int $total): string
    {
        // Remove sufixo de parcela anterior se existir
        $descricaoLimpa = preg_replace('/\s*[—\-–]\s*Parcela\s+\d+\/\d+\s*$/i', '', $descricaoBase);
        $descricaoLimpa = trim((string)$descricaoLimpa);

        if ($total > 1) {
            return $descricaoLimpa . ' — Parcela ' . $numero . '/' . $total;
        }

        return $descricaoLimpa;
    }

    /**
     * Verifica se já existe uma parcela no grupo com a data de vencimento informada.
     */
    private function existeParcelaNoGrupo(int $usuarioId, string $grupoParcelas, string $dataVencimento): bool
    {
        try {
            $pdo  = (new ContaReceber())->getPdo();
            $stmt = $pdo->prepare(
                "SELECT id FROM contas_receber
                 WHERE usuario_id = ? AND grupo_parcelas = ? AND data_vencimento = ?
                 LIMIT 1"
            );
            $stmt->execute([$usuarioId, $grupoParcelas, $dataVencimento]);
            return (bool)$stmt->fetch();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Mapeia o tipo de recorrência do contrato para o tipo de conta a receber.
     */
    private function mapearRecorrenciaContrato(string $recorrenciaContrato): string
    {
        $mapa = [
            'diario'     => 'customizada',
            'semanal'    => 'semanal',
            'mensal'     => 'mensal',
            'trimestral' => 'trimestral',
            'semestral'  => 'semestral',
            'anual'      => 'anual',
        ];
        return $mapa[$recorrenciaContrato] ?? 'mensal';
    }

    private function extrairIntTag(string $externalReference, string $tag): int
    {
        if ($externalReference === '') {
            return 0;
        }
        if (preg_match('/' . preg_quote($tag, '/') . ':(\\d+)/', $externalReference, $m)) {
            return (int)$m[1];
        }
        return 0;
    }

    // Alias para compatibilidade com código legado
    private function calcularProximoVencimento(string $dataBase, string $tipo, int $intervalo): string
    {
        return $this->calcularVencimento($dataBase, $tipo, $intervalo, 1);
    }
}
