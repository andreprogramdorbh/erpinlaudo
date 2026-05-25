<?php
namespace App\Models;

use App\Core\Model;
use PDO;

class PedidoCompra extends Model
{
    protected string $table = 'est_pedidos_compra';

    private function log(string $level, string $msg, array $ctx = []): void
    {
        $dir  = defined('BASE_PATH') ? BASE_PATH . '/storage/logs' : sys_get_temp_dir();
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        $file = $dir . '/estoque_' . date('Y-m') . '.log';
        $line = '[' . date('Y-m-d H:i:s') . '] [' . strtoupper($level) . '] [PedidoCompra] ' . $msg;
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
                "INSERT INTO est_pedido_seq (usuario_id, tipo, ultimo_seq) VALUES (?, 'compra', 1)
                 ON DUPLICATE KEY UPDATE ultimo_seq = ultimo_seq + 1"
            );
            $stmt->execute([$usuarioId]);
            $stmt = $this->pdo->prepare(
                "SELECT ultimo_seq FROM est_pedido_seq WHERE usuario_id = ? AND tipo = 'compra'"
            );
            $stmt->execute([$usuarioId]);
            $seq = (int)$stmt->fetchColumn();
            $this->pdo->commit();
            return 'PC-' . date('Y') . '-' . str_pad($seq, 5, '0', STR_PAD_LEFT);
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            $this->log('error', '[gerarNumero] ' . $e->getMessage());
            return 'PC-' . date('YmdHis');
        }
    }

    // ─── Criar pedido ────────────────────────────────────────────────────────
    public function create(array $d, array $itens): int|false
    {
        $this->log('info', '[create] Iniciando', ['usuario_id' => $d['usuario_id'] ?? null]);
        try {
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare(
                "INSERT INTO {$this->table}
                    (usuario_id, numero, fornecedor_id, fornecedor_nome, fornecedor_cnpj,
                     fornecedor_email, fornecedor_telefone,
                     status, data_pedido, data_previsao,
                     valor_produtos, valor_frete, valor_desconto, valor_total,
                     nfe_chave, nfe_numero, nfe_xml_path,
                     condicao_pagamento, observacoes, observacoes_internas)
                 VALUES
                    (:usuario_id, :numero, :fornecedor_id, :fornecedor_nome, :fornecedor_cnpj,
                     :fornecedor_email, :fornecedor_telefone,
                     :status, :data_pedido, :data_previsao,
                     :valor_produtos, :valor_frete, :valor_desconto, :valor_total,
                     :nfe_chave, :nfe_numero, :nfe_xml_path,
                     :condicao_pagamento, :observacoes, :observacoes_internas)"
            );
            $stmt->execute([
                ':usuario_id'          => $d['usuario_id'],
                ':numero'              => $d['numero'],
                ':fornecedor_id'       => !empty($d['fornecedor_id']) ? (int)$d['fornecedor_id'] : null,
                ':fornecedor_nome'     => $d['fornecedor_nome'] ?? '',
                ':fornecedor_cnpj'     => $d['fornecedor_cnpj'] ?? null,
                ':fornecedor_email'    => $d['fornecedor_email'] ?? null,
                ':fornecedor_telefone' => $d['fornecedor_telefone'] ?? null,
                ':status'              => $d['status'] ?? 'rascunho',
                ':data_pedido'         => $d['data_pedido'] ?? date('Y-m-d'),
                ':data_previsao'       => !empty($d['data_previsao']) ? $d['data_previsao'] : null,
                ':valor_produtos'      => $this->toFloat($d['valor_produtos'] ?? 0),
                ':valor_frete'         => $this->toFloat($d['valor_frete'] ?? 0),
                ':valor_desconto'      => $this->toFloat($d['valor_desconto'] ?? 0),
                ':valor_total'         => $this->toFloat($d['valor_total'] ?? 0),
                ':nfe_chave'           => $d['nfe_chave'] ?? null,
                ':nfe_numero'          => $d['nfe_numero'] ?? null,
                ':nfe_xml_path'        => $d['nfe_xml_path'] ?? null,
                ':condicao_pagamento'  => $d['condicao_pagamento'] ?? null,
                ':observacoes'         => $d['observacoes'] ?? null,
                ':observacoes_internas'=> $d['observacoes_internas'] ?? null,
            ]);
            $pedidoId = (int)$this->pdo->lastInsertId();

            // Insere itens
            foreach ($itens as $item) {
                $this->_insertItem($pedidoId, $item);
            }

            $this->pdo->commit();
            $this->log('info', '[create] Pedido criado', ['id' => $pedidoId, 'itens' => count($itens)]);
            return $pedidoId;
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            $this->log('error', '[create] Erro: ' . $e->getMessage(), [
                'trace' => substr($e->getTraceAsString(), 0, 500),
            ]);
            return false;
        }
    }

    // ─── Atualizar pedido ────────────────────────────────────────────────────
    public function update(int $id, array $d, array $itens): bool
    {
        $this->log('info', '[update] Iniciando', ['id' => $id]);
        try {
            $this->pdo->beginTransaction();
            $stmt = $this->pdo->prepare(
                "UPDATE {$this->table} SET
                    fornecedor_id=:fornecedor_id, fornecedor_nome=:fornecedor_nome,
                    fornecedor_cnpj=:fornecedor_cnpj, fornecedor_email=:fornecedor_email,
                    fornecedor_telefone=:fornecedor_telefone,
                    status=:status, data_pedido=:data_pedido, data_previsao=:data_previsao,
                    valor_produtos=:valor_produtos, valor_frete=:valor_frete,
                    valor_desconto=:valor_desconto, valor_total=:valor_total,
                    condicao_pagamento=:condicao_pagamento,
                    observacoes=:observacoes, observacoes_internas=:observacoes_internas
                 WHERE id = :id AND usuario_id = :usuario_id"
            );
            $ok = $stmt->execute([
                ':fornecedor_id'       => !empty($d['fornecedor_id']) ? (int)$d['fornecedor_id'] : null,
                ':fornecedor_nome'     => $d['fornecedor_nome'] ?? '',
                ':fornecedor_cnpj'     => $d['fornecedor_cnpj'] ?? null,
                ':fornecedor_email'    => $d['fornecedor_email'] ?? null,
                ':fornecedor_telefone' => $d['fornecedor_telefone'] ?? null,
                ':status'              => $d['status'] ?? 'rascunho',
                ':data_pedido'         => $d['data_pedido'] ?? date('Y-m-d'),
                ':data_previsao'       => !empty($d['data_previsao']) ? $d['data_previsao'] : null,
                ':valor_produtos'      => $this->toFloat($d['valor_produtos'] ?? 0),
                ':valor_frete'         => $this->toFloat($d['valor_frete'] ?? 0),
                ':valor_desconto'      => $this->toFloat($d['valor_desconto'] ?? 0),
                ':valor_total'         => $this->toFloat($d['valor_total'] ?? 0),
                ':condicao_pagamento'  => $d['condicao_pagamento'] ?? null,
                ':observacoes'         => $d['observacoes'] ?? null,
                ':observacoes_internas'=> $d['observacoes_internas'] ?? null,
                ':id'                  => $id,
                ':usuario_id'          => $d['usuario_id'],
            ]);
            // Recria itens
            $this->pdo->prepare("DELETE FROM est_pedidos_compra_itens WHERE pedido_id = ?")->execute([$id]);
            foreach ($itens as $item) {
                $this->_insertItem($id, $item);
            }
            $this->pdo->commit();
            $this->log('info', '[update] Pedido atualizado', ['id' => $id, 'ok' => $ok]);
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
            "INSERT INTO est_pedidos_compra_itens
                (pedido_id, produto_id, codigo_produto, descricao, unidade,
                 quantidade, preco_unitario, desconto_perc, valor_total,
                 lote, data_validade, observacoes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $qty   = (float)($item['quantidade'] ?? 0);
        $preco = $this->toFloat($item['preco_unitario'] ?? 0);
        $desc  = (float)($item['desconto_perc'] ?? 0);
        $total = $qty * $preco * (1 - $desc / 100);
        $stmt->execute([
            $pedidoId,
            !empty($item['produto_id']) ? (int)$item['produto_id'] : null,
            $item['codigo_produto'] ?? null,
            $item['descricao'] ?? '',
            $item['unidade'] ?? 'UN',
            $qty,
            $preco,
            $desc,
            $total,
            $item['lote'] ?? null,
            !empty($item['data_validade']) ? $item['data_validade'] : null,
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
            $where[] = "(p.numero LIKE :q OR p.fornecedor_nome LIKE :q OR p.nfe_numero LIKE :q)";
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
                    (SELECT COUNT(*) FROM est_pedidos_compra_itens i WHERE i.pedido_id = p.id) AS total_itens
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
        $stmt = $this->pdo->prepare(
            "SELECT p.* FROM {$this->table} p WHERE p.id = ?"
        );
        $stmt->execute([$id]);
        $pedido = $stmt->fetch(PDO::FETCH_OBJ);
        if ($pedido) {
            $stmt2 = $this->pdo->prepare(
                "SELECT i.*, pr.nome AS produto_nome, pr.imagem_principal
                 FROM est_pedidos_compra_itens i
                 LEFT JOIN produtos pr ON pr.id = i.produto_id
                 WHERE i.pedido_id = ?"
            );
            $stmt2->execute([$id]);
            $pedido->itens = $stmt2->fetchAll(PDO::FETCH_OBJ);
        }
        return $pedido;
    }

    // ─── Importar XML NF-e ───────────────────────────────────────────────────
    public function parseNfeXml(string $xmlContent): array|false
    {
        $this->log('info', '[parseNfeXml] Iniciando parse do XML', ['tamanho' => strlen($xmlContent)]);
        try {
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($xmlContent);
            if ($xml === false) {
                $erros = array_map(fn($e) => $e->message, libxml_get_errors());
                $this->log('error', '[parseNfeXml] XML inválido', $erros);
                libxml_clear_errors();
                return false;
            }
            // Namespace NF-e
            $ns   = $xml->getNamespaces(true);
            $nfe  = isset($ns['']) ? $xml->children($ns['']) : $xml;
            $infNFe = $nfe->NFe->infNFe ?? $xml->NFe->infNFe ?? $xml->infNFe ?? null;
            if (!$infNFe) {
                $this->log('error', '[parseNfeXml] Estrutura infNFe não encontrada');
                return false;
            }
            $emit = $infNFe->emit;
            $ide  = $infNFe->ide;
            $total= $infNFe->total->ICMSTot ?? null;

            $dados = [
                'nfe_chave'           => (string)($infNFe->attributes()['Id'] ?? ''),
                'nfe_numero'          => (string)($ide->nNF ?? ''),
                'nfe_serie'           => (string)($ide->serie ?? ''),
                'nfe_data_emissao'    => substr((string)($ide->dhEmi ?? $ide->dEmi ?? ''), 0, 10),
                'fornecedor_cnpj'     => preg_replace('/\D/', '', (string)($emit->CNPJ ?? $emit->CPF ?? '')),
                'fornecedor_nome'     => (string)($emit->xFant ?? $emit->xNome ?? ''),
                'fornecedor_email'    => (string)($emit->email ?? ''),
                'valor_produtos'      => (float)($total->vProd ?? 0),
                'valor_frete'         => (float)($total->vFrete ?? 0),
                'valor_desconto'      => (float)($total->vDesc ?? 0),
                'valor_total'         => (float)($total->vNF ?? 0),
                'itens'               => [],
            ];

            foreach ($infNFe->det as $det) {
                $prod = $det->prod;
                $dados['itens'][] = [
                    'codigo_produto' => (string)($prod->cProd ?? ''),
                    'descricao'      => (string)($prod->xProd ?? ''),
                    'ncm'            => (string)($prod->NCM ?? ''),
                    'unidade'        => (string)($prod->uCom ?? 'UN'),
                    'quantidade'     => (float)($prod->qCom ?? 0),
                    'preco_unitario' => (float)($prod->vUnCom ?? 0),
                    'valor_total'    => (float)($prod->vProd ?? 0),
                    'lote'           => (string)($det->rastro->nLote ?? ''),
                    'data_validade'  => !empty((string)($det->rastro->dVal ?? ''))
                                        ? date('Y-m-d', strtotime((string)$det->rastro->dVal))
                                        : null,
                ];
            }
            $this->log('info', '[parseNfeXml] Parse concluído', [
                'nfe_numero' => $dados['nfe_numero'],
                'itens'      => count($dados['itens']),
            ]);
            return $dados;
        } catch (\Throwable $e) {
            $this->log('error', '[parseNfeXml] Exceção: ' . $e->getMessage());
            return false;
        }
    }

    // ─── KPIs ────────────────────────────────────────────────────────────────
    public function kpis(int $usuarioId): object
    {
        $stmt = $this->pdo->prepare(
            "SELECT
               COUNT(*)                                AS total,
               SUM(status = 'rascunho')                AS rascunho,
               SUM(status = 'confirmado')              AS confirmado,
               SUM(status = 'recebido')                AS recebido,
               SUM(status = 'cancelado')               AS cancelado,
               SUM(valor_total)                        AS valor_total_geral,
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
            $this->pdo->prepare("DELETE FROM est_pedidos_compra_itens WHERE pedido_id = ?")->execute([$id]);
            $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE id = ? AND usuario_id = ?");
            $ok = $stmt->execute([$id, $usuarioId]);
            $this->pdo->commit();
            $this->log('info', '[delete] Pedido excluído', ['id' => $id, 'ok' => $ok]);
            return $ok;
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            $this->log('error', '[delete] Erro: ' . $e->getMessage(), ['id' => $id]);
            return false;
        }
    }
}
