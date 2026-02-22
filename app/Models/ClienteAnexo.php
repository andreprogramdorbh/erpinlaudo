<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class ClienteAnexo extends Model
{
    protected string $table = 'clientes_anexos';

    public function __construct()
    {
        parent::__construct();
        $this->ensureTableExists();
    }

    private function ensureTableExists(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS `{$this->table}` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `cliente_id` INT NOT NULL,
            `usuario_id` INT NOT NULL,
            `file_path` VARCHAR(255) NOT NULL,
            `original_name` VARCHAR(255) NOT NULL,
            `file_size` INT NOT NULL,
            `mime_type` VARCHAR(100) NOT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX (`cliente_id`),
            INDEX (`usuario_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $this->pdo->exec($sql);
    }

    public function findById(int $id): object|false
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function findByClienteId(int $clienteId, int $usuarioId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE cliente_id = ? AND usuario_id = ? ORDER BY created_at DESC");
        $stmt->execute([$clienteId, $usuarioId]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function create(array $data): int|false
    {
        $sql = "INSERT INTO {$this->table} (cliente_id, usuario_id, file_path, original_name, file_size, mime_type)
                VALUES (:cliente_id, :usuario_id, :file_path, :original_name, :file_size, :mime_type)";
        
        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute([
            ':cliente_id'   => $data['cliente_id'],
            ':usuario_id'   => $data['usuario_id'],
            ':file_path'    => $data['file_path'],
            ':original_name' => $data['original_name'],
            ':file_size'    => $data['file_size'],
            ':mime_type'    => $data['mime_type']
        ]);

        return $result ? (int) $this->pdo->lastInsertId() : false;
    }

    public function delete(int $id, int $usuarioId): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE id = ? AND usuario_id = ?");
        return $stmt->execute([$id, $usuarioId]);
    }
}
