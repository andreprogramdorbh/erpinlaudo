<?php
namespace App\Models;

use App\Core\Model;
use PDO;

class ApuracaoItem extends Model
{
    protected string $table = 'apuracao_itens';

    public function findByApuracaoId(int $apuracaoId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT ai.*, te.nome AS exame_nome_tabela
             FROM {$this->table} ai
             LEFT JOIN tabela_exames te ON te.id = ai.exame_id
             WHERE ai.apuracao_id = ?
             ORDER BY ai.linha_original ASC"
        );
        $stmt->execute([$apuracaoId]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function insertBatch(array $itens): int
    {
        if (empty($itens)) return 0;
        $sql = "INSERT INTO {$this->table}
                (apuracao_id, linha_original, unidade, medico_nome, medico_crm, revisor, data_revisao,
                 modalidade, study_description, paciente_nome, paciente_id, prioridade, origem,
                 registro, data_estudo, data_conclusao, sla, accession_number, visita, convenio,
                 valor_importado, valor_exame_import, exame_id, valor_calculado, tipo_prioridade, status_item, obs_item)
                VALUES
                (:apuracao_id, :linha_original, :unidade, :medico_nome, :medico_crm, :revisor, :data_revisao,
                 :modalidade, :study_description, :paciente_nome, :paciente_id, :prioridade, :origem,
                 :registro, :data_estudo, :data_conclusao, :sla, :accession_number, :visita, :convenio,
                 :valor_importado, :valor_exame_import, :exame_id, :valor_calculado, :tipo_prioridade, :status_item, :obs_item)";
        $stmt = $this->pdo->prepare($sql);
        $count = 0;
        foreach ($itens as $item) {
            $stmt->execute($item);
            $count++;
        }
        return $count;
    }

    public function deleteByApuracaoId(int $apuracaoId): void
    {
        $this->pdo->prepare("DELETE FROM {$this->table} WHERE apuracao_id = ?")->execute([$apuracaoId]);
    }

    public function countByApuracaoId(int $apuracaoId): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM {$this->table} WHERE apuracao_id = ?");
        $stmt->execute([$apuracaoId]);
        return (int) $stmt->fetchColumn();
    }
}
