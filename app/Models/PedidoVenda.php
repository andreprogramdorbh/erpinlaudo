<?php
namespace App\Models;

use App\Core\Model;
use PDO;

class PedidoVenda extends Model
{
    protected string $table = 'est_pedidos_venda';

    private function log(string $level, string $msg, array $ctx = []): void
    {
        $dir  = defined('BASE_PATH') ? BASE_PATH . '/storage/logs' : sys_get_temp_dir();
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        $file = $dir . '/estoque_' . date('Y-m') . '.log';
        $line = '[' . date('Y-m-d H:i:s') . '] [' . strtoupper($level) . '] [PedidoVenda] ' . $msg;
        if (!empty($ctx)) {
            $line .= ' | ctx=' . json_encode($ctx, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        @file_put_contents($file, $line . PHP_EOL, FILE_APPEND | LOCK_EX);
        error_log($line);
    }

    private function toFloat(mixed $v): float
    {
        if ($v === null || $v === '') return 0.0;
        return (float) str_replace(['.', ','], ['', '.'], (string)$v);
    }

    // ─── Gerar número sequencial ─────────────────────────────────────────────
    public function gerarNumero(int $usuarioId): string
    {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO est_pedido_seq (usuario_id, tipo, ultimo_seq) VALUES (?, 'venda', 1)
                 ON DUPLICATE KEY UPDATE ultimo_seq = ultimo_seq + 1"
            );
            $stmt->execute([$usuarioId]);
            $stmt = $this->pdo->prepare(
                "SELECT ultimo_seq FROM est_pedido_seq WHERE usuario_id = ? AND tipo = 'venda'"
            );
            $stmt->execute([$usuarioId]);
            $seq = (int)$stmt->fetchColumn();
            $this->pdo->commit();
            return 'PV-' . date('Y') . '-' . str_pad($seq, 5, '0', STR_PAD_LEFT);
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            $this->log('error', '[gerarNumero] ' . $e->getMessage());
            return 'PV-' . date('YmdHis');
        }
    }

