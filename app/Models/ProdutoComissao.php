<?php
namespace App\Models;

use App\Core\Model;
use PDO;

class ProdutoComissao extends Model
{
    protected string $table = 'produto_comissoes';

    public function findByProdutoId(int $produtoId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT pc.*,
                    c.nome AS colaborador_nome
             FROM {$this->table} pc
             LEFT JOIN colaboradores c ON c.id = pc.colaborador_id
             WHERE pc.produto_id = ?
             ORDER BY pc.ativo DESC, pc.descricao ASC"
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
                    (produto_id, usuario_id, colaborador_id, descricao, tipo, valor,
                     meta_minima, meta_maxima, escalonado, vigencia_inicio, vigencia_fim, ativo, observacoes)
                 VALUES
                    (:produto_id, :usuario_id, :colaborador_id, :descricao, :tipo, :valor,
                     :meta_minima, :meta_maxima, :escalonado, :vigencia_inicio, :vigencia_fim, :ativo, :observacoes)"
            );
            $stmt->execute([
                ':produto_id'      => (int) $d['produto_id'],
                ':usuario_id'      => (int) $d['usuario_id'],
                ':colaborador_id'  => !empty($d['colaborador_id']) ? (int)$d['colaborador_id'] : null,
                ':descricao'       => $d['descricao'] ?? '',
                ':tipo'            => $d['tipo'] ?? 'percentual_venda',
                ':valor'           => (float) str_replace(['.', ','], ['', '.'], $d['valor'] ?? '0'),
                ':meta_minima'     => !empty($d['meta_minima']) ? (float) str_replace(['.', ','], ['', '.'], $d['meta_minima']) : null,
                ':meta_maxima'     => !empty($d['meta_maxima']) ? (float) str_replace(['.', ','], ['', '.'], $d['meta_maxima']) : null,
                ':escalonado'      => (int)(bool)($d['escalonado'] ?? 0),
                ':vigencia_inicio' => !empty($d['vigencia_inicio']) ? $d['vigencia_inicio'] : null,
                ':vigencia_fim'    => !empty($d['vigencia_fim']) ? $d['vigencia_fim'] : null,
                ':ativo'           => (int)(bool)($d['ativo'] ?? 1),
                ':observacoes'     => $d['observacoes'] ?? null,
            ]);
            return (int) $this->pdo->lastInsertId();
        } catch (\PDOException $e) {
            error_log("[ProdutoComissao::create] ERRO: " . $e->getMessage());
            return false;
        }
    }

    public function update(int $id, array $d): bool
    {
        try {
            $stmt = $this->pdo->prepare(
                "UPDATE {$this->table} SET
                    colaborador_id=:colaborador_id, descricao=:descricao, tipo=:tipo, valor=:valor,
                    meta_minima=:meta_minima, meta_maxima=:meta_maxima, escalonado=:escalonado,
                    vigencia_inicio=:vigencia_inicio, vigencia_fim=:vigencia_fim, ativo=:ativo, observacoes=:observacoes
                 WHERE id = :id AND usuario_id = :usuario_id"
            );
            return $stmt->execute([
                ':colaborador_id'  => !empty($d['colaborador_id']) ? (int)$d['colaborador_id'] : null,
                ':descricao'       => $d['descricao'] ?? '',
                ':tipo'            => $d['tipo'] ?? 'percentual_venda',
                ':valor'           => (float) str_replace(['.', ','], ['', '.'], $d['valor'] ?? '0'),
                ':meta_minima'     => !empty($d['meta_minima']) ? (float) str_replace(['.', ','], ['', '.'], $d['meta_minima']) : null,
                ':meta_maxima'     => !empty($d['meta_maxima']) ? (float) str_replace(['.', ','], ['', '.'], $d['meta_maxima']) : null,
                ':escalonado'      => (int)(bool)($d['escalonado'] ?? 0),
                ':vigencia_inicio' => !empty($d['vigencia_inicio']) ? $d['vigencia_inicio'] : null,
                ':vigencia_fim'    => !empty($d['vigencia_fim']) ? $d['vigencia_fim'] : null,
                ':ativo'           => (int)(bool)($d['ativo'] ?? 1),
                ':observacoes'     => $d['observacoes'] ?? null,
                ':id'              => $id,
                ':usuario_id'      => (int) $d['usuario_id'],
            ]);
        } catch (\PDOException $e) {
            error_log("[ProdutoComissao::update] ERRO: " . $e->getMessage());
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
            error_log("[ProdutoComissao::delete] ERRO: " . $e->getMessage());
            return false;
        }
    }
}
