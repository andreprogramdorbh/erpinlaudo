<?php

namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * CrmTransferencia
 *
 * Gerencia o histórico de transferências de leads e oportunidades entre usuários.
 * Cada transferência é imutável (só INSERT, sem UPDATE/DELETE).
 */
class CrmTransferencia
{
    private PDO $pdo;
    private string $table = 'crm_transferencias';

    /** Rótulos legíveis dos motivos */
    public const MOTIVOS = [
        'sdr_qualificacao'        => 'SDR Qualificação',
        'conta_chave'             => 'Conta Chave',
        'colaborador_desligado'   => 'Colaborador Desligado',
        'rodizio_por_inatividade' => 'Rodízio por Inatividade',
    ];

    public function __construct()
    {
        $this->pdo = Database::getInstance();
        $this->garantirTabela();
    }

    // -------------------------------------------------------------------------
    // Auto-migration
    // -------------------------------------------------------------------------

    private function garantirTabela(): void
    {
        try {
            $this->pdo->exec(
                "CREATE TABLE IF NOT EXISTS `{$this->table}` (
                  id                INT AUTO_INCREMENT PRIMARY KEY,
                  usuario_id        INT NOT NULL,
                  related_id        INT NOT NULL,
                  related_type      ENUM('lead','oportunidade') NOT NULL,
                  de_usuario_id     INT NOT NULL,
                  para_usuario_id   INT NOT NULL,
                  motivo            ENUM(
                                      'sdr_qualificacao',
                                      'conta_chave',
                                      'colaborador_desligado',
                                      'rodizio_por_inatividade'
                                    ) NOT NULL,
                  observacao        TEXT NULL,
                  created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  INDEX idx_crm_transf_related (related_type, related_id),
                  INDEX idx_crm_transf_usuario (usuario_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            );
        } catch (\Throwable $e) {
            error_log('[CrmTransferencia] garantirTabela falhou: ' . $e->getMessage());
        }
    }

    // -------------------------------------------------------------------------
    // Escrita
    // -------------------------------------------------------------------------

    /**
     * Registra uma transferência.
     *
     * @param array $data {
     *   usuario_id, related_id, related_type,
     *   de_usuario_id, para_usuario_id, motivo, observacao
     * }
     */
    public function create(array $data): string|false
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO `{$this->table}`
             (usuario_id, related_id, related_type, de_usuario_id, para_usuario_id, motivo, observacao)
             VALUES (:usuario_id, :related_id, :related_type, :de_usuario_id, :para_usuario_id, :motivo, :observacao)"
        );

        $stmt->bindValue(':usuario_id',      (int) $data['usuario_id'],      PDO::PARAM_INT);
        $stmt->bindValue(':related_id',      (int) $data['related_id'],      PDO::PARAM_INT);
        $stmt->bindValue(':related_type',    $data['related_type']);
        $stmt->bindValue(':de_usuario_id',   (int) $data['de_usuario_id'],   PDO::PARAM_INT);
        $stmt->bindValue(':para_usuario_id', (int) $data['para_usuario_id'], PDO::PARAM_INT);
        $stmt->bindValue(':motivo',          $data['motivo']);
        $stmt->bindValue(':observacao',      $data['observacao'] ?? null);

        return $stmt->execute() ? $this->pdo->lastInsertId() : false;
    }

    // -------------------------------------------------------------------------
    // Consultas
    // -------------------------------------------------------------------------

    /**
     * Retorna o histórico de transferências de um lead ou oportunidade.
     */
    public function findByRelated(string $type, int $relatedId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT t.*,
                    u_exec.name  AS executor_nome,
                    u_de.name    AS de_nome,
                    u_para.name  AS para_nome
             FROM `{$this->table}` t
             LEFT JOIN users u_exec ON u_exec.id = t.usuario_id
             LEFT JOIN users u_de   ON u_de.id   = t.de_usuario_id
             LEFT JOIN users u_para ON u_para.id = t.para_usuario_id
             WHERE t.related_type = :type AND t.related_id = :id
             ORDER BY t.created_at DESC"
        );
        $stmt->execute([':type' => $type, ':id' => $relatedId]);
        return $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];
    }

    /**
     * Retorna todas as transferências recebidas por um usuário.
     */
    public function findRecebidosPorUsuario(int $usuarioId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT t.*,
                    u_de.name   AS de_nome,
                    u_exec.name AS executor_nome
             FROM `{$this->table}` t
             LEFT JOIN users u_de   ON u_de.id   = t.de_usuario_id
             LEFT JOIN users u_exec ON u_exec.id = t.usuario_id
             WHERE t.para_usuario_id = :uid
             ORDER BY t.created_at DESC
             LIMIT 50"
        );
        $stmt->execute([':uid' => $usuarioId]);
        return $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];
    }
}
