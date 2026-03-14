<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class Especialidade extends Model
{
    protected string $table = 'especialidades';

    public function findById(int $id): object|false
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function findByUsuarioId(int $usuarioId, array $filtros = []): array
    {
        $where = ['usuario_id = :usuario_id'];
        $params = [':usuario_id' => $usuarioId];

        $q = trim((string) ($filtros['pesquisa'] ?? ''));
        if ($q !== '') {
            $where[] = '(especialidade LIKE :q OR subespecialidade LIKE :q OR rqe LIKE :q)';
            $params[':q'] = '%' . $q . '%';
        }

        $sql = "SELECT *
                FROM {$this->table}
                WHERE " . implode(' AND ', $where) . "
                ORDER BY especialidade ASC, subespecialidade ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function listForSelect(int $usuarioId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, especialidade, subespecialidade
             FROM {$this->table}
             WHERE usuario_id = :usuario_id
             ORDER BY especialidade ASC, subespecialidade ASC"
        );
        $stmt->execute([':usuario_id' => $usuarioId]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function create(array $data): string|false
    {
        $sql = "INSERT INTO {$this->table}
                (usuario_id, especialidade, subespecialidade, rqe)
                VALUES (:usuario_id, :especialidade, :subespecialidade, :rqe)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':usuario_id', $data['usuario_id'], PDO::PARAM_INT);
        $stmt->bindValue(':especialidade', $data['especialidade']);
        $stmt->bindValue(':subespecialidade', $data['subespecialidade'] ?? null);
        $stmt->bindValue(':rqe', $data['rqe'] ?? null);

        if ($stmt->execute()) {
            return $this->pdo->lastInsertId();
        }

        return false;
    }
}
