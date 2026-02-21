<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class NotaFiscalImportacao extends Model
{
    protected string $table = 'notas_fiscais_importacoes';

    public function create(array $data): string|false
    {
        $sql = "INSERT INTO {$this->table} (usuario_id, arquivo_xml_path, status, mensagem)
                VALUES (:usuario_id, :arquivo_xml_path, :status, :mensagem)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':usuario_id', (int)$data['usuario_id'], PDO::PARAM_INT);
        $stmt->bindValue(':arquivo_xml_path', $data['arquivo_xml_path']);
        $stmt->bindValue(':status', $data['status'] ?? 'sucesso');

        $msg = $data['mensagem'] ?? null;
        if ($msg === '' || $msg === null) {
            $stmt->bindValue(':mensagem', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':mensagem', $msg);
        }

        if ($stmt->execute()) {
            return $this->pdo->lastInsertId();
        }

        return false;
    }

    public function updateStatus(int $id, string $status, ?string $mensagem = null): bool
    {
        $stmt = $this->pdo->prepare("UPDATE {$this->table} SET status = :status, mensagem = :mensagem WHERE id = :id");

        $msg = $mensagem;
        if ($msg === '') {
            $msg = null;
        }

        return $stmt->execute([
            ':id' => $id,
            ':status' => $status,
            ':mensagem' => $msg,
        ]);
    }
}
