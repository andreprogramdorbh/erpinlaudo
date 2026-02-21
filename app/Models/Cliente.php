<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class Cliente extends Model
{
    protected string $table = "clientes";

    /**
     * Obtém clientes de um usuário com filtros avançados.
     *
     * @param int $usuarioId O ID do usuário.
     * @param array $filtros Filtros: [status, pesquisa, uf, tipo]
     * @return array Array de objetos Cliente.
     */
    public function findByUsuarioId(int $usuarioId, array $filtros = []): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE usuario_id = ?";
        $params = [$usuarioId];

        if (!empty($filtros['status'])) {
            $sql .= " AND status = ?";
            $params[] = $filtros['status'];
        }

        if (!empty($filtros['pesquisa'])) {
            $sql .= " AND (razao_social LIKE ? OR nome_fantasia LIKE ? OR cpf_cnpj LIKE ?)";
            $busca = "%{$filtros['pesquisa']}%";
            $params[] = $busca;
            $params[] = $busca;
            $params[] = $busca;
        }

        if (!empty($filtros['uf'])) {
            $sql .= " AND estado = ?";
            $params[] = $filtros['uf'];
        }

        if (!empty($filtros['tipo'])) {
            $sql .= " AND tipo = ?";
            $params[] = $filtros['tipo'];
        }

        // Ordenar por ID descendente
        $sql .= " ORDER BY id DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Encontra um cliente pelo seu ID.
     *
     * @param int $id O ID do cliente.
     * @return object|false O objeto do cliente se encontrado, ou false caso contrário.
     */
    public function findById(int $id): object|false
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    /**
     * Encontra um cliente pelo CPF/CNPJ.
     *
     * @param string $cpfCnpj O CPF ou CNPJ do cliente.
     * @return object|false O objeto do cliente se encontrado, ou false caso contrário.
     */
    public function findByCpfCnpj(string $cpfCnpj): object|false
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE cpf_cnpj = ?");
        $stmt->execute([$cpfCnpj]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function findByCpfCnpjAndUsuarioId(string $cpfCnpj, int $usuarioId): object|false
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE cpf_cnpj = ? AND usuario_id = ?");
        $stmt->execute([$cpfCnpj, $usuarioId]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    /**
     * Encontra um cliente pelo Email.
     *
     * @param string $email O email do cliente.
     * @return object|false O objeto do cliente se encontrado, ou false caso contrário.
     */
    public function findByEmail(string $email): object|false
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    /**
     * Cria um novo cliente no banco de dados.
     *
     * @param array $data Os dados do cliente.
     * @return string|false O ID do cliente inserido, ou false em caso de falha.
     */
    public function create(array $data): string|false
    {
        $sql = "INSERT INTO {$this->table} 
                (tipo, cpf_cnpj, razao_social, nome_fantasia, email, website, cnae_principal, descricao_cnae, 
                 endereco, numero, complemento, bairro, cidade, estado, cep, 
                 telefone, celular, instagram, tiktok, facebook, usuario_id, status) 
                VALUES 
                (:tipo, :cpf_cnpj, :razao_social, :nome_fantasia, :email, :website, :cnae_principal, :descricao_cnae, 
                 :endereco, :numero, :complemento, :bairro, :cidade, :estado, :cep, 
                 :telefone, :celular, :instagram, :tiktok, :facebook, :usuario_id, :status)";

        $stmt = $this->pdo->prepare($sql);

        $stmt->bindValue(":tipo", $data["tipo"] ?? "PJ");
        $stmt->bindValue(":cpf_cnpj", $data["cpf_cnpj"]);
        $stmt->bindValue(":razao_social", $data["razao_social"]);
        $stmt->bindValue(":nome_fantasia", $data["nome_fantasia"] ?? null);
        $stmt->bindValue(":email", $data["email"]);
        $stmt->bindValue(":website", $data["website"] ?? null);
        $stmt->bindValue(":cnae_principal", $data["cnae_principal"] ?? null);
        $stmt->bindValue(":descricao_cnae", $data["descricao_cnae"] ?? null);
        $stmt->bindValue(":endereco", $data["endereco"] ?? null);
        $stmt->bindValue(":numero", $data["numero"] ?? null);
        $stmt->bindValue(":complemento", $data["complemento"] ?? null);
        $stmt->bindValue(":bairro", $data["bairro"] ?? null);
        $stmt->bindValue(":cidade", $data["cidade"] ?? null);
        $stmt->bindValue(":estado", $data["estado"] ?? null);
        $stmt->bindValue(":cep", $data["cep"] ?? null);
        $stmt->bindValue(":telefone", $data["telefone"] ?? null);
        $stmt->bindValue(":celular", $data["celular"] ?? null);
        $stmt->bindValue(":instagram", $data["instagram"] ?? null);
        $stmt->bindValue(":tiktok", $data["tiktok"] ?? null);
        $stmt->bindValue(":facebook", $data["facebook"] ?? null);
        $stmt->bindValue(":usuario_id", $data["usuario_id"]);
        $stmt->bindValue(":status", $data["status"] ?? "ativo");

        if ($stmt->execute()) {
            return $this->pdo->lastInsertId();
        }

        return false;
    }

    /**
     * Atualiza um cliente existente.
     *
     * @param int $id O ID do cliente a ser atualizado.
     * @param array $data Os dados a serem atualizados.
     * @return bool True se a atualização foi bem-sucedida, false caso contrário.
     */
    public function update(int $id, array $data): bool
    {
        $allowedFields = [
            "tipo",
            "cpf_cnpj",
            "razao_social",
            "nome_fantasia",
            "email",
            "website",
            "cnae_principal",
            "descricao_cnae",
            "endereco",
            "numero",
            "complemento",
            "bairro",
            "cidade",
            "estado",
            "cep",
            "telefone",
            "celular",
            "instagram",
            "tiktok",
            "facebook",
            "status"
        ];

        $updateFields = [];
        $params = [];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateFields[] = "{$field} = :{$field}";
                $params[":{$field}"] = $data[$field];
            }
        }

        if (empty($updateFields)) {
            return false;
        }

        $sql = "UPDATE {$this->table} SET " . implode(", ", $updateFields) . " WHERE id = :id";
        $params[":id"] = $id;

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Deleta um cliente (soft delete - apenas marca como inativo).
     *
     * @param int $id O ID do cliente a ser deletado.
     * @return bool True se a deleção foi bem-sucedida, false caso contrário.
     */
    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("UPDATE {$this->table} SET status = 'inativo' WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Deleta permanentemente um cliente do banco de dados.
     *
     * @param int $id O ID do cliente a ser deletado permanentemente.
     * @return bool True se a deleção foi bem-sucedida, false caso contrário.
     */
    public function forceDelete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Conta o número total de clientes de um usuário.
     *
     * @param int $usuarioId O ID do usuário.
     * @return int O número de clientes.
     */
    public function countByUsuarioId(int $usuarioId): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) as total FROM {$this->table} WHERE usuario_id = ?");
        $stmt->execute([$usuarioId]);
        $result = $stmt->fetch(PDO::FETCH_OBJ);
        return $result->total ?? 0;
    }

    /**
     * Verifica se um CPF/CNPJ já existe no banco de dados.
     *
     * @param string $cpfCnpj O CPF ou CNPJ a verificar.
     * @param int|null $excludeId ID do cliente a excluir da verificação (útil para updates).
     * @return bool True se existe, false caso contrário.
     */
    public function cpfCnpjExists(string $cpfCnpj, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE cpf_cnpj = ?";
        $params = [$cpfCnpj];

        if ($excludeId !== null) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_OBJ);
        return ($result->total ?? 0) > 0;
    }

    /**
     * Verifica se um Email já existe no banco de dados.
     *
     * @param string $email O email a verificar.
     * @param int|null $excludeId ID do cliente a excluir da verificação (útil para updates).
     * @return bool True se existe, false caso contrário.
     */
    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE email = ?";
        $params = [$email];

        if ($excludeId !== null) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_OBJ);
        return ($result->total ?? 0) > 0;
    }
}
