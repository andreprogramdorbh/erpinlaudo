<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class Medico extends Model
{
    protected string $table = 'medicos';

    public function findById(int $id): object|false
    {
        $sql = "SELECT m.*, e.especialidade AS especialidade_nome, e.subespecialidade AS especialidade_subespecialidade
                FROM {$this->table} m
                LEFT JOIN especialidades e ON e.id = m.especialidade_id
                WHERE m.id = :id
                LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function findByUsuarioId(int $usuarioId, array $filtros = []): array
    {
        $where = ['m.usuario_id = :usuario_id'];
        $params = [':usuario_id' => $usuarioId];

        $status = trim((string) ($filtros['status'] ?? 'ativo'));
        if ($status !== '') {
            $where[] = 'm.status = :status';
            $params[':status'] = $status;
        }

        $q = trim((string) ($filtros['pesquisa'] ?? ''));
        if ($q !== '') {
            $where[] = '(m.nome LIKE :q OR m.crm LIKE :q OR m.cpf LIKE :q OR e.especialidade LIKE :q)';
            $params[':q'] = '%' . $q . '%';
        }

        $sql = "SELECT m.*, e.especialidade AS especialidade_nome, e.subespecialidade AS especialidade_subespecialidade
                FROM {$this->table} m
                LEFT JOIN especialidades e
                  ON e.id = m.especialidade_id
                 AND e.usuario_id = m.usuario_id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY m.nome ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function create(array $data): string|false
    {
        $sql = "INSERT INTO {$this->table}
                (usuario_id, nome, crm, uf_crm, cpf, email, telefone, especialidade_id, subespecialidade, rqe, assinatura_digital, status)
                VALUES
                (:usuario_id, :nome, :crm, :uf_crm, :cpf, :email, :telefone, :especialidade_id, :subespecialidade, :rqe, :assinatura_digital, :status)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':usuario_id', $data['usuario_id'], PDO::PARAM_INT);
        $stmt->bindValue(':nome', $data['nome']);
        $stmt->bindValue(':crm', $data['crm']);
        $stmt->bindValue(':uf_crm', $data['uf_crm']);
        $stmt->bindValue(':cpf', $data['cpf']);
        $stmt->bindValue(':email', $data['email']);
        $stmt->bindValue(':telefone', $data['telefone']);
        $stmt->bindValue(':especialidade_id', $data['especialidade_id'], PDO::PARAM_INT);
        $stmt->bindValue(':subespecialidade', $data['subespecialidade'] ?? null);
        $stmt->bindValue(':rqe', $data['rqe'] ?? null);
        $stmt->bindValue(':assinatura_digital', $data['assinatura_digital'] ?? null);
        $stmt->bindValue(':status', $data['status'] ?? 'ativo');

        if ($stmt->execute()) {
            return $this->pdo->lastInsertId();
        }

        return false;
    }

    public function update(int $id, array $data): bool
    {
        $allowedFields = [
            'nome',
            'crm',
            'uf_crm',
            'cpf',
            'email',
            'telefone',
            'especialidade_id',
            'subespecialidade',
            'rqe',
            'assinatura_digital',
            'status',
        ];

        $updateFields = [];
        $params = [':id' => $id];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $updateFields[] = "{$field} = :{$field}";
                $params[":{$field}"] = $data[$field];
            }
        }

        if (empty($updateFields)) {
            return false;
        }

        $sql = "UPDATE {$this->table}
                SET " . implode(', ', $updateFields) . ", updated_at = CURRENT_TIMESTAMP
                WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }
}
