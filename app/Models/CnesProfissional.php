<?php
namespace App\Models;

use App\Core\Model;
use PDO;

/**
 * Model para profissionais de saúde por estabelecimento CNES.
 * Tabela: cnes_profissionais
 */
class CnesProfissional extends Model
{
    protected string $table = 'cnes_profissionais';

    /**
     * Busca profissionais de um estabelecimento com filtros.
     */
    public function findByUnidade(string $coUnidade, array $filtros = []): array
    {
        $where  = ['p.co_unidade = ?'];
        $params = [$coUnidade];

        if (!empty($filtros['q'])) {
            $where[]  = '(p.no_profissional LIKE ? OR p.co_cbo LIKE ? OR p.no_cbo LIKE ?)';
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
        $sql = "SELECT p.*,
                       COALESCE(p.no_cbo, c.no_cbo) AS no_cbo_desc,
                       COALESCE(p.no_conselho_classe, cs.no_conselho) AS no_conselho_desc
                FROM {$this->table} p
                LEFT JOIN cnes_dom_cbo c ON c.co_cbo = p.co_cbo
                LEFT JOIN cnes_dom_conselho cs ON cs.co_conselho = p.co_conselho_classe
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
     * Retorna lista de CBOs disponíveis para um estabelecimento.
     */
    public function cbosDisponiveis(string $coUnidade): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT DISTINCT p.co_cbo,
                    COALESCE(p.no_cbo, c.no_cbo, p.co_cbo) AS no_cbo
             FROM {$this->table} p
             LEFT JOIN cnes_dom_cbo c ON c.co_cbo = p.co_cbo
             WHERE p.co_unidade = ? AND p.co_cbo IS NOT NULL
             ORDER BY no_cbo"
        );
        $stmt->execute([$coUnidade]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Conta profissionais por estabelecimento.
     */
    public function contarPorUnidade(string $coUnidade): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM {$this->table} WHERE co_unidade = ?");
        $stmt->execute([$coUnidade]);
        return (int)$stmt->fetchColumn();
    }
}
