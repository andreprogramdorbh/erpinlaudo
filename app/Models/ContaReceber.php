<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class ContaReceber extends Model
{
    protected string $table = 'contas_receber';

    public function findById(int $id): object|false
    {
        $sql = "SELECT cr.*, c.razao_social AS cliente_nome, pc.codigo AS plano_codigo, pc.nome AS plano_nome
                FROM {$this->table} cr
                LEFT JOIN clientes c ON c.id = cr.cliente_id
                LEFT JOIN plano_contas pc ON pc.id = cr.plano_conta_id
                WHERE cr.id = ?";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function findByUsuarioId(int $usuarioId, array $filtros = []): array
    {
        $where = ["cr.usuario_id = :usuario_id"];
        $params = [':usuario_id' => $usuarioId];

        $status = $filtros['status'] ?? 'aberta';
        if ($status !== '') {
            $where[] = 'cr.status = :status';
            $params[':status'] = $status;
        }

        $q = trim($filtros['pesquisa'] ?? '');
        if ($q !== '') {
            $where[] = '(cr.descricao LIKE :q OR c.razao_social LIKE :q)';
            $params[':q'] = '%' . $q . '%';
        }

        $sql = "SELECT cr.*, c.razao_social AS cliente_nome, pc.codigo AS plano_codigo
                FROM {$this->table} cr
                LEFT JOIN clientes c ON c.id = cr.cliente_id
                LEFT JOIN plano_contas pc ON pc.id = cr.plano_conta_id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY cr.data_vencimento DESC, cr.id DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Busca contas a receber de um cliente específico (usado no Portal do Cliente).
     * O tenantId garante que o cliente só veja contas do seu tenant (usuário do ERP).
     */
    public function findByClienteIdAndTenantId(int $clienteId, int $tenantId, array $filtros = []): array
    {
        $where  = ['cr.cliente_id = :cliente_id', 'cr.usuario_id = :tenant_id'];
        $params = [':cliente_id' => $clienteId, ':tenant_id' => $tenantId];

        $status = $filtros['status'] ?? '';
        if ($status !== '') {
            $where[]           = 'cr.status = :status';
            $params[':status'] = $status;
        }

        $sql = "SELECT cr.*, pc.codigo AS plano_codigo
                FROM {$this->table} cr
                LEFT JOIN plano_contas pc ON pc.id = cr.plano_conta_id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY cr.data_vencimento ASC, cr.id DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function findByAsaasPaymentIdAndUsuarioId(int $usuarioId, string $paymentId): object|false
    {
        $stmt = $this->pdo->prepare("SELECT id, status FROM {$this->table} WHERE usuario_id = :usuario_id AND asaas_payment_id = :pid LIMIT 1");
        $stmt->execute([
            ':usuario_id' => $usuarioId,
            ':pid' => $paymentId,
        ]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function findByExternalReferenceAndUsuarioId(int $usuarioId, string $externalReference): object|false
    {
        $stmt = $this->pdo->prepare("SELECT id, status, asaas_payment_id FROM {$this->table} WHERE usuario_id = :usuario_id AND external_reference = :ref LIMIT 1");
        $stmt->execute([
            ':usuario_id' => $usuarioId,
            ':ref' => $externalReference,
        ]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function existsRecorrenciaGerada(int $usuarioId, int $prevContaReceberId, int $rootId, string $dataVencimento): bool
    {
        $sql = "SELECT id FROM {$this->table}
                WHERE usuario_id = :usuario_id
                  AND data_vencimento = :data_vencimento
                  AND (
                       external_reference LIKE :prev_tag
                       OR external_reference LIKE :root_tag
                  )
                LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':usuario_id' => $usuarioId,
            ':data_vencimento' => $dataVencimento,
            ':prev_tag' => '%prev:' . (int)$prevContaReceberId . '%',
            ':root_tag' => '%root:' . (int)$rootId . '%',
        ]);

        return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create(array $data): string|false
    {
        $sql = "INSERT INTO {$this->table}
                (usuario_id, cliente_id, plano_conta_id, descricao, valor, data_vencimento, data_recebimento, status, observacoes,
                 meio_pagamento, recorrente, recorrencia_tipo, recorrencia_intervalo, asaas_payment_id, asaas_subscription_id, external_reference)
                VALUES
                (:usuario_id, :cliente_id, :plano_conta_id, :descricao, :valor, :data_vencimento, :data_recebimento, :status, :observacoes,
                 :meio_pagamento, :recorrente, :recorrencia_tipo, :recorrencia_intervalo, :asaas_payment_id, :asaas_subscription_id, :external_reference)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':usuario_id', $data['usuario_id']);
        $stmt->bindValue(':cliente_id', (int)$data['cliente_id'], PDO::PARAM_INT);
        $stmt->bindValue(':plano_conta_id', (int)$data['plano_conta_id'], PDO::PARAM_INT);
        $stmt->bindValue(':descricao', trim($data['descricao']));
        $stmt->bindValue(':valor', $data['valor']);
        $stmt->bindValue(':data_vencimento', $data['data_vencimento']);

        $dataReceb = $data['data_recebimento'] ?? null;
        if ($dataReceb === '' || $dataReceb === null) {
            $stmt->bindValue(':data_recebimento', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':data_recebimento', $dataReceb);
        }

        $stmt->bindValue(':status', $data['status'] ?? 'aberta');

        $obs = $data['observacoes'] ?? null;
        if ($obs === '' || $obs === null) {
            $stmt->bindValue(':observacoes', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':observacoes', $obs);
        }

        $meio = $data['meio_pagamento'] ?? null;
        if ($meio === '' || $meio === null) {
            $stmt->bindValue(':meio_pagamento', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':meio_pagamento', (string)$meio);
        }

        $stmt->bindValue(':recorrente', (int)($data['recorrente'] ?? 0), PDO::PARAM_INT);

        $recTipo = $data['recorrencia_tipo'] ?? null;
        if ($recTipo === '' || $recTipo === null) {
            $stmt->bindValue(':recorrencia_tipo', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':recorrencia_tipo', (string)$recTipo);
        }

        $recInt = $data['recorrencia_intervalo'] ?? null;
        if ($recInt === '' || $recInt === null) {
            $stmt->bindValue(':recorrencia_intervalo', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':recorrencia_intervalo', (int)$recInt, PDO::PARAM_INT);
        }

        $paymentId = $data['asaas_payment_id'] ?? null;
        if ($paymentId === '' || $paymentId === null) {
            $stmt->bindValue(':asaas_payment_id', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':asaas_payment_id', (string)$paymentId);
        }

        $subId = $data['asaas_subscription_id'] ?? null;
        if ($subId === '' || $subId === null) {
            $stmt->bindValue(':asaas_subscription_id', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':asaas_subscription_id', (string)$subId);
        }

        $extRef = $data['external_reference'] ?? null;
        if ($extRef === '' || $extRef === null) {
            $stmt->bindValue(':external_reference', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':external_reference', (string)$extRef);
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
            'plano_conta_id',
            'descricao',
            'valor',
            'data_vencimento',
            'data_recebimento',
            'status',
            'observacoes',
            'meio_pagamento',
            'recorrente',
            'recorrencia_tipo',
            'recorrencia_intervalo',
            'asaas_payment_id',
            'asaas_subscription_id',
            'external_reference',
        ];

        $updateFields = [];
        $params = [':id' => $id];

        foreach ($allowedFields as $field) {
            if (!array_key_exists($field, $data)) {
                continue;
            }

            $updateFields[] = "{$field} = :{$field}";

            $value = $data[$field];
            if (in_array($field, ['data_recebimento', 'observacoes', 'meio_pagamento', 'recorrencia_tipo', 'recorrencia_intervalo', 'asaas_payment_id', 'asaas_subscription_id', 'external_reference'], true)) {
                if ($value === '' || $value === null) {
                    $params[":{$field}"] = null;
                    continue;
                }
            }

            if (in_array($field, ['cliente_id', 'plano_conta_id', 'recorrente', 'recorrencia_intervalo'], true) && $value !== null) {
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

    /**
     * Retorna dados agregados para o dashboard de pagamentos do portal do cliente.
     */
    public function getDashboardDataByClienteId(int $clienteId, int $tenantId): array
    {
        $hoje   = date('Y-m-d');
        $params = [':cliente_id' => $clienteId, ':tenant_id' => $tenantId];

        // Totais por status
        $stmtStatus = $this->pdo->prepare(
            "SELECT status, COUNT(*) AS total, COALESCE(SUM(valor), 0) AS valor_total
             FROM {$this->table}
             WHERE cliente_id = :cliente_id AND usuario_id = :tenant_id
             GROUP BY status"
        );
        $stmtStatus->execute($params);
        $porStatus = $stmtStatus->fetchAll(PDO::FETCH_OBJ);

        // Totais por meio de pagamento (apenas pagas)
        $stmtMeio = $this->pdo->prepare(
            "SELECT COALESCE(NULLIF(meio_pagamento,''),'outro') AS meio,
                    COUNT(*) AS total, COALESCE(SUM(valor), 0) AS valor_total
             FROM {$this->table}
             WHERE cliente_id = :cliente_id AND usuario_id = :tenant_id AND status = 'recebida'
             GROUP BY meio"
        );
        $stmtMeio->execute($params);
        $porMeio = $stmtMeio->fetchAll(PDO::FETCH_OBJ);

        // Evolucao mensal - ultimos 12 meses
        $stmtMensal = $this->pdo->prepare(
            "SELECT DATE_FORMAT(data_vencimento, '%Y-%m') AS mes,
                    status,
                    COALESCE(SUM(valor), 0) AS valor_total
             FROM {$this->table}
             WHERE cliente_id = :cliente_id AND usuario_id = :tenant_id
               AND data_vencimento >= DATE_SUB(:hoje, INTERVAL 11 MONTH)
             GROUP BY mes, status
             ORDER BY mes ASC"
        );
        $stmtMensal->execute(array_merge($params, [':hoje' => $hoje]));
        $mensal = $stmtMensal->fetchAll(PDO::FETCH_OBJ);

        // Contas vencidas
        $stmtVencidas = $this->pdo->prepare(
            "SELECT COUNT(*) AS total, COALESCE(SUM(valor), 0) AS valor_total
             FROM {$this->table}
             WHERE cliente_id = :cliente_id AND usuario_id = :tenant_id
               AND status = 'aberta' AND data_vencimento < :hoje"
        );
        $stmtVencidas->execute(array_merge($params, [':hoje' => $hoje]));
        $vencidas = $stmtVencidas->fetch(PDO::FETCH_OBJ);

        return [
            'por_status' => $porStatus,
            'por_meio'   => $porMeio,
            'mensal'     => $mensal,
            'vencidas'   => $vencidas,
        ];
    }
}