    // ─── Criar pedido de venda ───────────────────────────────────────────────
    public function create(array $d, array $itens): int|false
    {
        $this->log('info', '[create] Iniciando', ['usuario_id' => $d['usuario_id'] ?? null]);
        try {
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare(
                "INSERT INTO {$this->table}
                    (usuario_id, numero, proposta_id, oportunidade_id,
                     cliente_id, cliente_nome, cliente_cpf_cnpj, cliente_email,
                     cliente_telefone, cliente_endereco,
                     status, data_pedido, data_previsao_entrega,
                     valor_produtos, valor_frete, valor_desconto, valor_total,
                     valor_custo_total, margem_total,
                     condicao_pagamento, forma_pagamento,
                     comissao_percentual, comissao_valor, colaborador_id,
                     tipo_frete, transportadora, endereco_entrega,
                     observacoes, observacoes_internas)
                 VALUES
                    (:usuario_id, :numero, :proposta_id, :oportunidade_id,
                     :cliente_id, :cliente_nome, :cliente_cpf_cnpj, :cliente_email,
                     :cliente_telefone, :cliente_endereco,
                     :status, :data_pedido, :data_previsao_entrega,
                     :valor_produtos, :valor_frete, :valor_desconto, :valor_total,
                     :valor_custo_total, :margem_total,
                     :condicao_pagamento, :forma_pagamento,
                     :comissao_percentual, :comissao_valor, :colaborador_id,
                     :tipo_frete, :transportadora, :endereco_entrega,
                     :observacoes, :observacoes_internas)"
            );
            $stmt->execute([
                ':usuario_id'           => $d['usuario_id'],
                ':numero'               => $d['numero'],
                ':proposta_id'          => !empty($d['proposta_id']) ? (int)$d['proposta_id'] : null,
                ':oportunidade_id'      => !empty($d['oportunidade_id']) ? (int)$d['oportunidade_id'] : null,
                ':cliente_id'           => !empty($d['cliente_id']) ? (int)$d['cliente_id'] : null,
                ':cliente_nome'         => $d['cliente_nome'] ?? '',
                ':cliente_cpf_cnpj'     => $d['cliente_cpf_cnpj'] ?? null,
                ':cliente_email'        => $d['cliente_email'] ?? null,
                ':cliente_telefone'     => $d['cliente_telefone'] ?? null,
                ':cliente_endereco'     => $d['cliente_endereco'] ?? null,
                ':status'               => $d['status'] ?? 'rascunho',
                ':data_pedido'          => $d['data_pedido'] ?? date('Y-m-d'),
                ':data_previsao_entrega'=> !empty($d['data_previsao_entrega']) ? $d['data_previsao_entrega'] : null,
                ':valor_produtos'       => $this->toFloat($d['valor_produtos'] ?? 0),
                ':valor_frete'          => $this->toFloat($d['valor_frete'] ?? 0),
                ':valor_desconto'       => $this->toFloat($d['valor_desconto'] ?? 0),
                ':valor_total'          => $this->toFloat($d['valor_total'] ?? 0),
                ':valor_custo_total'    => $this->toFloat($d['valor_custo_total'] ?? 0),
                ':margem_total'         => $this->toFloat($d['margem_total'] ?? 0),
                ':condicao_pagamento'   => $d['condicao_pagamento'] ?? null,
                ':forma_pagamento'      => $d['forma_pagamento'] ?? null,
                ':comissao_percentual'  => $this->toFloat($d['comissao_percentual'] ?? 0),
                ':comissao_valor'       => $this->toFloat($d['comissao_valor'] ?? 0),
                ':colaborador_id'       => !empty($d['colaborador_id']) ? (int)$d['colaborador_id'] : null,
                ':tipo_frete'           => $d['tipo_frete'] ?? 'cif',
                ':transportadora'       => $d['transportadora'] ?? null,
                ':endereco_entrega'     => $d['endereco_entrega'] ?? null,
                ':observacoes'          => $d['observacoes'] ?? null,
                ':observacoes_internas' => $d['observacoes_internas'] ?? null,
            ]);
            $pedidoId = (int)$this->pdo->lastInsertId();

            foreach ($itens as $item) {
                $this->_insertItem($pedidoId, $item);
            }

            $this->pdo->commit();
            $this->log('info', '[create] Pedido de venda criado', ['id' => $pedidoId]);
            return $pedidoId;
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            $this->log('error', '[create] Erro: ' . $e->getMessage(), [
                'trace' => substr($e->getTraceAsString(), 0, 500),
            ]);
            return false;
        }
    }

