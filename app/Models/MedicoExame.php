<?php

namespace App\Models;

use App\Core\Database;

/**
 * Model para o vínculo entre médicos e exames da tabela de preços.
 * Permite definir valores de rotina/urgência específicos por médico.
 */
class MedicoExame
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Retorna todos os exames vinculados a um médico, com dados da tabela de exames e TAGs DICOM.
     */
    public function findByMedicoId(int $medicoId): array
    {
        $sql = "
            SELECT
                me.id,
                me.medico_id,
                me.tabela_exame_id,
                me.valor_rotina,
                me.valor_urgencia,
                me.usa_valor_custom,
                me.observacoes,
                te.nome_exame,
                te.modalidade,
                te.valor_padrao,
                te.valor_rotina  AS tabela_valor_rotina,
                te.valor_urgencia AS tabela_valor_urgencia,
                te.preco_venda,
                GROUP_CONCAT(DISTINCT tet.tag_valor ORDER BY tet.tag_valor SEPARATOR ',') AS tags_dicom_raw
            FROM medico_exames me
            INNER JOIN tabela_exames te ON te.id = me.tabela_exame_id
            LEFT JOIN tabela_exames_tags tet ON tet.exame_id = te.id AND tet.tag_valor != ''
            WHERE me.medico_id = :medico_id
            GROUP BY me.id, me.medico_id, me.tabela_exame_id, me.valor_rotina, me.valor_urgencia,
                     me.usa_valor_custom, me.observacoes, te.nome_exame, te.modalidade,
                     te.valor_padrao, te.valor_rotina, te.valor_urgencia, te.preco_venda
            ORDER BY te.nome_exame ASC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':medico_id' => $medicoId]);
        $rows = $stmt->fetchAll(\PDO::FETCH_OBJ);

        // Converter tags_dicom_raw em array
        foreach ($rows as $row) {
            $row->tags_dicom = !empty($row->tags_dicom_raw)
                ? array_filter(array_map('trim', explode(',', $row->tags_dicom_raw)))
                : [];
        }

        return $rows;
    }

    /**
     * Retorna mapa indexado por tag_dicom_valor → dados do vínculo médico-exame.
     * Usado pelo motor de apuração para lookup rápido.
     * Formato: ['CR' => {valor_rotina, valor_urgencia, nome_exame, ...}, ...]
     */
    public function findMapByTagDicomForMedico(int $medicoId): array
    {
        $sql = "
            SELECT
                me.valor_rotina,
                me.valor_urgencia,
                me.usa_valor_custom,
                te.nome_exame,
                te.modalidade,
                te.valor_rotina  AS tabela_valor_rotina,
                te.valor_urgencia AS tabela_valor_urgencia,
                te.preco_venda,
                tet.tag_valor
            FROM medico_exames me
            INNER JOIN tabela_exames te ON te.id = me.tabela_exame_id
            INNER JOIN tabela_exames_tags tet ON tet.exame_id = te.id AND tet.tag_valor != ''
            WHERE me.medico_id = :medico_id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':medico_id' => $medicoId]);
        $rows = $stmt->fetchAll(\PDO::FETCH_OBJ);

        $map = [];
        foreach ($rows as $row) {
            $tagKey = strtoupper(trim($row->tag_valor));
            if ($tagKey !== '') {
                // Usa valor custom do médico se definido, senão usa valor da tabela
                $row->valor_rotina_efetivo = $row->usa_valor_custom
                    ? (float) $row->valor_rotina
                    : (float) $row->tabela_valor_rotina;
                $row->valor_urgencia_efetivo = $row->usa_valor_custom
                    ? (float) $row->valor_urgencia
                    : (float) $row->tabela_valor_urgencia;
                $map[$tagKey] = $row;
            }
        }

        return $map;
    }

    /**
     * Retorna mapa indexado por modalidade → dados do vínculo médico-exame.
     * Fallback quando não há TAG DICOM correspondente.
     */
    public function findMapByModalidadeForMedico(int $medicoId): array
    {
        $sql = "
            SELECT
                me.valor_rotina,
                me.valor_urgencia,
                me.usa_valor_custom,
                te.nome_exame,
                te.modalidade,
                te.valor_rotina  AS tabela_valor_rotina,
                te.valor_urgencia AS tabela_valor_urgencia,
                te.preco_venda
            FROM medico_exames me
            INNER JOIN tabela_exames te ON te.id = me.tabela_exame_id
            WHERE me.medico_id = :medico_id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':medico_id' => $medicoId]);
        $rows = $stmt->fetchAll(\PDO::FETCH_OBJ);

        $map = [];
        foreach ($rows as $row) {
            $modKey = strtoupper(trim($row->modalidade));
            if ($modKey !== '' && !isset($map[$modKey])) {
                $row->valor_rotina_efetivo = $row->usa_valor_custom
                    ? (float) $row->valor_rotina
                    : (float) $row->tabela_valor_rotina;
                $row->valor_urgencia_efetivo = $row->usa_valor_custom
                    ? (float) $row->valor_urgencia
                    : (float) $row->tabela_valor_urgencia;
                $map[$modKey] = $row;
            }
        }

        return $map;
    }

    /**
     * Salva (upsert) o vínculo de um médico com um exame da tabela.
     */
    public function upsert(array $data): bool
    {
        $sql = "
            INSERT INTO medico_exames
                (usuario_id, medico_id, tabela_exame_id, valor_rotina, valor_urgencia, usa_valor_custom, observacoes)
            VALUES
                (:usuario_id, :medico_id, :tabela_exame_id, :valor_rotina, :valor_urgencia, :usa_valor_custom, :observacoes)
            ON DUPLICATE KEY UPDATE
                valor_rotina    = VALUES(valor_rotina),
                valor_urgencia  = VALUES(valor_urgencia),
                usa_valor_custom = VALUES(usa_valor_custom),
                observacoes     = VALUES(observacoes),
                updated_at      = CURRENT_TIMESTAMP
        ";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':usuario_id'      => (int) $data['usuario_id'],
            ':medico_id'       => (int) $data['medico_id'],
            ':tabela_exame_id' => (int) $data['tabela_exame_id'],
            ':valor_rotina'    => (float) ($data['valor_rotina'] ?? 0),
            ':valor_urgencia'  => (float) ($data['valor_urgencia'] ?? 0),
            ':usa_valor_custom' => (int) ($data['usa_valor_custom'] ?? 0),
            ':observacoes'     => $data['observacoes'] ?? null,
        ]);
    }

    /**
     * Remove o vínculo de um médico com um exame específico.
     */
    public function deleteByMedicoAndExame(int $medicoId, int $tabelaExameId): bool
    {
        $stmt = $this->db->prepare(
            "DELETE FROM medico_exames WHERE medico_id = :medico_id AND tabela_exame_id = :tabela_exame_id"
        );
        return $stmt->execute([
            ':medico_id'       => $medicoId,
            ':tabela_exame_id' => $tabelaExameId,
        ]);
    }

    /**
     * Remove todos os vínculos de um médico.
     */
    public function deleteAllByMedico(int $medicoId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM medico_exames WHERE medico_id = :medico_id");
        return $stmt->execute([':medico_id' => $medicoId]);
    }
}
