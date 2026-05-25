<?php
namespace App\Models;

use App\Core\Model;
use PDO;

class ProdutoComponente extends Model
{
    protected string $table = 'produto_componentes';

    public function findByProdutoId(int $produtoId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT pc.*,
                    p.codigo        AS comp_codigo,
                    p.nome          AS comp_nome,
                    p.tipo          AS comp_tipo,
                    p.categoria     AS comp_categoria,
                    p.unidade_medida AS comp_unidade,
                    p.preco_venda   AS comp_preco_venda,
                    p.imagem_principal AS comp_imagem,
                    p.status        AS comp_status
             FROM {$this->table} pc
             INNER JOIN produtos p ON p.id = pc.componente_id
             WHERE pc.produto_id = ?
             ORDER BY pc.ordem ASC, pc.id ASC"
        );
        $stmt->execute([$produtoId]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function findById(int $id): object|false
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function create(array $d): int|false
    {
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO {$this->table}
                    (produto_id, componente_id, usuario_id, quantidade, obrigatorio,
                     vendido_separado, preco_venda_proprio, desconto_composicao, ordem, observacoes)
                 VALUES
                    (:produto_id, :componente_id, :usuario_id, :quantidade, :obrigatorio,
                     :vendido_separado, :preco_venda_proprio, :desconto_composicao, :ordem, :observacoes)"
            );
            $stmt->execute([
                ':produto_id'          => (int) $d['produto_id'],
                ':componente_id'       => (int) $d['componente_id'],
                ':usuario_id'          => (int) $d['usuario_id'],
                ':quantidade'          => (float) ($d['quantidade'] ?? 1),
                ':obrigatorio'         => (int)(bool)($d['obrigatorio'] ?? 1),
                ':vendido_separado'    => (int)(bool)($d['vendido_separado'] ?? 1),
                ':preco_venda_proprio' => isset($d['preco_venda_proprio']) && $d['preco_venda_proprio'] !== ''
                                         ? (float) str_replace(['.', ','], ['', '.'], $d['preco_venda_proprio'])
                                         : null,
                ':desconto_composicao' => (float) ($d['desconto_composicao'] ?? 0),
                ':ordem'               => (int) ($d['ordem'] ?? 0),
                ':observacoes'         => $d['observacoes'] ?? null,
            ]);
            return (int) $this->pdo->lastInsertId();
        } catch (\PDOException $e) {
            error_log("[ProdutoComponente::create] ERRO: " . $e->getMessage());
            return false;
        }
    }

    public function delete(int $id, int $usuarioId): bool
    {
        try {
            $stmt = $this->pdo->prepare(
                "DELETE FROM {$this->table} WHERE id = ? AND usuario_id = ?"
            );
            return $stmt->execute([$id, $usuarioId]);
        } catch (\PDOException $e) {
            error_log("[ProdutoComponente::delete] ERRO: " . $e->getMessage());
            return false;
        }
    }

    public function reordenar(int $produtoId, array $ids): bool
    {
        try {
            foreach ($ids as $ordem => $id) {
                $stmt = $this->pdo->prepare(
                    "UPDATE {$this->table} SET ordem = ? WHERE id = ? AND produto_id = ?"
                );
                $stmt->execute([$ordem, (int)$id, $produtoId]);
            }
            return true;
        } catch (\PDOException $e) {
            error_log("[ProdutoComponente::reordenar] ERRO: " . $e->getMessage());
            return false;
        }
    }
}
