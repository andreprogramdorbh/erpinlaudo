<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class CrmInteracao extends Model
{
    protected string $table = 'crm_interacoes';

    public const TIPOS = [
        'email'              => 'E-mail',
        'telefone'           => 'Telefone',
        'whatsapp'           => 'WhatsApp',
        'reuniao_presencial' => 'Reunião Presencial',
        'reuniao_online'     => 'Reunião Online (Video)',
        'visita_tecnica'     => 'Visita Técnica',
        'proposta_enviada'   => 'Proposta Enviada',
        'contrato_enviado'   => 'Contrato Enviado',
        'outro'              => 'Outro',
    ];

    public const ICONES = [
        'email'              => 'fa-envelope',
        'telefone'           => 'fa-phone',
        'whatsapp'           => 'fa-whatsapp',
        'reuniao_presencial' => 'fa-handshake',
        'reuniao_online'     => 'fa-video',
        'visita_tecnica'     => 'fa-map-marker-alt',
        'proposta_enviada'   => 'fa-file-alt',
        'contrato_enviado'   => 'fa-file-signature',
        'outro'              => 'fa-comment',
    ];

    // ---------------------------------------------------------------
    // Garante que a coluna data_retorno existe (auto-migration)
    // Compatível com MySQL 5.7+ (não usa IF NOT EXISTS no ALTER TABLE)
    // ---------------------------------------------------------------
    private bool $colunaGarantida = false;

    private function garantirColunaRetorno(): void
    {
        if ($this->colunaGarantida) {
            return;
        }
        try {
            // Verifica via INFORMATION_SCHEMA se a coluna já existe
            $stmt = $this->pdo->prepare(
                "SELECT COUNT(*) AS cnt
                 FROM INFORMATION_SCHEMA.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME   = :tbl
                   AND COLUMN_NAME  = 'data_retorno'"
            );
            $stmt->execute([':tbl' => $this->table]);
            $exists = (int) $stmt->fetchColumn();

            if (!$exists) {
                $this->pdo->exec(
                    "ALTER TABLE `{$this->table}`
                     ADD COLUMN `data_retorno` DATE NULL DEFAULT NULL
                     COMMENT 'Data programada para o próximo retorno após esta interação'
                     AFTER `resumo`"
                );
            }
        } catch (\Throwable $e) {
            // Falha silenciosa — não impede a operação principal
            error_log('[CrmInteracao] garantirColunaRetorno falhou: ' . $e->getMessage());
        }
        $this->colunaGarantida = true;
    }

    // ---------------------------------------------------------------
    // Consultas
    // ---------------------------------------------------------------

    public function findByRelated(string $type, int $relatedId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT i.*, u.name AS usuario_nome
             FROM {$this->table} i
             LEFT JOIN users u ON u.id = i.usuario_id
             WHERE i.related_type = ? AND i.related_id = ?
             ORDER BY i.data_interacao DESC"
        );
        $stmt->execute([$type, $relatedId]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Retorna a data do próximo retorno programado para uma oportunidade
     * (a data mais próxima futura ou a mais recente se já passada).
     */
    public function proximoRetorno(int $opId): ?string
    {
        $stmt = $this->pdo->prepare(
            "SELECT data_retorno
             FROM {$this->table}
             WHERE related_type = 'oportunidade'
               AND related_id = ?
               AND data_retorno IS NOT NULL
             ORDER BY data_retorno ASC
             LIMIT 1"
        );
        $stmt->execute([$opId]);
        $row = $stmt->fetch(PDO::FETCH_OBJ);
        return $row ? $row->data_retorno : null;
    }

    /**
     * Retorna todos os próximos retornos programados para oportunidades abertas.
     * Se $usuarioId = 0, retorna de todos os usuários (admin).
     */
    public function findProximosRetornos(int $usuarioId): array
    {
        $whereUid = $usuarioId > 0 ? 'AND o.usuario_id = :uid' : '';
        $sql = "SELECT
                    i.id            AS interacao_id,
                    i.data_retorno,
                    i.resumo,
                    o.id            AS oportunidade_id,
                    o.titulo_oportunidade,
                    o.etapa_funil,
                    COALESCE(l.nome_lead, c.razao_social) AS nome_contato,
                    u.name          AS usuario_nome
                FROM {$this->table} i
                INNER JOIN crm_oportunidades o
                        ON o.id = i.related_id AND i.related_type = 'oportunidade'
                LEFT  JOIN crm_leads l ON l.id = o.lead_id
                LEFT  JOIN clientes  c ON c.id = o.cliente_id
                LEFT  JOIN users     u ON u.id = o.usuario_id
                WHERE i.data_retorno IS NOT NULL
                  AND o.status_oportunidade = 'aberta'
                  {$whereUid}
                ORDER BY i.data_retorno ASC";

        $params = [];
        if ($usuarioId > 0) {
            $params[':uid'] = $usuarioId;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    // ---------------------------------------------------------------
    // Escrita
    // ---------------------------------------------------------------

    public function create(array $data): string|false
    {
        $this->garantirColunaRetorno();

        $stmt = $this->pdo->prepare(
            "INSERT INTO {$this->table}
             (usuario_id, related_id, related_type, data_interacao, tipo_interacao, resumo, data_retorno)
             VALUES (:usuario_id, :related_id, :related_type, :data_interacao, :tipo_interacao, :resumo, :data_retorno)"
        );
        $stmt->bindValue(':usuario_id',     (int) $data['usuario_id'], PDO::PARAM_INT);
        $stmt->bindValue(':related_id',     (int) $data['related_id'], PDO::PARAM_INT);
        $stmt->bindValue(':related_type',   $data['related_type']);
        $stmt->bindValue(':data_interacao', $data['data_interacao']);
        $stmt->bindValue(':tipo_interacao', $data['tipo_interacao']);
        $stmt->bindValue(':resumo',         $data['resumo']);
        $stmt->bindValue(':data_retorno',   $data['data_retorno'] ?? null);

        return $stmt->execute() ? $this->pdo->lastInsertId() : false;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function findById(int $id): object|false
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }
}
