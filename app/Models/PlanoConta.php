<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class PlanoConta extends Model
{
    protected string $table = 'plano_contas';

    public function findById(int $id): object|false
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function findByUsuarioId(int $usuarioId, array $filtros = []): array
    {
        $where = ["usuario_id = :usuario_id"];
        $params = [':usuario_id' => $usuarioId];

        $status = $filtros['status'] ?? 'ativo';
        if ($status !== '') {
            $where[] = 'status = :status';
            $params[':status'] = $status;
        }

        $tipo = $filtros['tipo'] ?? '';
        if ($tipo !== '') {
            $where[] = 'tipo = :tipo';
            $params[':tipo'] = $tipo;
        }

        $q = trim($filtros['pesquisa'] ?? '');
        if ($q !== '') {
            $where[] = '(codigo LIKE :q OR nome LIKE :q)';
            $params[':q'] = '%' . $q . '%';
        }

        $sql = "SELECT * FROM {$this->table} WHERE " . implode(' AND ', $where) . " ORDER BY codigo ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function listAtivasParaPai(int $usuarioId, ?int $excludeId = null): array
    {
        $sql = "SELECT id, codigo, nome, nivel FROM {$this->table} WHERE usuario_id = :usuario_id AND status = 'ativo'";
        $params = [':usuario_id' => $usuarioId];

        if ($excludeId !== null) {
            $sql .= ' AND id <> :exclude_id';
            $params[':exclude_id'] = $excludeId;
        }

        $sql .= ' ORDER BY codigo ASC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function create(array $data): string|false
    {
        $nivel = 1;
        $contaPaiId = $data['conta_pai_id'] ?? null;
        if (!empty($contaPaiId)) {
            $pai = $this->findById((int)$contaPaiId);
            if ($pai) {
                $nivel = ((int)($pai->nivel ?? 1)) + 1;
            }
        } else {
            $contaPaiId = null;
        }

        $sql = "INSERT INTO {$this->table} (usuario_id, codigo, nome, tipo, nivel, conta_pai_id, status)
                VALUES (:usuario_id, :codigo, :nome, :tipo, :nivel, :conta_pai_id, :status)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':usuario_id', $data['usuario_id']);
        $stmt->bindValue(':codigo', trim($data['codigo']));
        $stmt->bindValue(':nome', trim($data['nome']));
        $stmt->bindValue(':tipo', $data['tipo']);
        $stmt->bindValue(':nivel', $nivel, PDO::PARAM_INT);
        $stmt->bindValue(':conta_pai_id', $contaPaiId, $contaPaiId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':status', $data['status'] ?? 'ativo');

        if ($stmt->execute()) {
            return $this->pdo->lastInsertId();
        }

        return false;
    }

    public function update(int $id, array $data): bool
    {
        $allowedFields = [
            'codigo',
            'nome',
            'tipo',
            'conta_pai_id',
            'status',
        ];

        $updateFields = [];
        $params = [':id' => $id];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $updateFields[] = "{$field} = :{$field}";

                if ($field === 'conta_pai_id') {
                    $value = $data[$field];
                    if ($value === '' || $value === null) {
                        $params[":{$field}"] = null;
                    } else {
                        $params[":{$field}"] = (int)$value;
                    }
                    continue;
                }

                $params[":{$field}"] = $data[$field];
            }
        }

        if (empty($updateFields)) {
            return false;
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $updateFields) . " WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("UPDATE {$this->table} SET status = 'inativo' WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
