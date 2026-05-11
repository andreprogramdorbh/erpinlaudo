<?php
namespace App\Models;

use App\Core\Model;
use PDO;

class ColaboradorAnexo extends Model
{
    protected string $table = 'colaboradores_anexos';

    public function findById(int $id): object|false
    {
        $stmt = $this->pdo->prepare("SELECT * FROM `{$this->table}` WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function findByColaboradorId(int $colaboradorId, int $usuarioId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM `{$this->table}` WHERE colaborador_id = ? AND usuario_id = ? ORDER BY created_at DESC"
        );
        $stmt->execute([$colaboradorId, $usuarioId]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function create(array $data): int|false
    {
        $sql = "INSERT INTO `{$this->table}`
                    (colaborador_id, usuario_id, nome_anexo, file_path, original_name, file_size, mime_type)
                VALUES
                    (:colaborador_id, :usuario_id, :nome_anexo, :file_path, :original_name, :file_size, :mime_type)";
        $stmt = $this->pdo->prepare($sql);
        try {
            $result = $stmt->execute([
                ':colaborador_id' => $data['colaborador_id'],
                ':usuario_id'     => $data['usuario_id'],
                ':nome_anexo'     => $data['nome_anexo'],
                ':file_path'      => $data['file_path'],
                ':original_name'  => $data['original_name'],
                ':file_size'      => $data['file_size'],
                ':mime_type'      => $data['mime_type'],
            ]);
            return $result ? (int) $this->pdo->lastInsertId() : false;
        } catch (\PDOException $e) {
            error_log("[ColaboradorAnexo::create] ERRO: " . $e->getMessage());
            return false;
        }
    }

    public function delete(int $id, int $usuarioId): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM `{$this->table}` WHERE id = ? AND usuario_id = ?");
        return $stmt->execute([$id, $usuarioId]);
    }
}
