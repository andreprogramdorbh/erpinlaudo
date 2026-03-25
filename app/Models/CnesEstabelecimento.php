<?php
namespace App\Models;

use App\Core\Model;
use PDO;

/**
 * Model para estabelecimentos CNES.
 * Tabela: cnes_estabelecimentos
 */
class CnesEstabelecimento extends Model
{
    protected string $table = 'cnes_estabelecimentos';

    /**
     * Busca estabelecimentos com filtros e paginação.
     */
    public function buscar(array $filtros = [], int $pagina = 1, int $porPagina = 50): array
    {
        $where  = ['1=1'];
        $params = [];

        if (!empty($filtros['q'])) {
            $where[]  = '(no_razao_social LIKE ? OR no_fantasia LIKE ? OR co_cnes LIKE ? OR nu_cnpj LIKE ?)';
            $busca    = '%' . $filtros['q'] . '%';
            $params[] = $busca;
            $params[] = $busca;
            $params[] = $busca;
            $params[] = $busca;
        }
        if (!empty($filtros['uf'])) {
            $where[]  = 'co_estado_gestor = ?';
            $params[] = strtoupper($filtros['uf']);
        }
        if (!empty($filtros['municipio'])) {
            $where[]  = 'co_municipio_gestor = ?';
            $params[] = $filtros['municipio'];
        }
        if (!empty($filtros['tp_unidade'])) {
            $where[]  = 'tp_unidade = ?';
            $params[] = $filtros['tp_unidade'];
        }
        if (isset($filtros['importado']) && $filtros['importado'] !== '') {
            if ($filtros['importado']) {
                $where[] = 'cliente_id IS NOT NULL';
            } else {
                $where[] = 'cliente_id IS NULL';
            }
        }

        $whereStr = implode(' AND ', $where);
        $offset   = ($pagina - 1) * $porPagina;

        // Total
        $stmtTotal = $this->pdo->prepare("SELECT COUNT(*) FROM {$this->table} WHERE {$whereStr}");
        $stmtTotal->execute($params);
        $total = (int)$stmtTotal->fetchColumn();

        // Registros
        $sql  = "SELECT * FROM {$this->table} WHERE {$whereStr} ORDER BY no_razao_social ASC LIMIT {$porPagina} OFFSET {$offset}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $registros = $stmt->fetchAll(PDO::FETCH_OBJ);

        return [
            'registros'   => $registros,
            'total'       => $total,
            'pagina'      => $pagina,
            'por_pagina'  => $porPagina,
            'total_paginas' => (int)ceil($total / $porPagina),
        ];
    }

    /**
     * Busca um estabelecimento pelo CO_CNES.
     */
    public function findByCnes(string $coCnes): ?object
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE co_cnes = ? LIMIT 1");
        $stmt->execute([trim($coCnes)]);
        return $stmt->fetch(PDO::FETCH_OBJ) ?: null;
    }

    /**
     * Busca um estabelecimento pelo CO_UNIDADE.
     */
    public function findByUnidade(string $coUnidade): ?object
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE co_unidade = ? LIMIT 1");
        $stmt->execute([trim($coUnidade)]);
        return $stmt->fetch(PDO::FETCH_OBJ) ?: null;
    }

    /**
     * Busca pelo ID interno.
     */
    public function findById(int $id): ?object
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_OBJ) ?: null;
    }

    /**
     * Vincula o estabelecimento a um cliente do ERP.
     */
    public function vincularCliente(int $id, int $clienteId): bool
    {
        $stmt = $this->pdo->prepare("UPDATE {$this->table} SET cliente_id = ? WHERE id = ?");
        return $stmt->execute([$clienteId, $id]);
    }

    /**
     * Retorna lista de UFs disponíveis na base importada.
     */
    public function listarUfs(): array
    {
        $stmt = $this->pdo->query("SELECT DISTINCT co_estado_gestor FROM {$this->table} WHERE co_estado_gestor IS NOT NULL ORDER BY co_estado_gestor");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Retorna lista de tipos de unidade disponíveis.
     */
    public function listarTiposUnidade(): array
    {
        $stmt = $this->pdo->query("SELECT DISTINCT tp_unidade FROM {$this->table} WHERE tp_unidade IS NOT NULL ORDER BY tp_unidade");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Conta total de estabelecimentos importados.
     */
    public function contarTotal(): int
    {
        return (int)$this->pdo->query("SELECT COUNT(*) FROM {$this->table}")->fetchColumn();
    }

    /**
     * Verifica se a base CNES já foi importada.
     */
    public function baseImportada(): bool
    {
        return $this->contarTotal() > 0;
    }

    /**
     * Expõe o PDO para uso direto no controller (queries específicas).
     */
    public function getPdo(): \PDO
    {
        return $this->pdo;
    }
}
