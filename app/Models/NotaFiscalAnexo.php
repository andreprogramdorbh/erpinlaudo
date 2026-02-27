<?php
namespace App\Models;

use App\Core\Model;
use PDO;

/**
 * Model de Anexos de Notas Fiscais.
 * Segue o mesmo padrão de ContaPagarAnexo e ContaReceberAnexo.
 */
class NotaFiscalAnexo extends Model
{
    protected string $table = 'notas_fiscais_anexos';

    public function __construct()
    {
        parent::__construct();
        // Garante que a tabela existe ao instanciar o model
        $this->ensureTableExists();
    }

    /**
     * Verifica se a tabela existe e cria se necessário.
     */
    private function ensureTableExists(): void
    {
        try {
            $this->pdo->query("SELECT 1 FROM {$this->table} LIMIT 1");
        } catch (\PDOException $e) {
            $this->createTable();
        }
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
     * Lista todos os anexos de uma nota fiscal, verificando o tenant.
     */
    public function findByNotaId(int $notaFiscalId, int $usuarioId): array
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT * FROM {$this->table}
                  WHERE nota_fiscal_id = ? AND usuario_id = ?
                  ORDER BY id DESC"
            );
            $stmt->execute([$notaFiscalId, $usuarioId]);
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (\PDOException $e) {
            // Se a tabela ainda não existir, cria e retorna vazio
            if ($e->getCode() === '42S02' || strpos($e->getMessage(), "doesn't exist") !== false) {
                $this->createTable();
                return [];
            }
            throw $e;
        }
    }

    /**
     * Lista todos os anexos de uma nota fiscal para o portal do cliente.
     * Verifica apenas o tenant (usuario_id), pois o cliente já foi validado na nota.
     */
    public function findByNotaIdForPortal(int $notaFiscalId, int $tenantId): array
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT * FROM {$this->table}
                  WHERE nota_fiscal_id = ? AND usuario_id = ?
                  ORDER BY id ASC"
            );
            $stmt->execute([$notaFiscalId, $tenantId]);
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (\PDOException $e) {
            if ($e->getCode() === '42S02' || strpos($e->getMessage(), "doesn't exist") !== false) {
                $this->createTable();
                return [];
            }
            throw $e;
        }
    }

    /**
     * Insere um novo anexo.
     */
    public function create(array $data): string|false
    {
        $sql = "INSERT INTO {$this->table}
                    (usuario_id, nota_fiscal_id, file_path, original_name, mime_type, file_size)
                VALUES
                    (:usuario_id, :nota_fiscal_id, :file_path, :original_name, :mime_type, :file_size)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':usuario_id',     $data['usuario_id'],     PDO::PARAM_INT);
        $stmt->bindValue(':nota_fiscal_id', $data['nota_fiscal_id'], PDO::PARAM_INT);
        $stmt->bindValue(':file_path',      $data['file_path']);
        $stmt->bindValue(':original_name',  $data['original_name']);
        $stmt->bindValue(':mime_type',      $data['mime_type']  ?? null);
        $stmt->bindValue(':file_size',      $data['file_size']  ?? null);

        if ($stmt->execute()) {
            return $this->pdo->lastInsertId();
        }
        return false;
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
     * Cria a tabela caso não exista (fallback de auto-criação).
     */
    public function createTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
            id              INT AUTO_INCREMENT PRIMARY KEY,
            usuario_id      INT NOT NULL,
            nota_fiscal_id  INT NOT NULL,
            file_path       VARCHAR(500) NOT NULL,
            original_name   VARCHAR(255) NOT NULL,
            mime_type       VARCHAR(100) NULL,
            file_size       INT NULL,
            created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_nf_anexos_nota   (nota_fiscal_id),
            INDEX idx_nf_anexos_tenant (usuario_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $this->pdo->exec($sql);
    }
}
