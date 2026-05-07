<?php
namespace App\Models;

use App\Core\Model;
use PDO;

class ContaMovimentacao extends Model
{
    protected string $table = 'contas_movimentacoes';

    // -------------------------------------------------------
    // Leitura
    // -------------------------------------------------------

    public function findById(int $id): object|false
    {
        $sql = "SELECT m.*, pc.codigo AS plano_codigo, pc.nome AS plano_nome
                FROM {$this->table} m
                LEFT JOIN plano_contas pc ON pc.id = m.plano_conta_id
                WHERE m.id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    /**
     * Extrato paginado de uma conta bancária com filtros avançados
     */
    public function findByContaId(int $contaId, array $filtros = [], int $pagina = 1, int $porPagina = 50): array
    {
        $where  = ['m.conta_bancaria_id = :conta_id'];
        $params = [':conta_id' => $contaId];

        // Filtro de período
        if (!empty($filtros['data_inicio'])) {
            $where[]               = 'm.data_movimentacao >= :data_inicio';
            $params[':data_inicio'] = $filtros['data_inicio'];
        }
        if (!empty($filtros['data_fim'])) {
            $where[]              = 'm.data_movimentacao <= :data_fim';
            $params[':data_fim']  = $filtros['data_fim'];
        }

        // Filtro de tipo
        if (!empty($filtros['tipo']) && in_array($filtros['tipo'], ['credito', 'debito'])) {
            $where[]         = 'm.tipo = :tipo';
            $params[':tipo'] = $filtros['tipo'];
        }

        // Filtro de origem
        if (!empty($filtros['origem'])) {
            $where[]            = 'm.origem = :origem';
            $params[':origem']  = $filtros['origem'];
        }

        // Filtro de conciliação
        if (isset($filtros['conciliada']) && $filtros['conciliada'] !== '') {
            $where[]                = 'm.conciliada = :conciliada';
            $params[':conciliada']  = (int) $filtros['conciliada'];
        }

        // Pesquisa textual
        $q = trim($filtros['pesquisa'] ?? '');
        if ($q !== '') {
            $where[]      = '(m.descricao LIKE :q OR m.descricao_original LIKE :q OR m.categoria LIKE :q)';
            $params[':q'] = '%' . $q . '%';
        }

        $whereStr = implode(' AND ', $where);
        $offset   = ($pagina - 1) * $porPagina;

        // Total de registros
        $sqlCount = "SELECT COUNT(*) FROM {$this->table} m WHERE {$whereStr}";
        $stmtCount = $this->pdo->prepare($sqlCount);
        $stmtCount->execute($params);
        $total = (int) $stmtCount->fetchColumn();

        // Dados
        $sql = "SELECT m.*, pc.codigo AS plano_codigo, pc.nome AS plano_nome
                FROM {$this->table} m
                LEFT JOIN plano_contas pc ON pc.id = m.plano_conta_id
                WHERE {$whereStr}
                ORDER BY m.data_movimentacao DESC, m.id DESC
                LIMIT {$porPagina} OFFSET {$offset}";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $itens = $stmt->fetchAll(PDO::FETCH_OBJ);

        return [
            'itens'      => $itens,
            'total'      => $total,
            'pagina'     => $pagina,
            'por_pagina' => $porPagina,
            'paginas'    => (int) ceil($total / $porPagina),
        ];
    }

    /**
     * Resumo financeiro do período (totais de crédito, débito, saldo)
     */
    public function getResumo(int $contaId, array $filtros = []): object
    {
        $where  = ['conta_bancaria_id = :conta_id'];
        $params = [':conta_id' => $contaId];

        if (!empty($filtros['data_inicio'])) {
            $where[]               = 'data_movimentacao >= :data_inicio';
            $params[':data_inicio'] = $filtros['data_inicio'];
        }
        if (!empty($filtros['data_fim'])) {
            $where[]              = 'data_movimentacao <= :data_fim';
            $params[':data_fim']  = $filtros['data_fim'];
        }

        $whereStr = implode(' AND ', $where);
        $sql = "SELECT
                    COUNT(*) AS total_transacoes,
                    COALESCE(SUM(CASE WHEN tipo = 'credito' THEN valor ELSE 0 END), 0) AS total_credito,
                    COALESCE(SUM(CASE WHEN tipo = 'debito'  THEN ABS(valor) ELSE 0 END), 0) AS total_debito,
                    COALESCE(SUM(valor), 0) AS saldo_periodo,
                    COALESCE(SUM(CASE WHEN conciliada = 1 THEN 1 ELSE 0 END), 0) AS total_conciliadas,
                    COALESCE(SUM(CASE WHEN conciliada = 0 THEN 1 ELSE 0 END), 0) AS total_pendentes
                FROM {$this->table}
                WHERE {$whereStr}";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    /**
     * Evolução do saldo por dia (para gráfico)
     */
    public function getEvolucaoSaldo(int $contaId, string $dataInicio, string $dataFim): array
    {
        $sql = "SELECT
                    data_movimentacao AS data,
                    SUM(valor) AS variacao_dia,
                    MAX(saldo_apos) AS saldo_dia
                FROM {$this->table}
                WHERE conta_bancaria_id = ?
                  AND data_movimentacao BETWEEN ? AND ?
                GROUP BY data_movimentacao
                ORDER BY data_movimentacao ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$contaId, $dataInicio, $dataFim]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Totais por categoria no período (para gráfico de pizza)
     */
    public function getTotaisPorCategoria(int $contaId, array $filtros = []): array
    {
        $where  = ['conta_bancaria_id = :conta_id'];
        $params = [':conta_id' => $contaId];

        if (!empty($filtros['data_inicio'])) {
            $where[]               = 'data_movimentacao >= :data_inicio';
            $params[':data_inicio'] = $filtros['data_inicio'];
        }
        if (!empty($filtros['data_fim'])) {
            $where[]              = 'data_movimentacao <= :data_fim';
            $params[':data_fim']  = $filtros['data_fim'];
        }
        if (!empty($filtros['tipo'])) {
            $where[]         = 'tipo = :tipo';
            $params[':tipo'] = $filtros['tipo'];
        }

        $sql = "SELECT
                    COALESCE(categoria, 'Sem categoria') AS categoria,
                    COUNT(*) AS qtd,
                    SUM(ABS(valor)) AS total
                FROM {$this->table}
                WHERE " . implode(' AND ', $where) . "
                GROUP BY categoria
                ORDER BY total DESC
                LIMIT 10";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Verifica se uma transação já foi importada (deduplicação por hash)
     */
    public function existsByHash(int $contaId, string $hash): bool
    {
        $sql  = "SELECT COUNT(*) FROM {$this->table} WHERE conta_bancaria_id = ? AND origem_hash = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$contaId, $hash]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Busca por ID de transação do Open Finance
     */
    public function findByOpenFinanceTxId(string $txId): object|false
    {
        $sql  = "SELECT * FROM {$this->table} WHERE openfinance_tx_id = ? LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$txId]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    // -------------------------------------------------------
    // Escrita
    // -------------------------------------------------------

    public function create(array $data): string|false
    {
        $sql = "INSERT INTO {$this->table}
                (conta_bancaria_id, usuario_id, data_movimentacao, data_compensacao,
                 descricao, descricao_original, valor, tipo, saldo_apos,
                 categoria, plano_conta_id, tags, origem, origem_id, origem_hash,
                 conta_pagar_id, conta_receber_id,
                 openfinance_tx_id, openfinance_data,
                 conciliada, observacoes)
                VALUES
                (:conta_bancaria_id, :usuario_id, :data_movimentacao, :data_compensacao,
                 :descricao, :descricao_original, :valor, :tipo, :saldo_apos,
                 :categoria, :plano_conta_id, :tags, :origem, :origem_id, :origem_hash,
                 :conta_pagar_id, :conta_receber_id,
                 :openfinance_tx_id, :openfinance_data,
                 :conciliada, :observacoes)";

        $stmt = $this->pdo->prepare($sql);

        $valor = (float) str_replace(['.', ','], ['', '.'], (string)($data['valor'] ?? 0));
        $tipo  = $data['tipo'] ?? ($valor >= 0 ? 'credito' : 'debito');

        $stmt->execute([
            ':conta_bancaria_id'   => (int) $data['conta_bancaria_id'],
            ':usuario_id'          => (int) $data['usuario_id'],
            ':data_movimentacao'   => $data['data_movimentacao'],
            ':data_compensacao'    => $data['data_compensacao'] ?? null,
            ':descricao'           => trim($data['descricao']),
            ':descricao_original'  => $data['descricao_original'] ?? null,
            ':valor'               => $valor,
            ':tipo'                => $tipo,
            ':saldo_apos'          => $data['saldo_apos'] ?? null,
            ':categoria'           => $data['categoria'] ?? null,
            ':plano_conta_id'      => !empty($data['plano_conta_id']) ? (int)$data['plano_conta_id'] : null,
            ':tags'                => isset($data['tags']) ? json_encode($data['tags']) : null,
            ':origem'              => $data['origem'] ?? 'manual',
            ':origem_id'           => $data['origem_id'] ?? null,
            ':origem_hash'         => $data['origem_hash'] ?? null,
            ':conta_pagar_id'      => !empty($data['conta_pagar_id']) ? (int)$data['conta_pagar_id'] : null,
            ':conta_receber_id'    => !empty($data['conta_receber_id']) ? (int)$data['conta_receber_id'] : null,
            ':openfinance_tx_id'   => $data['openfinance_tx_id'] ?? null,
            ':openfinance_data'    => isset($data['openfinance_data']) ? json_encode($data['openfinance_data']) : null,
            ':conciliada'          => isset($data['conciliada']) ? (int)$data['conciliada'] : 0,
            ':observacoes'         => $data['observacoes'] ?? null,
        ]);

        return $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $allowed = [
            'data_movimentacao', 'data_compensacao', 'descricao', 'valor', 'tipo',
            'categoria', 'plano_conta_id', 'tags', 'conciliada', 'data_conciliacao',
            'observacoes', 'conta_pagar_id', 'conta_receber_id',
        ];

        $sets   = [];
        $params = [':id' => $id];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $sets[]              = "`{$field}` = :{$field}";
                $params[":{$field}"] = $data[$field];
            }
        }

        if (empty($sets)) {
            return false;
        }

        $sql  = "UPDATE {$this->table} SET " . implode(', ', $sets) . " WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function conciliar(int $id): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE {$this->table} SET conciliada = 1, data_conciliacao = NOW() WHERE id = ?"
        );
        return $stmt->execute([$id]);
    }

    public function desconciliar(int $id): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE {$this->table} SET conciliada = 0, data_conciliacao = NULL WHERE id = ?"
        );
        return $stmt->execute([$id]);
    }
}
