<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class ClienteContato extends Model
{
    protected string $table = "clientes_contatos";

    /**
     * Encontra todos os contatos de um cliente.
     *
     * @param int $clienteId
     * @return array
     */
    public function findByClienteId(int $clienteId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE cliente_id = ? AND status = 'ativo'");
        $stmt->execute([$clienteId]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Cria um novo contato.
     *
     * @param array $data
     * @return string|false
     */
    public function create(array $data): string|false
    {
        $sql = "INSERT INTO {$this->table} 
                (cliente_id, nome, departamento, email, celular, telefone, cargo, observacoes, status) 
                VALUES 
                (:cliente_id, :nome, :departamento, :email, :celular, :telefone, :cargo, :observacoes, :status)";

        $stmt = $this->pdo->prepare($sql);

        $stmt->bindValue(":cliente_id", $data["cliente_id"]);
        $stmt->bindValue(":nome", $data["nome"]);
        $stmt->bindValue(":departamento", $data["departamento"] ?? null);
        $stmt->bindValue(":email", $data["email"] ?? null);
        $stmt->bindValue(":celular", $data["celular"] ?? null);
        $stmt->bindValue(":telefone", $data["telefone"] ?? null);
        $stmt->bindValue(":cargo", $data["cargo"] ?? null);
        $stmt->bindValue(":observacoes", $data["observacoes"] ?? null);
        $stmt->bindValue(":status", $data["status"] ?? "ativo");

        if ($stmt->execute()) {
            return $this->pdo->lastInsertId();
        }

        return false;
    }

    /**
     * Deleta um contato individualmente.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Deleta todos os contatos de um cliente (útil para atualizar a lista).
     *
     * @param int $clienteId
     * @return bool
     */
    public function deleteByClienteId(int $clienteId): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE cliente_id = ?");
        return $stmt->execute([$clienteId]);
    }
}
