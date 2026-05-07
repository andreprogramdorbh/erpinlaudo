<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class ContaReceber extends Model
{
    protected string $table = 'contas_receber';

    /**
     * Expõe o PDO para uso em services que precisam de queries customizadas.
     */
    public function getPdo(): \PDO
    {
        return $this->pdo;
    }

    /**
     * Busca todas as parcelas de um grupo.
     */
    public function findByGrupoParcelas(int $usuarioId, string $grupoParcelas): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT cr.*, c.razao_social AS cliente_nome
             FROM {$this->table} cr
             LEFT JOIN clientes c ON c.id = cr.cliente_id
             WHERE cr.usuario_id = ? AND cr.grupo_parcelas = ?
             ORDER BY cr.numero_parcela ASC, cr.data_vencimento ASC"
        );
        $stmt->execute([$usuarioId, $grupoParcelas]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Busca todas as contas a receber de um contrato.
     */
    public function findByContratoId(int $usuarioId, int $contratoId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT cr.*, c.razao_social AS cliente_nome
             FROM {$this->table} cr
             LEFT JOIN clientes c ON c.id = cr.cliente_id
             WHERE cr.usuario_id = ? AND cr.contrato_id = ?
             ORDER BY cr.data_vencimento ASC, cr.numero_parcela ASC"
        );
        $stmt->execute([$usuarioId, $contratoId]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Resumo de parcelas de um grupo para exibição no portal e no ERP.
     */
    public function getResumoParcelas(int $usuarioId, string $grupoParcelas): object
    {
        $hoje = date('Y-m-d');
        $stmt = $this->pdo->prepare(
            "SELECT
               COUNT(*) AS total,
               SUM(CASE WHEN status = 'recebida' THEN 1 ELSE 0 END) AS pagas,
               SUM(CASE WHEN status = 'aberta' AND data_vencimento < :hoje THEN 1 ELSE 0 END) AS vencidas,
               SUM(CASE WHEN status = 'aberta' AND data_vencimento >= :hoje2 THEN 1 ELSE 0 END) AS abertas,
               COALESCE(SUM(valor), 0) AS valor_total,
               COALESCE(SUM(CASE WHEN status = 'recebida' THEN valor ELSE 0 END), 0) AS valor_pago,
               MIN(data_vencimento) AS primeiro_vencimento,
               MAX(data_vencimento) AS ultimo_vencimento
             FROM {$this->table}
             WHERE usuario_id = :usuario_id AND grupo_parcelas = :grupo"
        );
        $stmt->execute([
            ':usuario_id' => $usuarioId,
            ':grupo'      => $grupoParcelas,
            ':hoje'       => $hoje,
            ':hoje2'      => $hoje,
        ]);
        return $stmt->fetch(PDO::FETCH_OBJ) ?: (object)[];
    }

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

    /**
     * Busca grupos de parcelas de um cliente para exibição no portal.
     * Retorna um resumo por grupo_parcelas com progresso de pagamento.
     */
    public function findGruposByClienteId(int $clienteId, int $tenantId): array
    {
        $sql = "SELECT
                    grupo_parcelas,
                    MIN(descricao) AS descricao,
                    MIN(data_vencimento) AS primeira_parcela,
                    MAX(data_vencimento) AS ultima_parcela,
                    COUNT(*) AS total_parcelas,
                    SUM(CASE WHEN status = 'recebida' THEN 1 ELSE 0 END) AS parcelas_pagas,
                    SUM(CASE WHEN status = 'aberta' AND data_vencimento < CURDATE() THEN 1 ELSE 0 END) AS parcelas_vencidas,
                    SUM(valor) AS valor_total,
                    SUM(CASE WHEN status = 'recebida' THEN valor ELSE 0 END) AS valor_pago,
                    contrato_id
                FROM {$this->table}
                WHERE cliente_id = :cliente_id
                  AND usuario_id = :tenant_id
                  AND grupo_parcelas IS NOT NULL
                  AND grupo_parcelas != ''
                GROUP BY grupo_parcelas, contrato_id
                ORDER BY primeira_parcela ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':cliente_id' => $clienteId, ':tenant_id' => $tenantId]);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /**
     * Busca todas as parcelas de um grupo para exibição no portal (por cliente).
     */
    public function findParcelasByGrupoAndCliente(int $clienteId, int $tenantId, string $grupo): array
    {
        $sql = "SELECT * FROM {$this->table}
                WHERE cliente_id = :cliente_id
                  AND usuario_id = :tenant_id
                  AND grupo_parcelas = :grupo
                ORDER BY numero_parcela ASC, data_vencimento ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':cliente_id' => $clienteId, ':tenant_id' => $tenantId, ':grupo' => $grupo]);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
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

    /**
     * Busca uma conta a receber pelo ID de fatura da Cora.
     * Usado pelo webhook da Cora para identificar a conta a atualizar.
     */
    public function findByCoraInvoiceId(string $coraInvoiceId): object|false
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM {$this->table} WHERE cora_invoice_id = :cora_invoice_id LIMIT 1"
        );
        $stmt->execute([':cora_invoice_id' => $coraInvoiceId]);
        return $stmt->fetch(PDO::FETCH_OBJ) ?: false;
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
                 meio_pagamento, recorrente, recorrencia_tipo, recorrencia_intervalo, asaas_payment_id, asaas_subscription_id, cora_invoice_id, external_reference,
                 numero_parcela, total_parcelas, grupo_parcelas, recorrencia_modo, contrato_id)
                VALUES
                (:usuario_id, :cliente_id, :plano_conta_id, :descricao, :valor, :data_vencimento, :data_recebimento, :status, :observacoes,
                 :meio_pagamento, :recorrente, :recorrencia_tipo, :recorrencia_intervalo, :asaas_payment_id, :asaas_subscription_id, :cora_invoice_id, :external_reference,
                 :numero_parcela, :total_parcelas, :grupo_parcelas, :recorrencia_modo, :contrato_id)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':usuario_id', $data['usuario_id']);
        $clienteIdVal = $data['cliente_id'] ?? null;
        if ($clienteIdVal === null || $clienteIdVal === '' || (int)$clienteIdVal === 0) {
            $stmt->bindValue(':cliente_id', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':cliente_id', (int)$clienteIdVal, PDO::PARAM_INT);
        }
        $planoContaR = $data['plano_conta_id'] ?? null;
        if ($planoContaR === null || $planoContaR === '' || (int)$planoContaR === 0) {
            $stmt->bindValue(':plano_conta_id', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':plano_conta_id', (int)$planoContaR, PDO::PARAM_INT);
        }
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

        $coraInvoiceId = $data['cora_invoice_id'] ?? null;
        if ($coraInvoiceId === '' || $coraInvoiceId === null) {
            $stmt->bindValue(':cora_invoice_id', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':cora_invoice_id', (string)$coraInvoiceId);
        }

        $extRef = $data['external_reference'] ?? null;
        if ($extRef === '' || $extRef === null) {
            $stmt->bindValue(':external_reference', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':external_reference', (string)$extRef);
        }

        // Campos de parcelas (opcionais)
        $numParcela = $data['numero_parcela'] ?? null;
        $stmt->bindValue(':numero_parcela', ($numParcela !== null && $numParcela !== '') ? (int)$numParcela : null, PDO::PARAM_INT);

        $totalParcelas = $data['total_parcelas'] ?? null;
        $stmt->bindValue(':total_parcelas', ($totalParcelas !== null && $totalParcelas !== '') ? (int)$totalParcelas : null, PDO::PARAM_INT);

        $grupoParcelas = $data['grupo_parcelas'] ?? null;
        $stmt->bindValue(':grupo_parcelas', ($grupoParcelas === '' || $grupoParcelas === null) ? null : (string)$grupoParcelas);

        $recorrenciaModo = $data['recorrencia_modo'] ?? 'rolling';
        $stmt->bindValue(':recorrencia_modo', ($recorrenciaModo === '' || $recorrenciaModo === null) ? 'rolling' : (string)$recorrenciaModo);

        $contratoId = $data['contrato_id'] ?? null;
        $stmt->bindValue(':contrato_id', ($contratoId === '' || $contratoId === null) ? null : (int)$contratoId, PDO::PARAM_INT);

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
            'cora_invoice_id',
            'external_reference',
            'numero_parcela',
            'total_parcelas',
            'grupo_parcelas',
            'recorrencia_modo',
            'contrato_id',
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
