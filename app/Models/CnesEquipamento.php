<?php
namespace App\Models;

use App\Core\Model;
use PDO;

/**
 * Model para equipamentos de estabelecimentos CNES.
 * Tabela: cnes_equipamentos
 *
 * NOTA: A busca aceita tanto co_unidade quanto co_cnes para compatibilidade
 * com diferentes versões do CSV CNES (algumas versões usam CO_CNES, outras CO_UNIDADE).
 */
class CnesEquipamento extends Model
{
    protected string $table = 'cnes_equipamentos';

    /**
     * Monta cláusula WHERE que busca por co_unidade OU co_cnes.
     * Garante que equipamentos importados por qualquer versão do CSV sejam encontrados.
     */
    private function whereEstab(string $coUnidade, string $coCnes = ''): array
    {
        if ($coCnes) {
            return [
                '(co_unidade = ? OR co_cnes = ?)',
                [$coUnidade, $coCnes],
            ];
        }
        return ['co_unidade = ?', [$coUnidade]];
    }

    /**
     * Busca equipamentos de um estabelecimento com filtros opcionais.
     *
     * @param string $coUnidade  Código da unidade (CO_UNIDADE do CNES)
     * @param array  $filtros    ['tipo' => '', 'q' => '', 'co_cnes' => '']
     */
    public function findByUnidade(string $coUnidade, array $filtros = []): array
    {
        $coCnes = $filtros['co_cnes'] ?? '';
        [$estabWhere, $estabParams] = $this->whereEstab($coUnidade, $coCnes);

        $where  = [$estabWhere];
        $params = $estabParams;

        if (!empty($filtros['tipo'])) {
            $where[]  = 'e.co_tipo_equipamento = ?';
            $params[] = $filtros['tipo'];
        }
        if (!empty($filtros['q'])) {
            $where[]  = 'e.no_equipamento LIKE ?';
            $params[] = '%' . $filtros['q'] . '%';
        }

        // Prefixar co_unidade/co_cnes com alias e
        $whereStr = str_replace('co_unidade', 'e.co_unidade', implode(' AND ', $where));
        $whereStr = str_replace('e.e.co_unidade', 'e.co_unidade', $whereStr);

        // Não faz JOIN com cnes_dom_tipo_equipamento pois a tabela pode não existir
        // O campo no_tipo_equipamento já vem preenchido na importação
        $sql = "SELECT e.*,
                       COALESCE(e.no_tipo_equipamento, e.co_tipo_equipamento) AS no_tipo_desc
                FROM {$this->table} e
                WHERE {$whereStr}
                ORDER BY e.co_tipo_equipamento, e.no_equipamento";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Busca apenas equipamentos de diagnóstico por imagem (tipo 1).
     */
    public function findImagemByUnidade(string $coUnidade, string $coCnes = ''): array
    {
        return $this->findByUnidade($coUnidade, ['tipo' => '1', 'co_cnes' => $coCnes]);
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
    public function tiposDisponiveis(string $coUnidade, string $coCnes = ''): array
    {
        [$estabWhere, $estabParams] = $this->whereEstab($coUnidade, $coCnes);
        $stmt = $this->pdo->prepare(
            "SELECT DISTINCT co_tipo_equipamento,
                    COALESCE(no_tipo_equipamento, co_tipo_equipamento) AS no_tipo_equipamento
             FROM {$this->table}
             WHERE ({$estabWhere}) AND co_tipo_equipamento IS NOT NULL
             ORDER BY co_tipo_equipamento"
        );
        $stmt->execute($estabParams);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Conta equipamentos por estabelecimento.
     */
    public function contarPorUnidade(string $coUnidade, string $coCnes = ''): int
    {
        [$estabWhere, $estabParams] = $this->whereEstab($coUnidade, $coCnes);
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM {$this->table} WHERE {$estabWhere}"
        );
        $stmt->execute($estabParams);
        return (int)$stmt->fetchColumn();
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
