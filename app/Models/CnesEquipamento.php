<?php
namespace App\Models;

use App\Core\Model;
use PDO;

/**
 * Model para equipamentos de estabelecimentos CNES.
 * Tabela: cnes_equipamentos
 */
class CnesEquipamento extends Model
{
    protected string $table = 'cnes_equipamentos';

    /**
     * Busca equipamentos de um estabelecimento.
     */
    public function findByUnidade(string $coUnidade, array $filtros = []): array
    {
        $where  = ['co_unidade = ?'];
        $params = [$coUnidade];

        if (!empty($filtros['tipo'])) {
            $where[]  = 'co_tipo_equipamento = ?';
            $params[] = $filtros['tipo'];
        }
        if (!empty($filtros['q'])) {
            $where[]  = 'no_equipamento LIKE ?';
            $params[] = '%' . $filtros['q'] . '%';
        }

        $whereStr = implode(' AND ', $where);
        $sql = "SELECT e.*,
                       COALESCE(e.no_tipo_equipamento, t.no_tipo) AS no_tipo_desc
                FROM {$this->table} e
                LEFT JOIN cnes_dom_tipo_equipamento t ON t.co_tipo = e.co_tipo_equipamento
                WHERE {$whereStr}
                ORDER BY e.co_tipo_equipamento, e.no_equipamento";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Busca apenas equipamentos de diagnóstico por imagem (tipo 1).
     */
    public function findImagemByUnidade(string $coUnidade): array
    {
        return $this->findByUnidade($coUnidade, ['tipo' => '1']);
    }

    /**
     * Atualiza campos extras de um equipamento (fabricante, modelo, ano_instalacao).
     */
    public function atualizarExtras(int $id, array $dados): bool
    {
        $campos = [];
        $params = [];
        $permitidos = ['fabricante', 'modelo', 'ano_instalacao', 'observacoes'];

        foreach ($permitidos as $campo) {
            if (array_key_exists($campo, $dados)) {
                $campos[] = "{$campo} = ?";
                $params[] = $dados[$campo] ?: null;
            }
        }
        if (empty($campos)) return false;

        $params[] = $id;
        $sql = "UPDATE {$this->table} SET " . implode(', ', $campos) . " WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Retorna tipos de equipamento disponíveis para um estabelecimento.
     */
    public function tiposDisponiveis(string $coUnidade): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT DISTINCT co_tipo_equipamento, no_tipo_equipamento
             FROM {$this->table}
             WHERE co_unidade = ? AND co_tipo_equipamento IS NOT NULL
             ORDER BY co_tipo_equipamento"
        );
        $stmt->execute([$coUnidade]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Retorna lista de fabricantes únicos para autocomplete.
     */
    public function fabricantesUnicos(): array
    {
        $stmt = $this->pdo->query(
            "SELECT DISTINCT fabricante FROM {$this->table}
             WHERE fabricante IS NOT NULL ORDER BY fabricante LIMIT 200"
        );
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
