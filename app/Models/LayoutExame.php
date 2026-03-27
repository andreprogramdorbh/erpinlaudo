<?php
namespace App\Models;

use App\Core\Model;
use PDO;

class LayoutExame extends Model
{
    protected string $table = 'layout_exames';

    public function findByUsuarioId(int $usuarioId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM {$this->table} WHERE usuario_id = ? ORDER BY ativo DESC, nome ASC"
        );
        $stmt->execute([$usuarioId]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function findById(int $id): object|false
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function findAtivo(int $usuarioId): object|false
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM {$this->table} WHERE usuario_id = ? AND ativo = 1 ORDER BY id DESC LIMIT 1"
        );
        $stmt->execute([$usuarioId]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function create(array $data): string|false
    {
        $sql = "INSERT INTO {$this->table} (usuario_id, nome, descricao, formato, mapeamento_json, ativo)
                VALUES (:usuario_id, :nome, :descricao, :formato, :mapeamento_json, :ativo)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':usuario_id'      => $data['usuario_id'],
            ':nome'            => $data['nome'],
            ':descricao'       => $data['descricao'] ?? null,
            ':formato'         => $data['formato'] ?? 'xlsx',
            ':mapeamento_json' => $data['mapeamento_json'],
            ':ativo'           => $data['ativo'] ?? 1,
        ]);
        return $this->pdo->lastInsertId() ?: false;
    }

    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE {$this->table} SET
                nome = :nome, descricao = :descricao, formato = :formato,
                mapeamento_json = :mapeamento_json, ativo = :ativo, updated_at = NOW()
                WHERE id = :id AND usuario_id = :usuario_id";
        return $this->pdo->prepare($sql)->execute([
            ':nome'            => $data['nome'],
            ':descricao'       => $data['descricao'] ?? null,
            ':formato'         => $data['formato'] ?? 'xlsx',
            ':mapeamento_json' => $data['mapeamento_json'],
            ':ativo'           => $data['ativo'] ?? 1,
            ':id'              => $id,
            ':usuario_id'      => $data['usuario_id'],
        ]);
    }

    public function delete(int $id, int $usuarioId): bool
    {
        return $this->pdo->prepare(
            "DELETE FROM {$this->table} WHERE id = ? AND usuario_id = ?"
        )->execute([$id, $usuarioId]);
    }
}
