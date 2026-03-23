<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class User extends Model
{
    protected string $table = "users";

    public function findById(int $id): object|false
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Encontra um usuário pelo seu endereço de e-mail.
     *
     * @param string $email O e-mail do usuário.
     * @return object|false O objeto do usuário se encontrado, ou false caso contrário.
     */
    public function findByEmail(string $email): object|false
    {
        $stmt = $this->pdo->prepare("SELECT *, role FROM {$this->table} WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    /**
     * Cria um novo usuário no banco de dados.
     *
     * @param array $data Os dados do usuário (ex: ["name" => ..., "email" => ..., "password" => ...]).
     * @return string|false O ID do usuário inserido, ou false em caso de falha.
     */
    public function create(array $data): string|false
    {
        $sql = "INSERT INTO {$this->table} (name, email, password, role) VALUES (:name, :email, :password, :role)";
        $stmt = $this->pdo->prepare($sql);

        $stmt->bindValue(":name", $data["name"]);
        $stmt->bindValue(":email", $data["email"]);
        $stmt->bindValue(":password", $data["password"]); // A senha já deve estar com hash
        $stmt->bindValue(":role", $data["role"] ?? "user");

        if ($stmt->execute()) {
            return $this->pdo->lastInsertId();
        }

        return false;
    }

    /**
     * Atualiza a senha do usuário (hash seguro).
     */
    public function updatePassword(int $userId, string $hashedPassword): bool
    {
        $sql = "UPDATE {$this->table} SET password = :password, updated_at = NOW() WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':password' => $hashedPassword,
            ':id' => $userId,
        ]);
    }

    /**
     * Atualiza os dados de um usuário (nome, e-mail, role, status).
     *
     * @param int    $id     ID do usuário a atualizar.
     * @param array  $data   Campos a atualizar: name, email, role, status.
     * @return bool  True em caso de sucesso, false em caso de falha.
     */
    public function update(int $id, array $data): bool
    {
        try {
            // Verifica se a coluna status existe na tabela users
            $checkCol = $this->pdo->query("SHOW COLUMNS FROM {$this->table} LIKE 'status'");
            $statusExists = $checkCol && $checkCol->rowCount() > 0;

            if ($statusExists) {
                $sql = "UPDATE {$this->table}
                        SET name = :name,
                            email = :email,
                            role = :role,
                            status = :status,
                            updated_at = NOW()
                        WHERE id = :id";
                $params = [
                    ':name'   => $data['name'],
                    ':email'  => $data['email'],
                    ':role'   => $data['role'],
                    ':status' => $data['status'] ?? 'ativo',
                    ':id'     => $id,
                ];
            } else {
                // Coluna status não existe ainda — atualiza sem ela
                $sql = "UPDATE {$this->table}
                        SET name = :name,
                            email = :email,
                            role = :role,
                            updated_at = NOW()
                        WHERE id = :id";
                $params = [
                    ':name'  => $data['name'],
                    ':email' => $data['email'],
                    ':role'  => $data['role'],
                    ':id'    => $id,
                ];

                // Tenta adicionar a coluna automaticamente
                try {
                    $this->pdo->exec("ALTER TABLE {$this->table} ADD COLUMN status ENUM('ativo','inativo') NOT NULL DEFAULT 'ativo' AFTER role");
                } catch (\PDOException $alterEx) {
                    // Ignora se já existir (race condition)
                    error_log('[User::update] ALTER TABLE status: ' . $alterEx->getMessage());
                }
            }

            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute($params);

            if (!$result) {
                error_log('[User::update] PDO execute failed for user_id=' . $id . ' | errorInfo=' . json_encode($stmt->errorInfo()));
            }

            return $result;
        } catch (\PDOException $e) {
            error_log('[User::update] PDOException for user_id=' . $id . ': ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Retorna todos os usuários do sistema
     */
    public function findAll(): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} ORDER BY created_at DESC");
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Retorna usuários com um ou mais roles específicos.
     * Usado pelo EmailAlertaService para resolver destinatários 'admin'/'financeiro'.
     *
     * @param string|array $roles  Ex: 'admin' ou ['admin', 'superadmin']
     */
    public function findByRole(string|array $roles): array
    {
        $roles        = (array) $roles;
        $placeholders = implode(',', array_fill(0, count($roles), '?'));
        $stmt = $this->pdo->prepare(
            "SELECT * FROM {$this->table} WHERE role IN ({$placeholders}) ORDER BY name ASC"
        );
        $stmt->execute($roles);
        return $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];
    }
}