    // ─── Atualizar ───────────────────────────────────────────────────────────
    public function update(int $id, array $d, array $itens): bool
    {
        $this->log('info', '[update] Iniciando', ['id' => $id]);
        try {
            $this->pdo->beginTransaction();
            $stmt = $this->pdo->prepare(
                "UPDATE {$this->table} SET
                    cliente_id=:cliente_id, cliente_nome=:cliente_nome,
                    cliente_cpf_cnpj=:cliente_cpf_cnpj, cliente_email=:cliente_email,
                    cliente_telefone=:cliente_telefone, cliente_endereco=:cliente_endereco,
                    status=:status, data_pedido=:data_pedido,
                    data_previsao_entrega=:data_previsao_entrega,
                    valor_produtos=:valor_produtos, valor_frete=:valor_frete,
                    valor_desconto=:valor_desconto, valor_total=:valor_total,
                    valor_custo_total=:valor_custo_total, margem_total=:margem_total,
                    condicao_pagamento=:condicao_pagamento, forma_pagamento=:forma_pagamento,
                    comissao_percentual=:comissao_percentual, comissao_valor=:comissao_valor,
                    colaborador_id=:colaborador_id, tipo_frete=:tipo_frete,
                    transportadora=:transportadora, endereco_entrega=:endereco_entrega,
                    observacoes=:observacoes, observacoes_internas=:observacoes_internas
                 WHERE id = :id AND usuario_id = :usuario_id"
            );
            $ok = $stmt->execute([
                ':cliente_id'           => !empty($d['cliente_id']) ? (int)$d['cliente_id'] : null,
                ':cliente_nome'         => $d['cliente_nome'] ?? '',
                ':cliente_cpf_cnpj'     => $d['cliente_cpf_cnpj'] ?? null,
                ':cliente_email'        => $d['cliente_email'] ?? null,
                ':cliente_telefone'     => $d['cliente_telefone'] ?? null,
                ':cliente_endereco'     => $d['cliente_endereco'] ?? null,
                ':status'               => $d['status'] ?? 'rascunho',
                ':data_pedido'          => $d['data_pedido'] ?? date('Y-m-d'),
                ':data_previsao_entrega'=> !empty($d['data_previsao_entrega']) ? $d['data_previsao_entrega'] : null,
                ':valor_produtos'       => $this->toFloat($d['valor_produtos'] ?? 0),
                ':valor_frete'          => $this->toFloat($d['valor_frete'] ?? 0),
                ':valor_desconto'       => $this->toFloat($d['valor_desconto'] ?? 0),
                ':valor_total'          => $this->toFloat($d['valor_total'] ?? 0),
                ':valor_custo_total'    => $this->toFloat($d['valor_custo_total'] ?? 0),
                ':margem_total'         => $this->toFloat($d['margem_total'] ?? 0),
                ':condicao_pagamento'   => $d['condicao_pagamento'] ?? null,
                ':forma_pagamento'      => $d['forma_pagamento'] ?? null,
                ':comissao_percentual'  => $this->toFloat($d['comissao_percentual'] ?? 0),
                ':comissao_valor'       => $this->toFloat($d['comissao_valor'] ?? 0),
                ':colaborador_id'       => !empty($d['colaborador_id']) ? (int)$d['colaborador_id'] : null,
                ':tipo_frete'           => $d['tipo_frete'] ?? 'cif',
                ':transportadora'       => $d['transportadora'] ?? null,
                ':endereco_entrega'     => $d['endereco_entrega'] ?? null,
                ':observacoes'          => $d['observacoes'] ?? null,
                ':observacoes_internas' => $d['observacoes_internas'] ?? null,
                ':id'                   => $id,
                ':usuario_id'           => $d['usuario_id'],
            ]);
            $this->pdo->prepare("DELETE FROM est_pedidos_venda_itens WHERE pedido_id = ?")->execute([$id]);
            foreach ($itens as $item) {
                $this->_insertItem($id, $item);
            }
            $this->pdo->commit();
            $this->log('info', '[update] Pedido atualizado', ['id' => $id]);
            return $ok;
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            $this->log('error', '[update] Erro: ' . $e->getMessage(), ['id' => $id]);
            return false;
        }
    }

    private function _insertItem(int $pedidoId, array $item): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO est_pedidos_venda_itens
                (pedido_id, produto_id, codigo_produto, descricao, unidade,
                 quantidade, preco_unitario, preco_custo, desconto_perc,
                 valor_total, margem_item, lote, observacoes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $qty    = (float)($item['quantidade'] ?? 0);
        $preco  = $this->toFloat($item['preco_unitario'] ?? 0);
        $custo  = $this->toFloat($item['preco_custo'] ?? 0);
        $desc   = (float)($item['desconto_perc'] ?? 0);
        $total  = $qty * $preco * (1 - $desc / 100);
        $margem = $qty > 0 ? ($total - ($qty * $custo)) : 0;
        $stmt->execute([
            $pedidoId,
            !empty($item['produto_id']) ? (int)$item['produto_id'] : null,
            $item['codigo_produto'] ?? null,
            $item['descricao'] ?? '',
            $item['unidade'] ?? 'UN',
            $qty,
            $preco,
            $custo,
            $desc,
            $total,
            $margem,
            $item['lote'] ?? null,
            $item['observacoes'] ?? null,
        ]);
    }

