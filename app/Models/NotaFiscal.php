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
     * Suporta filtros: numero_nf, data_inicio, data_fim, status, pesquisa.
     */
    public function findByClienteIdAndTenantId(int $clienteId, int $tenantId, array $filtros = []): array
    {
        $where  = [
            "nf.cliente_id = :cliente_id",
            "nf.usuario_id = :tenant_id",
            "nf.status IN ('emitida', 'importada', 'emitida_asaas', 'cancelada', 'erro_emissao', 'agendada')",
        ];
        $params = [':cliente_id' => $clienteId, ':tenant_id' => $tenantId];

        // Filtro por número da NF
        $numeroNf = trim($filtros['numero_nf'] ?? '');
        if ($numeroNf !== '') {
            $where[] = 'nf.numero_nf LIKE :numero_nf';
            $params[':numero_nf'] = '%' . $numeroNf . '%';
        }

        // Filtro por data início
        $dataInicio = trim($filtros['data_inicio'] ?? '');
        if ($dataInicio !== '') {
            $where[] = 'nf.data_emissao >= :data_inicio';
            $params[':data_inicio'] = $dataInicio;
        }

        // Filtro por data fim
        $dataFim = trim($filtros['data_fim'] ?? '');
        if ($dataFim !== '') {
            $where[] = 'nf.data_emissao <= :data_fim';
            $params[':data_fim'] = $dataFim;
        }

        // Filtro por status específico (ex: emitida, importada, emitida_asaas)
        $statusFiltro = trim($filtros['status'] ?? '');
        if ($statusFiltro !== '') {
            // Substitui o IN genérico por um status específico
            $where = array_filter($where, fn($w) => !str_contains($w, 'status IN'));
            $where[] = 'nf.status = :status';
            $params[':status'] = $statusFiltro;
        }

        // Filtro por pesquisa geral
        $pesquisa = trim($filtros['pesquisa'] ?? '');
        if ($pesquisa !== '') {
            $where[] = '(nf.numero_nf LIKE :pesquisa OR nf.serie LIKE :pesquisa)';
            $params[':pesquisa'] = '%' . $pesquisa . '%';
        }

        $sql = "SELECT nf.*
                FROM {$this->table} nf
                WHERE " . implode(' AND ', $where) . "
                ORDER BY nf.data_emissao DESC, nf.id DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Busca uma NF pelo asaas_invoice_id e tenant.
     */
    public function findByAsaasInvoiceId(string $invoiceId, int $tenantId): object|false
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM {$this->table}
             WHERE asaas_invoice_id = :invoice_id AND usuario_id = :tenant_id
             LIMIT 1"
        );
        $stmt->execute([':invoice_id' => $invoiceId, ':tenant_id' => $tenantId]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    /**
     * Busca uma NF pelo asaas_invoice_id sem filtro de tenant (para uso em webhooks).
     * Retorna o registro com usuario_id para validação posterior.
     */
    public function findByAsaasInvoiceIdGlobal(string $invoiceId): object|false
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM {$this->table}
             WHERE asaas_invoice_id = :invoice_id
             LIMIT 1"
        );
        $stmt->execute([':invoice_id' => $invoiceId]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    /**
     * Busca NFs com asaas_invoice_id mas sem pdfUrl (precisam de sincronização).
     */
    public function findPendingSyncByClienteAndTenant(int $clienteId, int $tenantId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM {$this->table}
             WHERE cliente_id = :cliente_id
               AND usuario_id = :tenant_id
               AND asaas_invoice_id IS NOT NULL
               AND asaas_invoice_id != ''
               AND (asaas_pdf_url IS NULL OR asaas_pdf_url = '')
               AND status NOT IN ('cancelada', 'erro_emissao')
             ORDER BY id DESC"
        );
        $stmt->execute([':cliente_id' => $clienteId, ':tenant_id' => $tenantId]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Busca uma NF pelo conta_receber_id e tenant.
     */
    public function findByContaReceberId(int $contaReceberId, int $tenantId): object|false
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM {$this->table}
             WHERE conta_receber_id = :cr_id AND usuario_id = :tenant_id
             LIMIT 1"
        );
        $stmt->execute([':cr_id' => $contaReceberId, ':tenant_id' => $tenantId]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function create(array $data): string|false
    {
        $sql = "INSERT INTO {$this->table}
                (usuario_id, cliente_id, numero_nf, serie, valor_total, data_emissao,
                 status, xml_path, asaas_invoice_id, origem_emissao, conta_receber_id,
                 asaas_pdf_url, asaas_status, servico_descricao, servico_codigo,
                 servico_id_asaas, observacoes_nf)
                VALUES
                (:usuario_id, :cliente_id, :numero_nf, :serie, :valor_total, :data_emissao,
                 :status, :xml_path, :asaas_invoice_id, :origem_emissao, :conta_receber_id,
                 :asaas_pdf_url, :asaas_status, :servico_descricao, :servico_codigo,
                 :servico_id_asaas, :observacoes_nf)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':usuario_id',       (int)$data['usuario_id'], PDO::PARAM_INT);
        $stmt->bindValue(':cliente_id',       (int)$data['cliente_id'], PDO::PARAM_INT);
        $stmt->bindValue(':numero_nf',        trim((string)($data['numero_nf'] ?? '')));
        $stmt->bindValue(':serie',            trim((string)($data['serie'] ?? '')));
        $stmt->bindValue(':valor_total',      $data['valor_total']);
        $stmt->bindValue(':data_emissao',     $data['data_emissao']);
        $stmt->bindValue(':status',           $data['status'] ?? 'rascunho');

        // Campos nullable
        $nullable = ['xml_path', 'asaas_invoice_id', 'asaas_pdf_url', 'asaas_status',
                     'servico_descricao', 'servico_codigo', 'servico_id_asaas', 'observacoes_nf'];
        foreach ($nullable as $f) {
            $v = $data[$f] ?? null;
            if ($v === '' || $v === null) {
                $stmt->bindValue(':' . $f, null, PDO::PARAM_NULL);
            } else {
                $stmt->bindValue(':' . $f, $v);
            }
        }

        $stmt->bindValue(':origem_emissao',   $data['origem_emissao'] ?? 'manual');

        $crId = $data['conta_receber_id'] ?? null;
        if ($crId === null || $crId === '') {
            $stmt->bindValue(':conta_receber_id', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':conta_receber_id', (int)$crId, PDO::PARAM_INT);
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
            'asaas_invoice_id',
            'origem_emissao',
            'conta_receber_id',
            'asaas_pdf_url',
            'asaas_status',
            'servico_descricao',
            'servico_codigo',
            'servico_id_asaas',
            'observacoes_nf',
        ];

        $updateFields = [];
        $params = [':id' => $id];

        foreach ($allowedFields as $field) {
            if (!array_key_exists($field, $data)) {
                continue;
            }

            $updateFields[] = "{$field} = :{$field}";
            $value = $data[$field];

            $nullableFields = ['xml_path', 'asaas_invoice_id', 'asaas_pdf_url', 'asaas_status',
                               'servico_descricao', 'servico_codigo', 'servico_id_asaas',
                               'observacoes_nf', 'conta_receber_id'];

            if (in_array($field, $nullableFields, true) && ($value === '' || $value === null)) {
                $params[":{$field}"] = null;
            } elseif ($field === 'cliente_id' || $field === 'conta_receber_id') {
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
