<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class ContaReceberAnexo extends Model
{
    protected string $table = 'contas_receber_anexos';

    public function findById(int $id): object|false
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function findByContaId(int $contaReceberId, int $usuarioId): array
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE conta_receber_id = ? AND usuario_id = ? ORDER BY id DESC");
            $stmt->execute([$contaReceberId, $usuarioId]);
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (\PDOException $e) {
            // Se a tabela não existir, tenta criar e retorna vazio
            if ($e->getCode() == '42S02' || strpos($e->getMessage(), "doesn't exist") !== false) {
                $this->createTable();
                return [];
            }
            throw $e;
        }
    }

    public function create(array $data): string|false
    {
        $sql = "INSERT INTO {$this->table} (usuario_id, conta_receber_id, file_path, original_name, mime_type, file_size)
                VALUES (:usuario_id, :conta_receber_id, :file_path, :original_name, :mime_type, :file_size)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':usuario_id', $data['usuario_id']);
        $stmt->bindValue(':conta_receber_id', $data['conta_receber_id']);
        $stmt->bindValue(':file_path', $data['file_path']);
        $stmt->bindValue(':original_name', $data['original_name']);
        $stmt->bindValue(':mime_type', $data['mime_type'] ?? null);
        $stmt->bindValue(':file_size', $data['file_size'] ?? null);

        if ($stmt->execute()) {
            return $this->pdo->lastInsertId();
        }

        return false;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function createTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            usuario_id INT NOT NULL,
            conta_receber_id INT NOT NULL,
            file_path VARCHAR(255) NOT NULL,
            original_name VARCHAR(255) NOT NULL,
            mime_type VARCHAR(100),
            file_size INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        $this->pdo->exec($sql);
    }
}
