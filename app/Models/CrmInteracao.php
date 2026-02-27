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

    public function create(array $data): string|false
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO {$this->table}
             (usuario_id, related_id, related_type, data_interacao, tipo_interacao, resumo)
             VALUES (:usuario_id, :related_id, :related_type, :data_interacao, :tipo_interacao, :resumo)"
        );
        $stmt->bindValue(':usuario_id',      (int) $data['usuario_id'], PDO::PARAM_INT);
        $stmt->bindValue(':related_id',      (int) $data['related_id'], PDO::PARAM_INT);
        $stmt->bindValue(':related_type',    $data['related_type']);
        $stmt->bindValue(':data_interacao',  $data['data_interacao']);
        $stmt->bindValue(':tipo_interacao',  $data['tipo_interacao']);
        $stmt->bindValue(':resumo',          $data['resumo']);

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
