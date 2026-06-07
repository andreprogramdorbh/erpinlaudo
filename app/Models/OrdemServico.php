<?php
namespace App\Models;

use App\Core\Database;
use App\Core\Logger;

class OrdemServico
{
    private \PDO   $pdo;
    private Logger $logger;
    private string $table     = 'manut_ordens_servico';
    private string $tableSeq  = 'manut_os_seq';
    private string $tableHist = 'manut_os_historico';

    public function __construct()
    {
        $this->pdo    = Database::getInstance();
        $this->logger = new Logger();
    }

    // =========================================================================
    // Geração de número sequencial  OS-2026-00001
    // =========================================================================
    public function gerarNumero(int $uid): string
    {
        $ano = (int) date('Y');
        try {
            $this->pdo->beginTransaction();
            $stmt = $this->pdo->prepare(
                "SELECT ultimo_numero FROM {$this->tableSeq}
                 WHERE usuario_id = :u AND ano = :a FOR UPDATE"
            );
            $stmt->execute([':u' => $uid, ':a' => $ano]);
            $row = $stmt->fetch(\PDO::FETCH_OBJ);
            if ($row) {
                $proximo = (int)$row->ultimo_numero + 1;
                $this->pdo->prepare(
                    "UPDATE {$this->tableSeq} SET ultimo_numero = :n
                     WHERE usuario_id = :u AND ano = :a"
                )->execute([':n' => $proximo, ':u' => $uid, ':a' => $ano]);
            } else {
                $proximo = 1;
                $this->pdo->prepare(
                    "INSERT INTO {$this->tableSeq} (usuario_id, ano, ultimo_numero)
                     VALUES (:u, :a, 1)"
                )->execute([':u' => $uid, ':a' => $ano]);
            }
            $this->pdo->commit();
            return 'OS-' . $ano . '-' . str_pad($proximo, 5, '0', STR_PAD_LEFT);
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            $this->logger->error('[OrdemServico::gerarNumero] ' . $e->getMessage());
            return 'OS-' . $ano . '-' . str_pad(rand(1000, 9999), 5, '0', STR_PAD_LEFT);
        }
    }

