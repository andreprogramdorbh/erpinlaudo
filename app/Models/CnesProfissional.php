<?php
namespace App\Models;

use App\Core\Model;
use PDO;

/**
 * Model para profissionais de saúde por estabelecimento CNES.
 * Tabela: cnes_profissionais
 *
 * NOTA: A busca aceita tanto co_unidade quanto co_cnes para compatibilidade
 * com diferentes versões do CSV CNES.
 */
class CnesProfissional extends Model
{
    protected string $table = 'cnes_profissionais';

    /**
     * Monta cláusula WHERE que busca por co_unidade OU co_cnes.
     */
    private function whereEstab(string $coUnidade, string $coCnes = ''): array
    {
        if ($coCnes) {
            return [
                '(p.co_unidade = ? OR p.co_cnes = ?)',
                [$coUnidade, $coCnes],
            ];
        }
        return ['p.co_unidade = ?', [$coUnidade]];
    }

    /**
     * Busca profissionais de um estabelecimento com filtros.
     *
     * @param string $coUnidade  Código da unidade (CO_UNIDADE do CNES)
     * @param array  $filtros    ['q' => '', 'cbo' => '', 'conselho' => '', 'situacao' => '', 'co_cnes' => '']
     */
    public function findByUnidade(string $coUnidade, array $filtros = []): array
    {
        $coCnes = $filtros['co_cnes'] ?? '';
        [$estabWhere, $estabParams] = $this->whereEstab($coUnidade, $coCnes);

        $where  = [$estabWhere];
        $params = $estabParams;

        if (!empty($filtros['q'])) {
            $where[]  = '(p.no_profissional LIKE ? OR p.co_cbo LIKE ? OR COALESCE(d.no_cbo, p.no_cbo) LIKE ?)';
            $busca    = '%' . $filtros['q'] . '%';
            $params[] = $busca;
            $params[] = $busca;
            $params[] = $busca;
        }
        if (!empty($filtros['cbo'])) {
            $where[]  = 'p.co_cbo = ?';
            $params[] = $filtros['cbo'];
        }
        if (!empty($filtros['conselho'])) {
            $where[]  = 'p.co_conselho_classe = ?';
            $params[] = $filtros['conselho'];
        }
        if (!empty($filtros['situacao'])) {
            $where[]  = 'p.situacao = ?';
            $params[] = $filtros['situacao'];
        }

        $whereStr = implode(' AND ', $where);

        // LEFT JOIN com cnes_dom_cbo para obter a descrição do CBO
        // COALESCE prioriza: 1) tabela dom, 2) campo no_cbo importado, 3) código numérico
        $sql = "SELECT p.*,
                       COALESCE(d.no_cbo, p.no_cbo, p.co_cbo) AS no_cbo_desc,
                       COALESCE(p.no_conselho_classe, p.co_conselho_classe) AS no_conselho_desc
                FROM {$this->table} p
                LEFT JOIN cnes_dom_cbo d ON d.co_cbo = p.co_cbo
                WHERE {$whereStr}
                ORDER BY p.no_profissional ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Atualiza campos extras de um profissional (email, contato, observacoes).
     */
    public function atualizarContato(int $id, array $dados): bool
    {
        $campos = [];
        $params = [];
        $permitidos = ['email', 'contato', 'observacoes', 'situacao'];

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
     * Retorna lista de CBOs disponíveis para um estabelecimento,
     * com descrição da tabela de domínio.
     */
    public function cbosDisponiveis(string $coUnidade, string $coCnes = ''): array
    {
        [$estabWhere, $estabParams] = $this->whereEstab($coUnidade, $coCnes);
        $stmt = $this->pdo->prepare(
            "SELECT DISTINCT p.co_cbo,
                    COALESCE(d.no_cbo, p.no_cbo, p.co_cbo) AS no_cbo
             FROM {$this->table} p
             LEFT JOIN cnes_dom_cbo d ON d.co_cbo = p.co_cbo
             WHERE ({$estabWhere}) AND p.co_cbo IS NOT NULL
             ORDER BY no_cbo"
        );
        $stmt->execute($estabParams);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Conta profissionais por estabelecimento.
     */
    public function contarPorUnidade(string $coUnidade, string $coCnes = ''): int
    {
        [$estabWhere, $estabParams] = $this->whereEstab($coUnidade, $coCnes);
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM {$this->table} p WHERE {$estabWhere}"
        );
        $stmt->execute($estabParams);
        return (int)$stmt->fetchColumn();
    }
}
