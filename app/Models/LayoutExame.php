<?php
namespace App\Models;

use App\Core\Model;
use PDO;

/**
 * Model para a tabela layout_exames.
 *
 * Histórico de alterações:
 * - 2026-05-11: Adicionados métodos allByUser(), insert() e corrigida assinatura
 *   de update(id, userId, data) para compatibilidade com PerfilController.
 *   Adicionado suporte às colunas individuais (separador, linha_cabecalho,
 *   col_medico, col_crm, etc.) via ALTER TABLE idempotente.
 *   Mantida retrocompatibilidade com ContratosController (mapeamento_json).
 */
class LayoutExame extends Model
{
    protected string $table = 'layout_exames';

    /** Colunas individuais adicionadas ao redesign do formulário de perfil */
    private const COLUNAS_EXTRAS = [
        'separador'             => "VARCHAR(5)   NOT NULL DEFAULT ';'",
        'linha_cabecalho'       => "TINYINT      NOT NULL DEFAULT 1",
        'col_medico'            => "VARCHAR(100) DEFAULT NULL",
        'col_crm'               => "VARCHAR(100) DEFAULT NULL",
        'col_modalidade'        => "VARCHAR(100) DEFAULT NULL",
        'col_study_description' => "VARCHAR(100) DEFAULT NULL",
        'col_prioridade'        => "VARCHAR(100) DEFAULT NULL",
        'col_data_conclusao'    => "VARCHAR(100) DEFAULT NULL",
        'col_paciente'          => "VARCHAR(100) DEFAULT NULL",
        'col_paciente_id'       => "VARCHAR(100) DEFAULT NULL",
        'col_unidade'           => "VARCHAR(100) DEFAULT NULL",
        'col_accession'         => "VARCHAR(100) DEFAULT NULL",
        'col_convenio'          => "VARCHAR(100) DEFAULT NULL",
        'col_valor_exame'       => "VARCHAR(100) DEFAULT NULL",
        'col_revisor'           => "VARCHAR(100) DEFAULT NULL",
        'col_data_revisao'      => "VARCHAR(100) DEFAULT NULL",
        'valores_urgencia'      => "VARCHAR(255) DEFAULT 'URGENTE,U,URGENT'",
        'formato_data'          => "VARCHAR(50)  DEFAULT 'd/m/Y H:i'",
    ];

    public function __construct()
    {
        parent::__construct();
        $this->garantirColunas();
    }

