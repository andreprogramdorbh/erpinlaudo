<?php
namespace App\Models;

use App\Core\Model;
use PDO;

/**
 * Model para anexos de documentos do CRM.
 * Tabela: crm_anexos
 *
 * Suporta anexos vinculados a Leads e Oportunidades.
 */
class CrmAnexo extends Model
{
    protected string $table = 'crm_anexos';

    /**
     * Tipos de documento disponíveis.
     */
    public const TIPOS = [
        'contrato'           => 'Contrato',
        'termo_aceite'       => 'Termo de Aceite',
        'proposta_comercial' => 'Proposta Comercial',
        'edital'             => 'Edital',
        'outro'              => 'Outro',
    ];

    /**
     * Ícones por tipo de documento.
     */
    public const ICONES = [
        'contrato'           => 'fa-file-signature text-primary',
        'termo_aceite'       => 'fa-file-check text-success',
        'proposta_comercial' => 'fa-file-invoice-dollar text-warning',
        'edital'             => 'fa-gavel text-danger',
        'outro'              => 'fa-paperclip text-muted',
    ];

    /**
     * Busca todos os anexos de um lead ou oportunidade.
     *
     * @param string $type     'lead' ou 'oportunidade'
     * @param int    $relatedId ID do registro
     */
    public function findByRelated(string $type, int $relatedId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT a.*, u.name AS usuario_nome
             FROM {$this->table} a
             LEFT JOIN users u ON u.id = a.usuario_id
             WHERE a.related_type = ? AND a.related_id = ?
             ORDER BY a.created_at DESC"
        );
        $stmt->execute([$type, $relatedId]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Busca um anexo pelo ID.
     */
    public function findById(int $id): object|false
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    /**
     * Cria um novo anexo.
     */
    public function create(array $data): string|false
    {
        $sql = "INSERT INTO {$this->table}
                (usuario_id, related_type, related_id, nome_documento, tipo_documento,
                 file_path, original_name, mime_type, file_size)
                VALUES
                (:usuario_id, :related_type, :related_id, :nome_documento, :tipo_documento,
                 :file_path, :original_name, :mime_type, :file_size)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':usuario_id',      (int) $data['usuario_id'],   PDO::PARAM_INT);
        $stmt->bindValue(':related_type',    $data['related_type']);
        $stmt->bindValue(':related_id',      (int) $data['related_id'],   PDO::PARAM_INT);
        $stmt->bindValue(':nome_documento',  $data['nome_documento']);
        $stmt->bindValue(':tipo_documento',  $data['tipo_documento']);
        $stmt->bindValue(':file_path',       $data['file_path']);
        $stmt->bindValue(':original_name',   $data['original_name']);
        $stmt->bindValue(':mime_type',       $data['mime_type'] ?? null);
        $stmt->bindValue(':file_size',       $data['file_size'] ?? null, PDO::PARAM_INT);

        return $stmt->execute() ? $this->pdo->lastInsertId() : false;
    }

    /**
     * Remove um anexo pelo ID.
     */
    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Conta anexos por registro.
     */
    public function countByRelated(string $type, int $relatedId): int
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM {$this->table} WHERE related_type = ? AND related_id = ?"
        );
        $stmt->execute([$type, $relatedId]);
        return (int) $stmt->fetchColumn();
    }
}
