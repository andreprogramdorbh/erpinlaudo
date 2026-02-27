<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class CrmOportunidade extends Model
{
    protected string $table = 'crm_oportunidades';

    // ------------------------------------------------------------------
    // Constantes de domínio
    // ------------------------------------------------------------------
    public const ETAPAS = [
        'qualificacao' => 'Qualificação',
        'proposta'     => 'Proposta',
        'negociacao'   => 'Negociação',
        'fechamento'   => 'Fechamento',
    ];

    public const STATUS = [
        'aberta'  => 'Aberta',
        'ganha'   => 'Ganha',
        'perdida' => 'Perdida',
    ];

    public const MODALIDADES = [
        'tomografia'    => 'Tomografia',
        'ressonancia'   => 'Ressonância Magnética',
        'raio_x'        => 'Raio-X',
        'mamografia'    => 'Mamografia',
        'ultrassom'     => 'Ultrassom',
        'densitometria' => 'Densitometria Óssea',
        'pet_ct'        => 'PET-CT',
        'laudos_gerais' => 'Laudos Gerais',
        'outro'         => 'Outro',
    ];

    public const TIPOS_CONTRATO = [
        'laudo_avulso'        => 'Laudo Avulso',
        'contrato_mensal'     => 'Contrato Mensal',
        'contrato_anual'      => 'Contrato Anual',
        'projeto_implantacao' => 'Projeto de Implantação',
        'outro'               => 'Outro',
    ];

    // ------------------------------------------------------------------
    // Consultas
    // ------------------------------------------------------------------

    public function findByUsuarioId(int $usuarioId, array $filtros = []): array
    {
        $where  = ['o.usuario_id = :uid'];
        $params = [':uid' => $usuarioId];

        if (!empty($filtros['etapa'])) {
            $where[]         = 'o.etapa_funil = :etapa';
            $params[':etapa'] = $filtros['etapa'];
        }
        if (!empty($filtros['status'])) {
            $where[]           = 'o.status_oportunidade = :status';
            $params[':status'] = $filtros['status'];
        }
        if (!empty($filtros['q'])) {
            $where[]      = '(o.titulo_oportunidade LIKE :q OR l.nome_lead LIKE :q OR c.razao_social LIKE :q)';
            $params[':q'] = '%' . $filtros['q'] . '%';
        }

        $sql = "SELECT o.*,
                       COALESCE(l.nome_lead, c.razao_social) AS nome_contato,
                       l.email AS lead_email,
                       l.telefone AS lead_telefone,
                       (SELECT COUNT(*) FROM crm_interacoes i
                        WHERE i.related_type = 'oportunidade' AND i.related_id = o.id) AS total_interacoes
                FROM {$this->table} o
                LEFT JOIN crm_leads l ON l.id = o.lead_id
                LEFT JOIN clientes  c ON c.id = o.cliente_id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY o.data_fechamento_prevista ASC, o.created_at DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /** Retorna todas as oportunidades abertas agrupadas por etapa (para o Funil Kanban) */
    public function findAbertosByUsuarioId(int $usuarioId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT o.*,
                    COALESCE(l.nome_lead, c.razao_social) AS nome_contato,
                    l.email AS lead_email
             FROM {$this->table} o
             LEFT JOIN crm_leads l ON l.id = o.lead_id
             LEFT JOIN clientes  c ON c.id = o.cliente_id
             WHERE o.usuario_id = ? AND o.status_oportunidade = 'aberta'
             ORDER BY o.valor_estimado DESC, o.created_at DESC"
        );
        $stmt->execute([$usuarioId]);
        $rows = $stmt->fetchAll(PDO::FETCH_OBJ);

        // Agrupa por etapa
        $grouped = array_fill_keys(array_keys(self::ETAPAS), []);
        foreach ($rows as $row) {
            $grouped[$row->etapa_funil][] = $row;
        }
        return $grouped;
    }

    public function findById(int $id): object|false
    {
        $stmt = $this->pdo->prepare(
            "SELECT o.*,
                    COALESCE(l.nome_lead, c.razao_social) AS nome_contato,
                    l.email AS lead_email, l.telefone AS lead_telefone,
                    l.segmento_principal AS lead_segmento,
                    l.responsavel_nome AS lead_responsavel
             FROM {$this->table} o
             LEFT JOIN crm_leads l ON l.id = o.lead_id
             LEFT JOIN clientes  c ON c.id = o.cliente_id
             WHERE o.id = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function create(array $data): string|false
    {
        $fields = [
            'usuario_id','lead_id','cliente_id','titulo_oportunidade','etapa_funil',
            'valor_estimado','data_fechamento_prevista','probabilidade_sucesso',
            'status_oportunidade','motivo_perda','modalidade_principal',
            'tipo_contrato','volume_estimado_mes','observacoes',
        ];

        $cols  = implode(', ', $fields);
        $binds = ':' . implode(', :', $fields);
        $sql   = "INSERT INTO {$this->table} ({$cols}) VALUES ({$binds})";
        $stmt  = $this->pdo->prepare($sql);

        foreach ($fields as $f) {
            $val = $data[$f] ?? null;
            $stmt->bindValue(':' . $f, ($val === '') ? null : $val);
        }

        return $stmt->execute() ? $this->pdo->lastInsertId() : false;
    }

    public function update(int $id, array $data): bool
    {
        $allowed = [
            'lead_id','cliente_id','titulo_oportunidade','etapa_funil',
            'valor_estimado','data_fechamento_prevista','probabilidade_sucesso',
            'status_oportunidade','motivo_perda','modalidade_principal',
            'tipo_contrato','volume_estimado_mes','observacoes',
        ];

        $sets   = [];
        $params = [':id' => $id];

        foreach ($allowed as $f) {
            if (!array_key_exists($f, $data)) continue;
            $sets[]           = "{$f} = :{$f}";
            $params[':' . $f] = ($data[$f] === '') ? null : $data[$f];
        }

        if (empty($sets)) return false;

        $stmt = $this->pdo->prepare(
            "UPDATE {$this->table} SET " . implode(', ', $sets) . " WHERE id = :id"
        );
        return $stmt->execute($params);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /** Atualiza apenas a etapa do funil (usado pelo drag-and-drop) */
    public function updateEtapa(int $id, string $etapa): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE {$this->table} SET etapa_funil = ? WHERE id = ?"
        );
        return $stmt->execute([$etapa, $id]);
    }

    /** Totais por etapa para o resumo do funil */
    public function resumoFunilByUsuarioId(int $usuarioId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT etapa_funil,
                    COUNT(*) AS total,
                    COALESCE(SUM(valor_estimado), 0) AS valor_total
             FROM {$this->table}
             WHERE usuario_id = ? AND status_oportunidade = 'aberta'
             GROUP BY etapa_funil"
        );
        $stmt->execute([$usuarioId]);
        $rows   = $stmt->fetchAll(PDO::FETCH_OBJ);
        $result = [];
        foreach (array_keys(self::ETAPAS) as $e) {
            $result[$e] = ['total' => 0, 'valor_total' => 0.0];
        }
        foreach ($rows as $r) {
            $result[$r->etapa_funil] = [
                'total'       => (int)   $r->total,
                'valor_total' => (float) $r->valor_total,
            ];
        }
        return $result;
    }
}