    // ─── Listagem ────────────────────────────────────────────────────────────
    public function findByUsuarioId(int $usuarioId, array $filtros = []): array
    {
        $where  = ["p.usuario_id = :uid"];
        $params = [':uid' => $usuarioId];
        if (!empty($filtros['status'])) {
            $where[] = "p.status = :status";
            $params[':status'] = $filtros['status'];
        }
        if (!empty($filtros['q'])) {
            $where[] = "(p.numero LIKE :q OR p.cliente_nome LIKE :q)";
            $params[':q'] = '%' . $filtros['q'] . '%';
        }
        if (!empty($filtros['data_inicio'])) {
            $where[] = "p.data_pedido >= :di";
            $params[':di'] = $filtros['data_inicio'];
        }
        if (!empty($filtros['data_fim'])) {
            $where[] = "p.data_pedido <= :df";
            $params[':df'] = $filtros['data_fim'];
        }
        $stmt = $this->pdo->prepare(
            "SELECT p.*,
                    (SELECT COUNT(*) FROM est_pedidos_venda_itens i WHERE i.pedido_id = p.id) AS total_itens
             FROM {$this->table} p
             WHERE " . implode(' AND ', $where) . "
             ORDER BY p.created_at DESC"
        );
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    // ─── Busca por ID com itens ──────────────────────────────────────────────
    public function findById(int $id): object|false
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        $pedido = $stmt->fetch(PDO::FETCH_OBJ);
        if ($pedido) {
            $stmt2 = $this->pdo->prepare(
                "SELECT i.*, pr.nome AS produto_nome, pr.imagem_principal
                 FROM est_pedidos_venda_itens i
                 LEFT JOIN produtos pr ON pr.id = i.produto_id
                 WHERE i.pedido_id = ?"
            );
            $stmt2->execute([$id]);
            $pedido->itens = $stmt2->fetchAll(PDO::FETCH_OBJ);
        }
        return $pedido;
    }

    // ─── KPIs ────────────────────────────────────────────────────────────────
    public function kpis(int $usuarioId): object
    {
        $stmt = $this->pdo->prepare(
            "SELECT
               COUNT(*)                                AS total,
               SUM(status = 'rascunho')                AS rascunho,
               SUM(status = 'confirmado')              AS confirmado,
               SUM(status = 'entregue')                AS entregue,
               SUM(status = 'faturado')                AS faturado,
               SUM(status = 'cancelado')               AS cancelado,
               SUM(CASE WHEN status != 'cancelado' THEN valor_total ELSE 0 END) AS valor_total_geral,
               SUM(CASE WHEN status != 'cancelado' THEN margem_total ELSE 0 END) AS margem_total_geral,
               SUM(CASE WHEN status != 'cancelado' AND MONTH(data_pedido) = MONTH(NOW()) AND YEAR(data_pedido) = YEAR(NOW()) THEN valor_total ELSE 0 END) AS valor_mes
             FROM {$this->table}
             WHERE usuario_id = ?"
        );
        $stmt->execute([$usuarioId]);
        return $stmt->fetch(PDO::FETCH_OBJ) ?: (object)[];
    }

    // ─── Excluir ─────────────────────────────────────────────────────────────
    public function delete(int $id, int $usuarioId): bool
    {
        try {
            $this->pdo->beginTransaction();
            $this->pdo->prepare("DELETE FROM est_pedidos_venda_itens WHERE pedido_id = ?")->execute([$id]);
            $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE id = ? AND usuario_id = ?");
            $ok = $stmt->execute([$id, $usuarioId]);
            $this->pdo->commit();
            $this->log('info', '[delete] Pedido excluído', ['id' => $id]);
            return $ok;
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            $this->log('error', '[delete] Erro: ' . $e->getMessage(), ['id' => $id]);
            return false;
        }
    }
    /**
     * Retorna pedidos de venda de um cliente específico (para o portal).
     */
    public function findByClienteIdAndTenantId(int $clienteId, int $tenantId, array $filtros = []): array
    {
        $where  = ['p.usuario_id = :uid', 'p.cliente_id = :cid'];
        $params = [':uid' => $tenantId, ':cid' => $clienteId];
        if (!empty($filtros['status'])) {
            $where[]           = 'p.status = :status';
            $params[':status'] = $filtros['status'];
        }
        $sql = "SELECT p.*, COUNT(i.id) AS total_itens
                FROM {$this->table} p
                LEFT JOIN pedidos_venda_itens i ON i.pedido_id = p.id
                WHERE " . implode(' AND ', $where) . "
                GROUP BY p.id
                ORDER BY p.created_at DESC";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(\PDO::FETCH_OBJ);
        } catch (\Throwable $e) {
            error_log('[PedidoVenda] findByClienteIdAndTenantId: ' . $e->getMessage());
            return [];
        }
    }


}