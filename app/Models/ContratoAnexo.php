<?php
namespace App\Models;

use App\Core\Model;
use PDO;

class ContratoAnexo extends Model
{
    protected string $table = 'contratos_anexos';

    public function findByContratoId(int $contratoId, int $usuarioId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM {$this->table} WHERE contrato_id = ? AND usuario_id = ? ORDER BY created_at DESC"
        );
        $stmt->execute([$contratoId, $usuarioId]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function create(array $data): string|false
    {
        $sql = "INSERT INTO {$this->table} (contrato_id, usuario_id, file_path, original_name, file_size, mime_type)
                VALUES (:contrato_id, :usuario_id, :file_path, :original_name, :file_size, :mime_type)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':contrato_id'   => $data['contrato_id'],
            ':usuario_id'    => $data['usuario_id'],
            ':file_path'     => $data['file_path'],
            ':original_name' => $data['original_name'],
            ':file_size'     => $data['file_size'] ?? null,
            ':mime_type'     => $data['mime_type'] ?? null,
        ]);
        return $this->pdo->lastInsertId() ?: false;
    }

    public function delete(int $id, int $usuarioId): object|false
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE id = ? AND usuario_id = ? LIMIT 1");
        $stmt->execute([$id, $usuarioId]);
        $anexo = $stmt->fetch(PDO::FETCH_OBJ);
        if (!$anexo) return false;
        $this->pdo->prepare("DELETE FROM {$this->table} WHERE id = ?")->execute([$id]);
        return $anexo;
    }
}
