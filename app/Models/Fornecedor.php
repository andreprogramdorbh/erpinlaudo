<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class Fornecedor extends Model
{
    protected string $table = 'fornecedores';

    public function findById(int $id): object|false
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function findByUsuarioId(int $usuarioId, array $filtros = []): array
    {
        $where = ["usuario_id = :usuario_id"];
        $params = [':usuario_id' => $usuarioId];

        $status = $filtros['status'] ?? 'ativo';
        if ($status !== '') {
            $where[] = 'status = :status';
            $params[':status'] = $status;
        }

        $q = trim($filtros['pesquisa'] ?? '');
        if ($q !== '') {
            $where[] = '(nome LIKE :q OR documento LIKE :q OR email LIKE :q)';
            $params[':q'] = '%' . $q . '%';
        }

        $sql = "SELECT * FROM {$this->table} WHERE " . implode(' AND ', $where) . " ORDER BY nome ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function create(array $data): string|false
    {
        $sql = "INSERT INTO {$this->table} (usuario_id, nome, documento, email, telefone, status)
                VALUES (:usuario_id, :nome, :documento, :email, :telefone, :status)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':usuario_id', $data['usuario_id']);
        $stmt->bindValue(':nome', trim($data['nome']));
        $stmt->bindValue(':documento', $data['documento'] ?? null);
        $stmt->bindValue(':email', $data['email'] ?? null);
        $stmt->bindValue(':telefone', $data['telefone'] ?? null);
        $stmt->bindValue(':status', $data['status'] ?? 'ativo');

        if ($stmt->execute()) {
            return $this->pdo->lastInsertId();
        }

        return false;
    }

    public function update(int $id, array $data): bool
    {
        $allowedFields = [
            'nome',
            'documento',
            'email',
            'telefone',
            'status',
        ];

        $updateFields = [];
        $params = [':id' => $id];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $updateFields[] = "{$field} = :{$field}";
                $params[":{$field}"] = $data[$field];
            }
        }

        if (empty($updateFields)) {
            return false;
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $updateFields) . " WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("UPDATE {$this->table} SET status = 'inativo' WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
