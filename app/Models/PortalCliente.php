<?php

namespace App\Models;

use App\Core\Model;
use PDO;

/**
 * Model para gerenciar as credenciais de acesso do Portal do Cliente.
 * Tabela: portal_clientes
 */
class PortalCliente extends Model
{
    protected string $table = 'portal_clientes';

    /**
     * Busca o registro do portal pelo e-mail.
     */
    public function findByEmail(string $email): object|false
    {
        $stmt = $this->pdo->prepare(
            "SELECT pc.*, c.razao_social, c.nome_fantasia, c.cpf_cnpj, c.email AS email_principal,
                    c.telefone, c.celular, c.cidade, c.estado, c.usuario_id AS tenant_id,
                    c.endereco, c.numero, c.complemento, c.bairro, c.cep
             FROM {$this->table} pc
             INNER JOIN clientes c ON c.id = pc.cliente_id
             WHERE pc.email = ? AND pc.ativo = 1
             LIMIT 1"
        );
        $stmt->execute([strtolower(trim($email))]);
        return $stmt->fetch(PDO::FETCH_OBJ) ?: false;
    }

    /**
     * Busca o registro do portal pelo cliente_id.
     */
    public function findByClienteId(int $clienteId): object|false
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM {$this->table} WHERE cliente_id = ? LIMIT 1"
        );
        $stmt->execute([$clienteId]);
        return $stmt->fetch(PDO::FETCH_OBJ) ?: false;
    }

    /**
     * Busca pelo ID do portal com dados completos do cliente.
     */
    public function findById(int $id): object|false
    {
        $stmt = $this->pdo->prepare(
            "SELECT pc.*, c.razao_social, c.nome_fantasia, c.cpf_cnpj, c.email AS email_principal,
                    c.telefone, c.celular, c.cidade, c.estado, c.usuario_id AS tenant_id,
                    c.endereco, c.numero, c.complemento, c.bairro, c.cep
             FROM {$this->table} pc
             INNER JOIN clientes c ON c.id = pc.cliente_id
             WHERE pc.id = ? AND pc.ativo = 1
             LIMIT 1"
        );
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_OBJ) ?: false;
    }

    /**
     * Cria ou atualiza o acesso ao portal para um cliente.
     */
    public function upsert(int $clienteId, string $email): bool
    {
        $email = strtolower(trim($email));
        $existing = $this->findByClienteId($clienteId);

        if ($existing) {
            $stmt = $this->pdo->prepare(
                "UPDATE {$this->table} SET email = ?, updated_at = NOW() WHERE cliente_id = ?"
            );
            return $stmt->execute([$email, $clienteId]);
        }

        $stmt = $this->pdo->prepare(
            "INSERT INTO {$this->table} (cliente_id, email, password_hash, primeiro_acesso, ativo)
             VALUES (?, ?, NULL, 1, 1)"
        );
        return $stmt->execute([$clienteId, $email]);
    }

    /**
     * Define a senha do portal (após o primeiro acesso).
     */
    public function definirSenha(int $id, string $passwordHash): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE {$this->table}
             SET password_hash = ?, primeiro_acesso = 0, updated_at = NOW()
             WHERE id = ?"
        );
        return $stmt->execute([$passwordHash, $id]);
    }

    /**
     * Atualiza o timestamp de último acesso.
     */
    public function registrarAcesso(int $id): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE {$this->table} SET ultimo_acesso = NOW() WHERE id = ?"
        );
        $stmt->execute([$id]);
    }

    /**
     * Cria um token de primeiro acesso ou reset de senha.
     * Retorna o token gerado.
     */
    public function criarToken(int $clienteId, string $tipo = 'primeiro_acesso'): string
    {
        // Invalida tokens anteriores do mesmo tipo
        $stmt = $this->pdo->prepare(
            "UPDATE portal_clientes_tokens SET usado = 1
             WHERE cliente_id = ? AND tipo = ? AND usado = 0"
        );
        $stmt->execute([$clienteId, $tipo]);

        $token = bin2hex(random_bytes(48)); // 96 chars hex
        $expira = date('Y-m-d H:i:s', strtotime('+24 hours'));

        $stmt = $this->pdo->prepare(
            "INSERT INTO portal_clientes_tokens (cliente_id, token, tipo, expira_em)
             VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$clienteId, $token, $tipo, $expira]);

        return $token;
    }

    /**
     * Valida e retorna um token (deve estar ativo e não expirado).
     */
    public function validarToken(string $token, string $tipo = 'primeiro_acesso'): object|false
    {
        $stmt = $this->pdo->prepare(
            "SELECT t.*, pc.id AS portal_id, pc.email, pc.primeiro_acesso, pc.cliente_id
             FROM portal_clientes_tokens t
             INNER JOIN portal_clientes pc ON pc.cliente_id = t.cliente_id
             WHERE t.token = ?
               AND t.tipo = ?
               AND t.usado = 0
               AND t.expira_em > NOW()
             LIMIT 1"
        );
        $stmt->execute([$token, $tipo]);
        return $stmt->fetch(PDO::FETCH_OBJ) ?: false;
    }

    /**
     * Marca um token como usado.
     */
    public function consumirToken(string $token): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE portal_clientes_tokens SET usado = 1 WHERE token = ?"
        );
        $stmt->execute([$token]);
    }
}