    /**
     * Garante que as colunas extras existam na tabela (idempotente).
     * Executa ALTER TABLE ADD COLUMN apenas se a coluna ainda não existir.
     */
    private function garantirColunas(): void
    {
        try {
            $stmt = $this->pdo->query("SHOW COLUMNS FROM `{$this->table}`");
            $existentes = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'Field');
            foreach (self::COLUNAS_EXTRAS as $col => $def) {
                if (!in_array($col, $existentes, true)) {
                    $this->pdo->exec("ALTER TABLE `{$this->table}` ADD COLUMN `{$col}` {$def}");
                }
            }
        } catch (\Throwable $e) {
            // Falha silenciosa: não bloqueia a requisição se o banco estiver indisponível
            error_log('[LayoutExame::garantirColunas] ' . $e->getMessage());
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Leitura
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Retorna todos os layouts do usuário, com total_colunas calculado.
     * Alias de findByUsuarioId() para compatibilidade com PerfilController.
     */
    public function allByUser(int $usuarioId): array
    {
        return $this->findByUsuarioId($usuarioId);
    }

    /**
     * Retorna todos os layouts do usuário ordenados por ativo DESC, nome ASC.
     * Calcula total_colunas contando quantas colunas col_* estão preenchidas.
     */
    public function findByUsuarioId(int $usuarioId): array
    {
        $colCols = implode(', ', array_map(fn($c) => "`{$c}`", array_keys(self::COLUNAS_EXTRAS)));
        $stmt = $this->pdo->prepare(
            "SELECT *, {$this->totalColunasExpr()} AS total_colunas
               FROM `{$this->table}`
              WHERE usuario_id = ?
              ORDER BY ativo DESC, nome ASC"
        );
        $stmt->execute([$usuarioId]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function findById(int $id): object|false
    {
        $stmt = $this->pdo->prepare(
            "SELECT *, {$this->totalColunasExpr()} AS total_colunas
               FROM `{$this->table}`
              WHERE id = ? LIMIT 1"
        );
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function findAtivo(int $usuarioId): object|false
    {
        $stmt = $this->pdo->prepare(
            "SELECT *, {$this->totalColunasExpr()} AS total_colunas
               FROM `{$this->table}`
              WHERE usuario_id = ? AND ativo = 1
              ORDER BY id DESC LIMIT 1"
        );
        $stmt->execute([$usuarioId]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Escrita
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Insere um novo layout.
     * Alias de create() para compatibilidade com PerfilController.
     */
    public function insert(array $data): string|false
    {
        return $this->create($data);
    }

    /**
     * Cria um novo layout.
     * Aceita tanto o formato antigo (mapeamento_json) quanto o novo
     * (colunas individuais col_medico, separador, etc.).
     */
    public function create(array $data): string|false
    {
        $campos = $this->camposParaSalvar($data);
        $colunas  = implode(', ', array_map(fn($c) => "`{$c}`", array_keys($campos)));
        $binds    = implode(', ', array_map(fn($c) => ":{$c}", array_keys($campos)));
        $sql = "INSERT INTO `{$this->table}` ({$colunas}) VALUES ({$binds})";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->prefixarBinds($campos));
        return $this->pdo->lastInsertId() ?: false;
    }

    /**
     * Atualiza um layout existente.
     * Aceita 2 assinaturas:
     *   update(int $id, array $data)           — formato antigo (ContratosController)
     *   update(int $id, int $userId, array $data) — formato novo (PerfilController)
     */
    public function update(int $id, int|array $userIdOrData, array $data = []): bool
    {
        // Normalizar assinatura
        if (is_array($userIdOrData)) {
            // Chamada antiga: update($id, $data)
            $data   = $userIdOrData;
            $userId = (int)($data['usuario_id'] ?? 0);
        } else {
            // Chamada nova: update($id, $userId, $data)
            $userId = $userIdOrData;
        }

        $campos = $this->camposParaSalvar($data);
        // Remover usuario_id do SET (não deve ser alterado)
        unset($campos['usuario_id']);

        $set = implode(', ', array_map(fn($c) => "`{$c}` = :{$c}", array_keys($campos)));
        $sql = "UPDATE `{$this->table}` SET {$set}, updated_at = NOW()
                 WHERE id = :id";
        // Se userId informado, adicionar restrição de segurança
        if ($userId > 0) {
            $sql .= " AND usuario_id = :usuario_id";
        }

        $params = $this->prefixarBinds($campos);
        $params[':id'] = $id;
        if ($userId > 0) {
            $params[':usuario_id'] = $userId;
        }

        return $this->pdo->prepare($sql)->execute($params);
    }

    public function delete(int $id, int $usuarioId): bool
    {
        return $this->pdo->prepare(
            "DELETE FROM `{$this->table}` WHERE id = ? AND usuario_id = ?"
        )->execute([$id, $usuarioId]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers privados
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Retorna a expressão SQL para calcular total_colunas
     * (conta quantas colunas col_* estão preenchidas).
     */
    private function totalColunasExpr(): string
    {
        $colCols = ['col_medico','col_crm','col_modalidade','col_study_description',
                    'col_prioridade','col_data_conclusao','col_paciente','col_paciente_id',
                    'col_unidade','col_accession','col_convenio','col_valor_exame',
                    'col_revisor','col_data_revisao'];
        $parts = array_map(fn($c) => "IF(`{$c}` IS NOT NULL AND `{$c}` <> '', 1, 0)", $colCols);
        return '(' . implode(' + ', $parts) . ')';
    }

    /**
     * Monta o array de campos a salvar, suportando os dois formatos:
     * - Novo: colunas individuais (separador, col_medico, etc.)
     * - Antigo: mapeamento_json
     */
    private function camposParaSalvar(array $data): array
    {
        $campos = [
            'usuario_id' => $data['usuario_id'] ?? null,
            'nome'       => $data['nome']        ?? null,
            'ativo'      => $data['ativo']        ?? 1,
        ];

        // Formato antigo (ContratosController) — mapeamento_json
        if (isset($data['mapeamento_json'])) {
            $campos['descricao']       = $data['descricao']       ?? null;
            $campos['formato']         = $data['formato']         ?? 'xlsx';
            $campos['mapeamento_json'] = $data['mapeamento_json'];
        }

        // Formato novo (PerfilController) — colunas individuais
        foreach (array_keys(self::COLUNAS_EXTRAS) as $col) {
            if (array_key_exists($col, $data)) {
                $campos[$col] = $data[$col];
            }
        }

        // Remover nulos de campos obrigatórios
        return array_filter($campos, fn($v) => $v !== null);
    }

    /**
     * Prefixa as chaves do array com ':' para uso em PDO::execute().
     */
    private function prefixarBinds(array $campos): array
    {
        $result = [];
        foreach ($campos as $col => $val) {
            $result[":{$col}"] = $val;
        }
        return $result;
    }
}
