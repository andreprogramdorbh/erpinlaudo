<?php
namespace App\Models;

use App\Core\Model;
use PDO;

class ColaboradorComissao extends Model
{
    protected string $table = 'colaboradores_comissoes';

    public function findByColaboradorId(int $colaboradorId, int $usuarioId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM `{$this->table}`
              WHERE colaborador_id = ? AND usuario_id = ?
              ORDER BY vigencia_inicio DESC, id DESC"
        );
        $stmt->execute([$colaboradorId, $usuarioId]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function findById(int $id): object|false
    {
        $stmt = $this->pdo->prepare("SELECT * FROM `{$this->table}` WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function create(array $data): int|false
    {
        $sql = "INSERT INTO `{$this->table}`
                    (colaborador_id, usuario_id, descricao, tipo, valor, base_calculo,
                     vigencia_inicio, vigencia_fim, ativo, observacoes)
                VALUES
                    (:colaborador_id, :usuario_id, :descricao, :tipo, :valor, :base_calculo,
                     :vigencia_inicio, :vigencia_fim, :ativo, :observacoes)";
        $stmt = $this->pdo->prepare($sql);
        try {
            $result = $stmt->execute([
                ':colaborador_id'  => $data['colaborador_id'],
                ':usuario_id'      => $data['usuario_id'],
                ':descricao'       => $data['descricao'],
                ':tipo'            => $data['tipo'],
                ':valor'           => $data['valor'],
                ':base_calculo'    => $data['base_calculo'],
                ':vigencia_inicio' => $data['vigencia_inicio'] ?: null,
                ':vigencia_fim'    => $data['vigencia_fim'] ?: null,
                ':ativo'           => (int)($data['ativo'] ?? 1),
                ':observacoes'     => $data['observacoes'] ?? null,
            ]);
            return $result ? (int) $this->pdo->lastInsertId() : false;
        } catch (\PDOException $e) {
            error_log("[ColaboradorComissao::create] ERRO: " . $e->getMessage());
            return false;
        }
    }

    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE `{$this->table}` SET
                    descricao = :descricao,
                    tipo = :tipo,
                    valor = :valor,
                    base_calculo = :base_calculo,
                    vigencia_inicio = :vigencia_inicio,
                    vigencia_fim = :vigencia_fim,
                    ativo = :ativo,
                    observacoes = :observacoes,
                    updated_at = NOW()
                WHERE id = :id AND usuario_id = :usuario_id";
        $stmt = $this->pdo->prepare($sql);
        try {
            return $stmt->execute([
                ':descricao'       => $data['descricao'],
                ':tipo'            => $data['tipo'],
                ':valor'           => $data['valor'],
                ':base_calculo'    => $data['base_calculo'],
                ':vigencia_inicio' => $data['vigencia_inicio'] ?: null,
                ':vigencia_fim'    => $data['vigencia_fim'] ?: null,
                ':ativo'           => (int)($data['ativo'] ?? 1),
                ':observacoes'     => $data['observacoes'] ?? null,
                ':id'              => $id,
                ':usuario_id'      => $data['usuario_id'],
            ]);
        } catch (\PDOException $e) {
            error_log("[ColaboradorComissao::update] ERRO id={$id}: " . $e->getMessage());
            return false;
        }
    }

    public function delete(int $id, int $usuarioId): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM `{$this->table}` WHERE id = ? AND usuario_id = ?");
        return $stmt->execute([$id, $usuarioId]);
    }
}
