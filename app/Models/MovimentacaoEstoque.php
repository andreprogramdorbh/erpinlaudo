<?php
namespace App\Models;

use App\Core\Model;
use PDO;

class MovimentacaoEstoque extends Model
{
    protected string $table = 'est_movimentacoes';

    // ─── Logger interno ──────────────────────────────────────────────────────
    private function log(string $level, string $msg, array $ctx = []): void
    {
        $dir  = defined('BASE_PATH') ? BASE_PATH . '/storage/logs' : sys_get_temp_dir();
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        $file = $dir . '/estoque_' . date('Y-m') . '.log';
        $line = '[' . date('Y-m-d H:i:s') . '] [' . strtoupper($level) . '] [MovimentacaoEstoque] ' . $msg;
        if (!empty($ctx)) {
            $line .= ' | ctx=' . json_encode($ctx, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        @file_put_contents($file, $line . PHP_EOL, FILE_APPEND | LOCK_EX);
        error_log($line);
    }

    // ─── Registrar movimentação + atualizar estoque do produto ───────────────
    public function registrar(array $d): int|false
    {
        $this->log('info', '[registrar] Iniciando', [
            'produto_id' => $d['produto_id'] ?? null,
            'tipo'       => $d['tipo'] ?? null,
            'quantidade' => $d['quantidade'] ?? null,
        ]);

        try {
            $this->pdo->beginTransaction();

            // Snapshot do estoque atual
            $stmtProd = $this->pdo->prepare("SELECT estoque_atual FROM produtos WHERE id = ?");
            $stmtProd->execute([$d['produto_id']]);
            $estoque_antes = (float)($stmtProd->fetchColumn() ?? 0);

            $quantidade = (float)($d['quantidade'] ?? 0);
            $tipo       = $d['tipo'] ?? 'entrada';

            // Calcula estoque depois
            $delta = in_array($tipo, ['entrada', 'devolucao_venda']) ? $quantidade : -$quantidade;
            $estoque_depois = $estoque_antes + $delta;

            // Insere movimentação
            $stmt = $this->pdo->prepare(
                "INSERT INTO {$this->table}
                    (usuario_id, produto_id, tipo, origem,
                     pedido_compra_id, pedido_venda_id,
                     nfe_chave, nfe_numero, nfe_serie, nfe_emitente_cnpj, nfe_emitente_nome, nfe_data_emissao,
                     quantidade, unidade, preco_unitario, valor_total, custo_unitario,
                     lote, data_fabricacao, data_validade, localizacao,
                     estoque_antes, estoque_depois,
                     motivo, observacoes, usuario_responsavel)
                 VALUES
                    (:usuario_id, :produto_id, :tipo, :origem,
                     :pedido_compra_id, :pedido_venda_id,
                     :nfe_chave, :nfe_numero, :nfe_serie, :nfe_emitente_cnpj, :nfe_emitente_nome, :nfe_data_emissao,
                     :quantidade, :unidade, :preco_unitario, :valor_total, :custo_unitario,
                     :lote, :data_fabricacao, :data_validade, :localizacao,
                     :estoque_antes, :estoque_depois,
                     :motivo, :observacoes, :usuario_responsavel)"
            );
            $stmt->execute([
                ':usuario_id'          => $d['usuario_id'],
                ':produto_id'          => $d['produto_id'],
                ':tipo'                => $tipo,
                ':origem'              => $d['origem'] ?? 'manual',
                ':pedido_compra_id'    => $d['pedido_compra_id'] ?? null,
                ':pedido_venda_id'     => $d['pedido_venda_id'] ?? null,
                ':nfe_chave'           => $d['nfe_chave'] ?? null,
                ':nfe_numero'          => $d['nfe_numero'] ?? null,
                ':nfe_serie'           => $d['nfe_serie'] ?? null,
                ':nfe_emitente_cnpj'   => $d['nfe_emitente_cnpj'] ?? null,
                ':nfe_emitente_nome'   => $d['nfe_emitente_nome'] ?? null,
                ':nfe_data_emissao'    => $d['nfe_data_emissao'] ?? null,
                ':quantidade'          => $quantidade,
                ':unidade'             => $d['unidade'] ?? 'UN',
                ':preco_unitario'      => (float)($d['preco_unitario'] ?? 0),
                ':valor_total'         => (float)($d['valor_total'] ?? ($quantidade * ($d['preco_unitario'] ?? 0))),
                ':custo_unitario'      => (float)($d['custo_unitario'] ?? 0),
                ':lote'                => $d['lote'] ?? null,
                ':data_fabricacao'     => $d['data_fabricacao'] ?? null,
                ':data_validade'       => $d['data_validade'] ?? null,
                ':localizacao'         => $d['localizacao'] ?? null,
                ':estoque_antes'       => $estoque_antes,
                ':estoque_depois'      => $estoque_depois,
                ':motivo'              => $d['motivo'] ?? null,
                ':observacoes'         => $d['observacoes'] ?? null,
                ':usuario_responsavel' => $d['usuario_id'],
            ]);
            $movId = (int)$this->pdo->lastInsertId();

            // Atualiza estoque do produto
            $op = $delta >= 0 ? '+' : '-';
            $abs = abs($delta);
            $stmtUp = $this->pdo->prepare(
                "UPDATE produtos SET estoque_atual = estoque_atual {$op} ? WHERE id = ?"
            );
            $stmtUp->execute([$abs, $d['produto_id']]);

            $this->pdo->commit();
            $this->log('info', '[registrar] Movimentação registrada', [
                'mov_id'         => $movId,
                'estoque_antes'  => $estoque_antes,
                'estoque_depois' => $estoque_depois,
            ]);
            return $movId;
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            $this->log('error', '[registrar] Erro: ' . $e->getMessage(), [
                'trace' => substr($e->getTraceAsString(), 0, 500),
            ]);
            return false;
        }
    }

    // ─── Listagem com filtros ────────────────────────────────────────────────
    public function findByUsuarioId(int $usuarioId, array $filtros = []): array
    {
        $where  = ["m.usuario_id = :uid"];
        $params = [':uid' => $usuarioId];

        if (!empty($filtros['produto_id'])) {
            $where[] = "m.produto_id = :pid";
            $params[':pid'] = (int)$filtros['produto_id'];
        }
        if (!empty($filtros['tipo'])) {
            $where[] = "m.tipo = :tipo";
            $params[':tipo'] = $filtros['tipo'];
        }
        if (!empty($filtros['origem'])) {
            $where[] = "m.origem = :origem";
            $params[':origem'] = $filtros['origem'];
        }
        if (!empty($filtros['data_inicio'])) {
            $where[] = "DATE(m.created_at) >= :di";
            $params[':di'] = $filtros['data_inicio'];
        }
        if (!empty($filtros['data_fim'])) {
            $where[] = "DATE(m.created_at) <= :df";
            $params[':df'] = $filtros['data_fim'];
        }
        if (!empty($filtros['q'])) {
            $where[] = "(p.nome LIKE :q OR p.codigo LIKE :q OR m.nfe_numero LIKE :q OR m.nfe_emitente_nome LIKE :q)";
            $params[':q'] = '%' . $filtros['q'] . '%';
        }

        $limit  = (int)($filtros['limit'] ?? 100);
        $offset = (int)($filtros['offset'] ?? 0);

        $sql = "SELECT m.*,
                       p.nome AS produto_nome, p.codigo AS produto_codigo,
                       p.unidade_medida AS produto_unidade,
                       u.name AS responsavel_nome
                FROM {$this->table} m
                LEFT JOIN produtos p ON p.id = m.produto_id
                LEFT JOIN users u ON u.id = m.usuario_responsavel
                WHERE " . implode(' AND ', $where) . "
                ORDER BY m.created_at DESC
                LIMIT {$limit} OFFSET {$offset}";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    // ─── Busca por ID ────────────────────────────────────────────────────────
    public function findById(int $id): object|false
    {
        $stmt = $this->pdo->prepare(
            "SELECT m.*,
                    p.nome AS produto_nome, p.codigo AS produto_codigo,
                    p.unidade_medida AS produto_unidade, p.imagem_principal,
                    u.name AS responsavel_nome
             FROM {$this->table} m
             LEFT JOIN produtos p ON p.id = m.produto_id
             LEFT JOIN users u ON u.id = m.usuario_responsavel
             WHERE m.id = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    // ─── KPIs do período ─────────────────────────────────────────────────────
    public function kpis(int $usuarioId, string $inicio = '', string $fim = ''): object
    {
        $where  = "usuario_id = ?";
        $params = [$usuarioId];
        if ($inicio) { $where .= " AND DATE(created_at) >= ?"; $params[] = $inicio; }
        if ($fim)    { $where .= " AND DATE(created_at) <= ?"; $params[] = $fim; }

        $stmt = $this->pdo->prepare(
            "SELECT
               COUNT(*)                                AS total_movimentacoes,
               SUM(tipo = 'entrada')                   AS total_entradas,
               SUM(tipo = 'saida')                     AS total_saidas,
               SUM(tipo = 'ajuste')                    AS total_ajustes,
               SUM(CASE WHEN tipo = 'entrada' THEN valor_total ELSE 0 END) AS valor_entradas,
               SUM(CASE WHEN tipo = 'saida'   THEN valor_total ELSE 0 END) AS valor_saidas,
               COUNT(DISTINCT produto_id)              AS produtos_movimentados
             FROM {$this->table}
             WHERE {$where}"
        );
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_OBJ) ?: (object)[];
    }

    // ─── Histórico de um produto ─────────────────────────────────────────────
    public function findByProdutoId(int $produtoId, int $limit = 50): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT m.*, u.name AS responsavel_nome
             FROM {$this->table} m
             LEFT JOIN users u ON u.id = m.usuario_responsavel
             WHERE m.produto_id = ?
             ORDER BY m.created_at DESC
             LIMIT ?"
        );
        $stmt->execute([$produtoId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }
}