    // =========================================================================
    // CREATE
    // =========================================================================
    public function create(array $d): int|false
    {
        try {
            $sql = "INSERT INTO {$this->table}
                    (usuario_id, numero, tipo, status, cliente_id, cliente_nome,
                     cliente_cpf_cnpj, cliente_email, cliente_telefone, cliente_endereco,
                     cliente_cidade, cliente_estado, equipamento_id, produto_id,
                     produto_nome, produto_codigo, numero_serie, marca, modelo, vida_util_meses, motivo_chamado,
                     descricao_servico, data_abertura, data_previsao, tecnico_responsavel,
                     prioridade, valor_servico, valor_pecas, valor_total,
                     proposta_id, observacoes, token_impressao)
                    VALUES
                    (:usuario_id, :numero, :tipo, :status, :cliente_id, :cliente_nome,
                     :cliente_cpf_cnpj, :cliente_email, :cliente_telefone, :cliente_endereco,
                     :cliente_cidade, :cliente_estado, :equipamento_id, :produto_id,
                     :produto_nome, :produto_codigo, :numero_serie, :marca, :modelo, :vida_util_meses, :motivo_chamado,
                     :descricao_servico, :data_abertura, :data_previsao, :tecnico_responsavel,
                     :prioridade, :valor_servico, :valor_pecas, :valor_total,
                     :proposta_id, :observacoes, :token_impressao)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':usuario_id'         => $d['usuario_id'],
                ':numero'             => $d['numero'],
                ':tipo'               => $d['tipo']               ?? 'corretiva',
                ':status'             => $d['status']             ?? 'aberta',
                ':cliente_id'         => $d['cliente_id']         ?? null,
                ':cliente_nome'       => $d['cliente_nome']       ?? '',
                ':cliente_cpf_cnpj'   => $d['cliente_cpf_cnpj']   ?? null,
                ':cliente_email'      => $d['cliente_email']       ?? null,
                ':cliente_telefone'   => $d['cliente_telefone']    ?? null,
                ':cliente_endereco'   => $d['cliente_endereco']    ?? null,
                ':cliente_cidade'     => $d['cliente_cidade']      ?? null,
                ':cliente_estado'     => $d['cliente_estado']      ?? null,
                ':equipamento_id'     => $d['equipamento_id']      ?? null,
                ':produto_id'         => $d['produto_id']          ?? null,
                ':produto_nome'       => $d['produto_nome']        ?? null,
                ':produto_codigo'     => $d['produto_codigo']      ?? null,
                ':numero_serie'       => $d['numero_serie']        ?? null,
                ':marca'              => $d['marca']               ?? null,
                ':modelo'             => $d['modelo']              ?? null,
                ':vida_util_meses'    => isset($d['vida_util_meses']) && $d['vida_util_meses'] !== null ? (int)$d['vida_util_meses'] : null,
                ':motivo_chamado'     => $d['motivo_chamado']      ?? '',
                ':descricao_servico'  => $d['descricao_servico']   ?? null,
                ':data_abertura'      => $d['data_abertura']       ?? date('Y-m-d'),
                ':data_previsao'      => $d['data_previsao']       ?? null,
                ':tecnico_responsavel'=> $d['tecnico_responsavel'] ?? null,
                ':prioridade'         => $d['prioridade']          ?? 'normal',
                ':valor_servico'      => (float)($d['valor_servico'] ?? 0),
                ':valor_pecas'        => (float)($d['valor_pecas']   ?? 0),
                ':valor_total'        => (float)($d['valor_total']   ?? 0),
                ':proposta_id'        => $d['proposta_id']         ?? null,
                ':observacoes'        => $d['observacoes']         ?? null,
                ':token_impressao'    => $d['token_impressao']     ?? bin2hex(random_bytes(16)),
            ]);
            $id = (int) $this->pdo->lastInsertId();
            $this->logger->info('[OrdemServico::create] OS criada', ['id' => $id, 'numero' => $d['numero']]);
            return $id;
        } catch (\Throwable $e) {
            $this->logger->error('[OrdemServico::create] ' . $e->getMessage(), $d);
            return false;
        }
    }

    // =========================================================================
    // UPDATE
    // =========================================================================
    public function update(int $id, array $d): bool
    {
        try {
            $sets   = [];
            $params = [':id' => $id];
            $campos = [
                'tipo','status','cliente_id','cliente_nome','cliente_cpf_cnpj',
                'cliente_email','cliente_telefone','cliente_endereco','cliente_cidade',
                'cliente_estado','equipamento_id','produto_id','produto_nome',
                'produto_codigo','numero_serie','marca','modelo','vida_util_meses','motivo_chamado','descricao_servico',
                'evolucao','data_previsao','data_conclusao','tecnico_responsavel',
                'prioridade','valor_servico','valor_pecas','valor_total',
                'proposta_id','pedido_venda_id','conta_receber_id','observacoes',
            ];
            foreach ($campos as $c) {
                if (array_key_exists($c, $d)) {
                    $sets[]       = "`{$c}` = :{$c}";
                    $params[":{$c}"] = $d[$c];
                }
            }
            if (empty($sets)) return true;
            $this->pdo->prepare(
                "UPDATE {$this->table} SET " . implode(', ', $sets) . " WHERE id = :id"
            )->execute($params);
            $this->logger->info('[OrdemServico::update] OS atualizada', ['id' => $id]);
            return true;
        } catch (\Throwable $e) {
            $this->logger->error('[OrdemServico::update] ' . $e->getMessage(), ['id' => $id]);
            return false;
        }
    }

    // =========================================================================
    // FIND BY ID
    // =========================================================================
    public function findById(int $id): object|null
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM {$this->table} WHERE id = :id LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(\PDO::FETCH_OBJ) ?: null;
    }

    // =========================================================================
    // FIND BY USUARIO (listagem com filtros)
    // =========================================================================
    public function findByUsuarioId(int $uid, array $filtros = []): array
    {
        $where  = ['o.usuario_id = :uid'];
        $params = [':uid' => $uid];

        if (!empty($filtros['status'])) {
            $where[]          = 'o.status = :status';
            $params[':status'] = $filtros['status'];
        }
        if (!empty($filtros['tipo'])) {
            $where[]        = 'o.tipo = :tipo';
            $params[':tipo'] = $filtros['tipo'];
        }
        if (!empty($filtros['q'])) {
            $where[]      = '(o.numero LIKE :q OR o.cliente_nome LIKE :q OR o.produto_nome LIKE :q OR o.numero_serie LIKE :q)';
            $params[':q'] = '%' . $filtros['q'] . '%';
        }
        if (!empty($filtros['data_de'])) {
            $where[]           = 'o.data_abertura >= :data_de';
            $params[':data_de'] = $filtros['data_de'];
        }
        if (!empty($filtros['data_ate'])) {
            $where[]            = 'o.data_abertura <= :data_ate';
            $params[':data_ate'] = $filtros['data_ate'];
        }
        if (!empty($filtros['cliente_id'])) {
            $where[]             = 'o.cliente_id = :cliente_id';
            $params[':cliente_id'] = (int)$filtros['cliente_id'];
        }

        $sql = "SELECT o.*
                FROM {$this->table} o
                WHERE " . implode(' AND ', $where) . "
                ORDER BY o.created_at DESC
                LIMIT 500";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    // =========================================================================
    // KPIs para o dashboard do módulo
    // =========================================================================
    public function kpis(int $uid): object
    {
        $stmt = $this->pdo->prepare(
            "SELECT
               COUNT(*) AS total,
               SUM(status = 'aberta')         AS abertas,
               SUM(status = 'em_andamento')   AS em_andamento,
               SUM(status = 'concluida')      AS concluidas,
               SUM(status = 'faturada')       AS faturadas,
               SUM(tipo   = 'preventiva')     AS preventivas,
               SUM(tipo   = 'corretiva')      AS corretivas,
               SUM(MONTH(data_abertura) = MONTH(CURDATE()) AND YEAR(data_abertura) = YEAR(CURDATE())) AS mes_atual
             FROM {$this->table}
             WHERE usuario_id = :uid"
        );
        $stmt->execute([':uid' => $uid]);
        return $stmt->fetch(\PDO::FETCH_OBJ) ?: (object)[];
    }

    // =========================================================================
    // HISTÓRICO / EVOLUÇÃO
    // =========================================================================
    public function registrarHistorico(int $osId, string $statusAnterior, string $statusNovo, int $uid, string $descricao, string $nomeUsuario = ''): void
    {
        try {
            $this->pdo->prepare(
                "INSERT INTO {$this->tableHist}
                 (os_id, usuario_id, usuario_nome, status_anterior, status_novo, descricao)
                 VALUES (:os_id, :uid, :nome, :ant, :novo, :desc)"
            )->execute([
                ':os_id' => $osId,
                ':uid'   => $uid,
                ':nome'  => $nomeUsuario,
                ':ant'   => $statusAnterior,
                ':novo'  => $statusNovo,
                ':desc'  => $descricao,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('[OrdemServico::registrarHistorico] ' . $e->getMessage());
        }
    }

    public function getHistorico(int $osId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM {$this->tableHist} WHERE os_id = :id ORDER BY created_at ASC"
        );
        $stmt->execute([':id' => $osId]);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    // =========================================================================
    // TROCAS (peças e serviços realizados)
    // =========================================================================
    public function getTrocas(int $osId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT t.*, p.vida_util_meses AS produto_vida_util
             FROM manut_os_trocas t
             LEFT JOIN produtos p ON p.id = t.produto_id
             WHERE t.os_id = :id
             ORDER BY t.created_at ASC"
        );
        $stmt->execute([':id' => $osId]);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    public function addTroca(int $osId, array $d): int|false
    {
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO manut_os_trocas
                 (os_id, produto_id, produto_codigo, descricao, unidade, quantidade,
                  preco_unitario, preco_total, vida_util_meses, data_proxima_troca, observacoes)
                 VALUES
                 (:os_id, :produto_id, :produto_codigo, :descricao, :unidade, :quantidade,
                  :preco_unitario, :preco_total, :vida_util_meses, :data_proxima_troca, :observacoes)"
            );
            $stmt->execute([
                ':os_id'             => $osId,
                ':produto_id'        => $d['produto_id']         ?? null,
                ':produto_codigo'    => $d['produto_codigo']      ?? null,
                ':descricao'         => $d['descricao']           ?? '',
                ':unidade'           => $d['unidade']             ?? 'UN',
                ':quantidade'        => (float)($d['quantidade']  ?? 1),
                ':preco_unitario'    => (float)($d['preco_unitario'] ?? 0),
                ':preco_total'       => (float)($d['preco_total']    ?? 0),
                ':vida_util_meses'   => !empty($d['vida_util_meses']) ? (int)$d['vida_util_meses'] : null,
                ':data_proxima_troca'=> $d['data_proxima_troca']  ?? null,
                ':observacoes'       => $d['observacoes']         ?? null,
            ]);
            // Recalcular valor_pecas da OS
            $this->_recalcularValores($osId);
            return (int) $this->pdo->lastInsertId();
        } catch (\Throwable $e) {
            $this->logger->error('[OrdemServico::addTroca] ' . $e->getMessage());
            return false;
        }
    }

    public function deleteTroca(int $trocaId): bool
    {
        try {
            $t = $this->pdo->prepare("SELECT os_id FROM manut_os_trocas WHERE id = :id");
            $t->execute([':id' => $trocaId]);
            $row = $t->fetch(\PDO::FETCH_OBJ);
            $this->pdo->prepare("DELETE FROM manut_os_trocas WHERE id = :id")->execute([':id' => $trocaId]);
            if ($row) $this->_recalcularValores((int)$row->os_id);
            return true;
        } catch (\Throwable $e) {
            $this->logger->error('[OrdemServico::deleteTroca] ' . $e->getMessage());
            return false;
        }
    }

    private function _recalcularValores(int $osId): void
    {
        $stmt = $this->pdo->prepare(
            "SELECT COALESCE(SUM(preco_total), 0) AS total_pecas FROM manut_os_trocas WHERE os_id = :id"
        );
        $stmt->execute([':id' => $osId]);
        $row = $stmt->fetch(\PDO::FETCH_OBJ);
        $totalPecas = (float)($row->total_pecas ?? 0);
        // Buscar valor_servico atual
        $os = $this->findById($osId);
        $valorServico = (float)($os->valor_servico ?? 0);
        $this->pdo->prepare(
            "UPDATE {$this->table}
             SET valor_pecas = :pecas, valor_total = :total
             WHERE id = :id"
        )->execute([
            ':pecas' => $totalPecas,
            ':total' => $totalPecas + $valorServico,
            ':id'    => $osId,
        ]);
    }

    // =========================================================================
    // Vincular proposta gerada
    // =========================================================================
    public function vincularProposta(int $osId, int $propostaId): void
    {
        $this->pdo->prepare(
            "UPDATE {$this->table} SET proposta_id = :pid WHERE id = :id"
        )->execute([':pid' => $propostaId, ':id' => $osId]);
    }

    // =========================================================================
    // Vincular pedido de venda e fechar OS ao faturar
    // =========================================================================
    public function fecharComFaturamento(int $osId, int $pedidoVendaId, int $contaReceberId = 0): void
    {
        $this->pdo->prepare(
            "UPDATE {$this->table}
             SET status = 'faturada',
                 pedido_venda_id  = :pv,
                 conta_receber_id = :cr,
                 data_conclusao   = CURDATE()
             WHERE id = :id"
        )->execute([
            ':pv' => $pedidoVendaId,
            ':cr' => $contaReceberId ?: null,
            ':id' => $osId,
        ]);
    }

    // =========================================================================
    // Buscar por token de impressão (acesso público)
    // =========================================================================
    public function findByToken(string $token): object|null
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM {$this->table} WHERE token_impressao = :t LIMIT 1"
        );
        $stmt->execute([':t' => $token]);
        return $stmt->fetch(\PDO::FETCH_OBJ) ?: null;
    }

    // =========================================================================
    // DELETE (soft: cancelar)
    // =========================================================================
    public function cancelar(int $id, int $uid, string $motivo = ''): bool
    {
        return $this->update($id, ['status' => 'cancelada']);
    }
}
