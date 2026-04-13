<?php
namespace App\Models;

use App\Core\Database;

/**
 * Model ContratoExame
 *
 * Gerencia os exames vinculados a um contrato com possibilidade de override
 * dos valores da tabela de exames. Quando usa_valor_custom = 1, os valores
 * do contrato são a base contábil em todas as apurações.
 *
 * Para contratos de médico (prestador): valor_rotina e valor_urgencia
 * Para contratos de cliente: valor_venda_rotina e valor_venda_urgencia
 */
class ContratoExame
{
    private \PDO $db;
    private string $table = 'contrato_exames';

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Retorna todos os exames vinculados a um contrato,
     * com os dados da tabela de exames para comparação.
     */
    public function findByContratoId(int $contratoId): array
    {
        $sql = "
            SELECT
                ce.*,
                te.nome_exame,
                te.modalidade,
                te.valor_rotina          AS tabela_valor_rotina,
                te.valor_urgencia        AS tabela_valor_urgencia,
                te.valor_venda_rotina    AS tabela_valor_venda_rotina,
                te.valor_venda_urgencia  AS tabela_valor_venda_urgencia,
                te.preco_venda           AS tabela_preco_venda
            FROM {$this->table} ce
            INNER JOIN tabela_exames te ON te.id = ce.tabela_exame_id
            WHERE ce.contrato_id = :contrato_id
            ORDER BY te.modalidade ASC, te.nome_exame ASC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':contrato_id' => $contratoId]);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /**
     * Retorna mapa indexado por tabela_exame_id para uso no motor de apuração.
     * Apenas retorna entradas onde usa_valor_custom = 1.
     */
    public function findMapByContratoId(int $contratoId): array
    {
        $sql = "
            SELECT
                ce.tabela_exame_id,
                ce.valor_rotina,
                ce.valor_urgencia,
                ce.valor_venda_rotina,
                ce.valor_venda_urgencia,
                ce.usa_valor_custom,
                te.nome_exame,
                te.modalidade
            FROM {$this->table} ce
            INNER JOIN tabela_exames te ON te.id = ce.tabela_exame_id
            WHERE ce.contrato_id = :contrato_id
              AND ce.usa_valor_custom = 1
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':contrato_id' => $contratoId]);
        $rows = $stmt->fetchAll(\PDO::FETCH_OBJ);

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row->tabela_exame_id] = $row;
        }
        return $map;
    }

    /**
     * Upsert: insere ou atualiza o vínculo de um exame com um contrato.
     */
    public function upsert(array $data): bool
    {
        $sql = "
            INSERT INTO {$this->table}
                (usuario_id, contrato_id, tabela_exame_id,
                 valor_rotina, valor_urgencia,
                 valor_venda_rotina, valor_venda_urgencia,
                 usa_valor_custom, observacoes)
            VALUES
                (:usuario_id, :contrato_id, :tabela_exame_id,
                 :valor_rotina, :valor_urgencia,
                 :valor_venda_rotina, :valor_venda_urgencia,
                 :usa_valor_custom, :observacoes)
            ON DUPLICATE KEY UPDATE
                valor_rotina           = VALUES(valor_rotina),
                valor_urgencia         = VALUES(valor_urgencia),
                valor_venda_rotina     = VALUES(valor_venda_rotina),
                valor_venda_urgencia   = VALUES(valor_venda_urgencia),
                usa_valor_custom       = VALUES(usa_valor_custom),
                observacoes            = VALUES(observacoes),
                updated_at             = CURRENT_TIMESTAMP
        ";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':usuario_id'           => (int) $data['usuario_id'],
            ':contrato_id'          => (int) $data['contrato_id'],
            ':tabela_exame_id'      => (int) $data['tabela_exame_id'],
            ':valor_rotina'         => (float) ($data['valor_rotina'] ?? 0),
            ':valor_urgencia'       => (float) ($data['valor_urgencia'] ?? 0),
            ':valor_venda_rotina'   => (float) ($data['valor_venda_rotina'] ?? 0),
            ':valor_venda_urgencia' => (float) ($data['valor_venda_urgencia'] ?? 0),
            ':usa_valor_custom'     => (int) ($data['usa_valor_custom'] ?? 0),
            ':observacoes'          => $data['observacoes'] ?? null,
        ]);
    }

    /**
     * Remove um exame específico de um contrato.
     */
    public function deleteByContratoAndExame(int $contratoId, int $tabelaExameId): bool
    {
        $stmt = $this->db->prepare(
            "DELETE FROM {$this->table}
             WHERE contrato_id = :contrato_id AND tabela_exame_id = :tabela_exame_id"
        );
        return $stmt->execute([
            ':contrato_id'     => $contratoId,
            ':tabela_exame_id' => $tabelaExameId,
        ]);
    }

    /**
     * Remove pelo ID do registro.
     */
    public function deleteById(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Remove todos os exames de um contrato.
     */
    public function deleteAllByContrato(int $contratoId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE contrato_id = :contrato_id");
        return $stmt->execute([':contrato_id' => $contratoId]);
    }

    /**
     * Busca um registro específico pelo ID.
     */
    public function findById(int $id): ?\stdClass
    {
        $stmt = $this->db->prepare(
            "SELECT ce.*, te.nome_exame, te.modalidade
             FROM {$this->table} ce
             INNER JOIN tabela_exames te ON te.id = ce.tabela_exame_id
             WHERE ce.id = :id LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(\PDO::FETCH_OBJ);
        return $row ?: null;
    }
}
