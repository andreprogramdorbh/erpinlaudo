<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class ContaPagar extends Model
{
    protected string $table = 'contas_pagar';

    public function findById(int $id): object|false
    {
        $sql = "SELECT cp.*, f.nome AS fornecedor_nome, pc.codigo AS plano_codigo, pc.nome AS plano_nome
                FROM {$this->table} cp
                LEFT JOIN fornecedores f ON f.id = cp.fornecedor_id
                LEFT JOIN plano_contas pc ON pc.id = cp.plano_conta_id
                WHERE cp.id = ?";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function findByUsuarioId(int $usuarioId, array $filtros = []): array
    {
        $where = ["cp.usuario_id = :usuario_id"];
        $params = [':usuario_id' => $usuarioId];

        $status = $filtros['status'] ?? 'aberta';
        if ($status !== '') {
            $where[] = 'cp.status = :status';
            $params[':status'] = $status;
        }

        $q = trim($filtros['pesquisa'] ?? '');
        if ($q !== '') {
            $where[] = '(cp.descricao LIKE :q OR f.nome LIKE :q)';
            $params[':q'] = '%' . $q . '%';
        }

        $sql = "SELECT cp.*, f.nome AS fornecedor_nome, pc.codigo AS plano_codigo
                FROM {$this->table} cp
                LEFT JOIN fornecedores f ON f.id = cp.fornecedor_id
                LEFT JOIN plano_contas pc ON pc.id = cp.plano_conta_id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY cp.data_vencimento DESC, cp.id DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function create(array $data): string|false
    {
        $sql = "INSERT INTO {$this->table}
                (usuario_id, plano_conta_id, fornecedor_id, descricao, valor, data_vencimento, data_pagamento, codigo_barras,
                 recorrente, recorrencia_tipo, recorrencia_intervalo, status, observacoes)
                VALUES
                (:usuario_id, :plano_conta_id, :fornecedor_id, :descricao, :valor, :data_vencimento, :data_pagamento, :codigo_barras,
                 :recorrente, :recorrencia_tipo, :recorrencia_intervalo, :status, :observacoes)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':usuario_id', $data['usuario_id']);
        $stmt->bindValue(':plano_conta_id', (int)$data['plano_conta_id'], PDO::PARAM_INT);

        $fornecedorId = $data['fornecedor_id'] ?? null;
        if ($fornecedorId === '' || $fornecedorId === null) {
            $stmt->bindValue(':fornecedor_id', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':fornecedor_id', (int)$fornecedorId, PDO::PARAM_INT);
        }

        $stmt->bindValue(':descricao', trim($data['descricao']));
        $stmt->bindValue(':valor', $data['valor']);
        $stmt->bindValue(':data_vencimento', $data['data_vencimento']);

        $dataPagamento = $data['data_pagamento'] ?? null;
        if ($dataPagamento === '' || $dataPagamento === null) {
            $stmt->bindValue(':data_pagamento', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':data_pagamento', $dataPagamento);
        }

        $codigoBarras = $data['codigo_barras'] ?? null;
        if ($codigoBarras === '' || $codigoBarras === null) {
            $stmt->bindValue(':codigo_barras', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':codigo_barras', $codigoBarras);
        }

        $stmt->bindValue(':recorrente', (int)($data['recorrente'] ?? 0), PDO::PARAM_INT);

        $recTipo = $data['recorrencia_tipo'] ?? null;
        if ($recTipo === '' || $recTipo === null) {
            $stmt->bindValue(':recorrencia_tipo', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':recorrencia_tipo', $recTipo);
        }

        $recInt = $data['recorrencia_intervalo'] ?? null;
        if ($recInt === '' || $recInt === null) {
            $stmt->bindValue(':recorrencia_intervalo', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':recorrencia_intervalo', (int)$recInt, PDO::PARAM_INT);
        }

        $stmt->bindValue(':status', $data['status'] ?? 'aberta');

        $obs = $data['observacoes'] ?? null;
        if ($obs === '' || $obs === null) {
            $stmt->bindValue(':observacoes', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':observacoes', $obs);
        }

        if ($stmt->execute()) {
            return $this->pdo->lastInsertId();
        }

        return false;
    }

    public function update(int $id, array $data): bool
    {
        $allowedFields = [
            'plano_conta_id',
            'fornecedor_id',
            'descricao',
            'valor',
            'data_vencimento',
            'data_pagamento',
            'codigo_barras',
            'recorrente',
            'recorrencia_tipo',
            'recorrencia_intervalo',
            'status',
            'observacoes',
        ];

        $updateFields = [];
        $params = [':id' => $id];

        foreach ($allowedFields as $field) {
            if (!array_key_exists($field, $data)) {
                continue;
            }

            $updateFields[] = "{$field} = :{$field}";

            $value = $data[$field];
            if (in_array($field, ['fornecedor_id', 'data_pagamento', 'codigo_barras', 'recorrencia_tipo', 'recorrencia_intervalo', 'observacoes'], true)) {
                if ($value === '' || $value === null) {
                    $params[":{$field}"] = null;
                    continue;
                }
            }

            if (in_array($field, ['plano_conta_id', 'fornecedor_id', 'recorrente', 'recorrencia_intervalo'], true) && $value !== null) {
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
