<?php

namespace App\Models;

use App\Core\Model;
use PDO;

/**
 * Tokens de redefinição de senha.
 * Apenas o hash do token é armazenado; o valor em texto puro nunca é persistido.
 */
class PasswordResetToken extends Model
{
    protected string $table = "password_reset_tokens";

    private const TOKEN_BYTES = 32;
    private const EXPIRY_MINUTES = 60;

    /**
     * Gera um token seguro (random_bytes) e retorna [token em texto puro, hash].
     * O texto puro deve ser usado apenas para o link no e-mail; nunca em logs.
     */
    public function createForUser(int $userId): array
    {
        $rawToken = bin2hex(random_bytes(self::TOKEN_BYTES));
        $tokenHash = hash('sha256', $rawToken);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+' . self::EXPIRY_MINUTES . ' minutes'));

        $sql = "INSERT INTO {$this->table} (user_id, token_hash, expires_at) VALUES (:user_id, :token_hash, :expires_at)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':token_hash' => $tokenHash,
            ':expires_at' => $expiresAt,
        ]);

        return ['raw' => $rawToken, 'hash' => $tokenHash, 'expires_at' => $expiresAt];
    }

    /**
     * Busca um registro válido (não usado, não expirado) pelo hash do token.
     */
    public function findValidByTokenHash(string $tokenHash): object|false
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE token_hash = :token_hash 
                AND used_at IS NULL 
                AND expires_at > NOW() 
                LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':token_hash' => $tokenHash]);
        return $stmt->fetch();
    }

    /**
     * Marca o token como utilizado (invalida para reuso).
     */
    public function markAsUsed(int $id): bool
    {
        $sql = "UPDATE {$this->table} SET used_at = NOW() WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * Cria um token específico para um usuário (usado no fluxo de criação/reset)
     */
    public function create(int $userId, string $rawToken): bool
    {
        $tokenHash = hash('sha256', $rawToken);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+' . self::EXPIRY_MINUTES . ' minutes'));

        $sql = "INSERT INTO {$this->table} (user_id, token_hash, expires_at) VALUES (:user_id, :token_hash, :expires_at)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':user_id' => $userId,
            ':token_hash' => $tokenHash,
            ':expires_at' => $expiresAt,
        ]);
    }

    /**
     * Invalida todos os tokens de um usuário
     */
    public function invalidateUserTokens(int $userId): bool
    {
        $sql = "UPDATE {$this->table} SET used_at = NOW() WHERE user_id = :user_id AND used_at IS NULL";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':user_id' => $userId]);
    }
}
