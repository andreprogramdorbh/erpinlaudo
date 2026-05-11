<?php
namespace App\Models;

use App\Core\Model;
use PDO;

class Colaborador extends Model
{
    protected string $table = 'colaboradores';

    public function findByUsuarioId(int $usuarioId, array $filtros = []): array
    {
        $where  = ['c.usuario_id = :usuario_id'];
        $params = [':usuario_id' => $usuarioId];

        if (!empty($filtros['pesquisa'])) {
            $where[]              = '(c.nome LIKE :pesquisa OR c.cpf_cnpj LIKE :pesquisa OR c.email LIKE :pesquisa OR c.cargo LIKE :pesquisa)';
            $params[':pesquisa']  = '%' . $filtros['pesquisa'] . '%';
        }
        if (!empty($filtros['status'])) {
            $where[]           = 'c.status = :status';
            $params[':status'] = $filtros['status'];
        }
        if (!empty($filtros['tipo_contratacao'])) {
            $where[]                    = 'c.tipo_contratacao = :tipo_contratacao';
            $params[':tipo_contratacao'] = $filtros['tipo_contratacao'];
        }

        $whereStr = implode(' AND ', $where);
        $sql = "SELECT c.*, u.name AS usuario_nome, u.email AS usuario_email
                  FROM `{$this->table}` c
             LEFT JOIN `users` u ON u.id = c.user_id
                 WHERE {$whereStr}
                 ORDER BY c.nome ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function findById(int $id): object|false
    {
        $stmt = $this->pdo->prepare(
            "SELECT c.*, u.name AS usuario_nome, u.email AS usuario_email, u.role AS usuario_role, u.status AS usuario_status
               FROM `{$this->table}` c
          LEFT JOIN `users` u ON u.id = c.user_id
              WHERE c.id = ? LIMIT 1"
        );
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function findByIdAndUsuarioId(int $id, int $usuarioId): object|false
    {
        $stmt = $this->pdo->prepare(
            "SELECT c.*, u.name AS usuario_nome, u.email AS usuario_email, u.role AS usuario_role, u.status AS usuario_status
               FROM `{$this->table}` c
          LEFT JOIN `users` u ON u.id = c.user_id
              WHERE c.id = ? AND c.usuario_id = ? LIMIT 1"
        );
        $stmt->execute([$id, $usuarioId]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function findByCpfCnpjAndUsuarioId(string $cpfCnpj, int $usuarioId): object|false
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM `{$this->table}` WHERE cpf_cnpj = ? AND usuario_id = ? LIMIT 1"
        );
        $stmt->execute([$cpfCnpj, $usuarioId]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function findByUserId(int $userId): object|false
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM `{$this->table}` WHERE user_id = ? LIMIT 1"
        );
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function create(array $data): string|false
    {
        $campos = $this->camposPermitidos($data);
        $cols   = implode(', ', array_map(fn($c) => "`{$c}`", array_keys($campos)));
        $binds  = implode(', ', array_map(fn($c) => ":{$c}", array_keys($campos)));
        $stmt   = $this->pdo->prepare("INSERT INTO `{$this->table}` ({$cols}) VALUES ({$binds})");
        foreach ($campos as $col => $val) {
            $stmt->bindValue(":{$col}", $val);
        }
        try {
            $stmt->execute();
            $id = $this->pdo->lastInsertId();
            error_log("[Colaborador::create] OK id={$id}");
            return $id ?: false;
        } catch (\PDOException $e) {
            error_log("[Colaborador::create] ERRO: " . $e->getMessage() . " | data=" . json_encode($campos));
            return false;
        }
    }

    public function update(int $id, array $data): bool
    {
        $campos = $this->camposPermitidos($data);
        unset($campos['usuario_id']); // nunca alterar o tenant
        $set    = implode(', ', array_map(fn($c) => "`{$c}` = :{$c}", array_keys($campos)));
        $stmt   = $this->pdo->prepare("UPDATE `{$this->table}` SET {$set}, updated_at = NOW() WHERE id = :id");
        foreach ($campos as $col => $val) {
            $stmt->bindValue(":{$col}", $val);
        }
        $stmt->bindValue(':id', $id);
        try {
            $result = $stmt->execute();
            if (!$result) {
                error_log("[Colaborador::update] PDO execute false id={$id} | " . json_encode($stmt->errorInfo()));
            }
            return $result;
        } catch (\PDOException $e) {
            error_log("[Colaborador::update] ERRO id={$id}: " . $e->getMessage());
            return false;
        }
    }

    public function delete(int $id, int $usuarioId): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM `{$this->table}` WHERE id = ? AND usuario_id = ?");
        return $stmt->execute([$id, $usuarioId]);
    }

    public function vincularUsuario(int $id, int $userId, int $usuarioId): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE `{$this->table}` SET user_id = ?, updated_at = NOW() WHERE id = ? AND usuario_id = ?"
        );
        return $stmt->execute([$userId, $id, $usuarioId]);
    }

    public function desvincularUsuario(int $id, int $usuarioId): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE `{$this->table}` SET user_id = NULL, updated_at = NOW() WHERE id = ? AND usuario_id = ?"
        );
        return $stmt->execute([$id, $usuarioId]);
    }

    public function countByUsuarioId(int $usuarioId): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM `{$this->table}` WHERE usuario_id = ?");
        $stmt->execute([$usuarioId]);
        return (int) $stmt->fetchColumn();
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function camposPermitidos(array $data): array
    {
        $permitidos = [
            'usuario_id','tipo_contratacao','cpf_cnpj','nome','nome_social',
            'data_nascimento','rg','orgao_emissor','pis_pasep','ctps','ctps_serie',
            'estado_civil','escolaridade',
            'inscricao_estadual','inscricao_municipal','cnae_principal','descricao_cnae',
            'nome_responsavel','cpf_responsavel',
            'email','telefone','celular',
            'cep','endereco','numero','complemento','bairro','cidade','estado',
            'cargo','departamento','data_admissao','data_demissao','salario_base',
            'banco','agencia','conta','tipo_conta','chave_pix',
            'user_id','status','observacoes',
        ];
        $result = [];
        foreach ($permitidos as $campo) {
            if (array_key_exists($campo, $data)) {
                $result[$campo] = $data[$campo] === '' ? null : $data[$campo];
            }
        }
        return $result;
    }
}
