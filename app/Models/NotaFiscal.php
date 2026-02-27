<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class NotaFiscal extends Model
{
    protected string $table = 'notas_fiscais';

    public function findById(int $id): object|false
    {
        $sql = "SELECT nf.*, c.razao_social AS cliente_nome
                FROM {$this->table} nf
                LEFT JOIN clientes c ON c.id = nf.cliente_id
                WHERE nf.id = ?";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function findByUsuarioId(int $usuarioId, array $filtros = []): array
    {
        $where = ["nf.usuario_id = :usuario_id"];
        $params = [':usuario_id' => $usuarioId];

        $status = $filtros['status'] ?? '';
        if ($status !== '') {
            $where[] = 'nf.status = :status';
            $params[':status'] = $status;
        }

        $q = trim($filtros['pesquisa'] ?? '');
        if ($q !== '') {
            $where[] = '(nf.numero_nf LIKE :q OR nf.serie LIKE :q OR c.razao_social LIKE :q)';
            $params[':q'] = '%' . $q . '%';
        }

        $sql = "SELECT nf.*, c.razao_social AS cliente_nome
                FROM {$this->table} nf
                INNER JOIN clientes c ON c.id = nf.cliente_id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY nf.data_emissao DESC, nf.id DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Busca notas fiscais de um cliente específico (usado no Portal do Cliente).
     * Retorna apenas notas emitidas e importadas (visíveis ao cliente).
     */
    public function findByClienteIdAndTenantId(int $clienteId, int $tenantId): array
    {
        $sql = "SELECT nf.*
                FROM {$this->table} nf
                WHERE nf.cliente_id = :cliente_id
                  AND nf.usuario_id = :tenant_id
                  AND nf.status IN ('emitida', 'importada')
                ORDER BY nf.data_emissao DESC, nf.id DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':cliente_id' => $clienteId, ':tenant_id' => $tenantId]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function create(array $data): string|false
    {
        $sql = "INSERT INTO {$this->table}
                (usuario_id, cliente_id, numero_nf, serie, valor_total, data_emissao, status, xml_path)
                VALUES
                (:usuario_id, :cliente_id, :numero_nf, :serie, :valor_total, :data_emissao, :status, :xml_path)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':usuario_id', (int)$data['usuario_id'], PDO::PARAM_INT);
        $stmt->bindValue(':cliente_id', (int)$data['cliente_id'], PDO::PARAM_INT);
        $stmt->bindValue(':numero_nf', trim((string)$data['numero_nf']));
        $stmt->bindValue(':serie', trim((string)$data['serie']));
        $stmt->bindValue(':valor_total', $data['valor_total']);
        $stmt->bindValue(':data_emissao', $data['data_emissao']);
        $stmt->bindValue(':status', $data['status'] ?? 'rascunho');

        $xmlPath = $data['xml_path'] ?? null;
        if ($xmlPath === '' || $xmlPath === null) {
            $stmt->bindValue(':xml_path', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':xml_path', $xmlPath);
        }

        if ($stmt->execute()) {
            return $this->pdo->lastInsertId();
        }

        return false;
    }

    public function update(int $id, array $data): bool
    {
        $allowedFields = [
            'cliente_id',
            'numero_nf',
            'serie',
            'valor_total',
            'data_emissao',
            'status',
            'xml_path',
        ];

        $updateFields = [];
        $params = [':id' => $id];

        foreach ($allowedFields as $field) {
            if (!array_key_exists($field, $data)) {
                continue;
            }

            $updateFields[] = "{$field} = :{$field}";

            $value = $data[$field];
            if ($field === 'xml_path') {
                if ($value === '' || $value === null) {
                    $params[":{$field}"] = null;
                    continue;
                }
            }

            if ($field === 'cliente_id') {
                $params[":{$field}"] = (int)$value;
            } else {
                $params[":{$field}"] = $value;
            }
        }

        if (empty($updateFields)) {
            return false;
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $updateFields) . " WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function cancel(int $id): bool
    {
        $stmt = $this->pdo->prepare("UPDATE {$this->table} SET status = 'cancelada' WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
