<?php
namespace App\Models;

use App\Core\Model;
use PDO;

class Fornecedor extends Model
{
    protected string $table = 'fornecedores';

    // Campos permitidos para INSERT/UPDATE
    private array $allowedFields = [
        'tipo',
        'nome',
        'nome_fantasia',
        'documento',
        'email',
        'telefone',
        'celular',
        'contato_nome',
        'website',
        'cep',
        'endereco',
        'numero',
        'complemento',
        'bairro',
        'cidade',
        'estado',
        'inscricao_estadual',
        'inscricao_municipal',
        'prazo_pagamento',
        'cnae_principal',
        'descricao_cnae',
        'observacoes',
        'status',
    ];

    // ---------------------------------------------------------------
    // LEITURA
    // ---------------------------------------------------------------

    public function findById(int $id): object|false
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function findByUsuarioId(int $usuarioId, array $filtros = []): array
    {
        $where  = ['usuario_id = :usuario_id'];
        $params = [':usuario_id' => $usuarioId];

        $status = $filtros['status'] ?? 'ativo';
        if ($status !== '') {
            $where[]          = 'status = :status';
            $params[':status'] = $status;
        }

        $q = trim($filtros['pesquisa'] ?? '');
        if ($q !== '') {
            $where[]    = '(nome LIKE :q OR documento LIKE :q OR email LIKE :q)';
            $params[':q'] = '%' . $q . '%';
        }

        $sql  = "SELECT * FROM {$this->table} WHERE " . implode(' AND ', $where) . " ORDER BY nome ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    // ---------------------------------------------------------------
    // HISTORICO
    // ---------------------------------------------------------------

    /**
     * Retorna o historico completo do fornecedor:
     * - Pedidos de compra vinculados
     * - Movimentacoes de entrada via NF-e com CNPJ do fornecedor
     * - Resumo financeiro
     */
    public function getHistorico(int $fornecedorId): array
    {
        $fornecedor = $this->findById($fornecedorId);
        $cnpj       = $fornecedor ? preg_replace('/\D/', '', $fornecedor->documento ?? '') : '';

        // 1. Pedidos de compra vinculados pelo fornecedor_id
        $pedidos = $this->getPedidosCompra($fornecedorId, $cnpj);

        // 2. Movimentacoes de entrada vinculadas ao fornecedor via NF-e
        $movimentacoes = $this->getMovimentacoesEntrada($fornecedorId, $cnpj);

        // 3. Resumo financeiro
        $resumo = $this->calcularResumo($pedidos, $movimentacoes);

        return [
            'pedidos'       => $pedidos,
            'movimentacoes' => $movimentacoes,
            'resumo'        => $resumo,
        ];
    }

    private function getPedidosCompra(int $fornecedorId, string $cnpj): array
    {
        // Busca por fornecedor_id OU por CNPJ do fornecedor (para pedidos antigos)
        $sql = "
            SELECT
                pc.*,
                (SELECT COUNT(*) FROM est_pedidos_compra_itens pci WHERE pci.pedido_id = pc.id) AS total_itens
            FROM est_pedidos_compra pc
            WHERE pc.fornecedor_id = :fid
        ";
        $params = [':fid' => $fornecedorId];

        if ($cnpj !== '') {
            $sql   .= " OR pc.fornecedor_cnpj = :cnpj";
            $params[':cnpj'] = $cnpj;
        }

        $sql .= " ORDER BY pc.data_pedido DESC LIMIT 100";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (\Exception $e) {
            return [];
        }
    }

    private function getMovimentacoesEntrada(int $fornecedorId, string $cnpj): array
    {
        // Busca movimentacoes de entrada via NF-e com CNPJ do emitente = CNPJ do fornecedor
        // OU vinculadas a um pedido de compra deste fornecedor
        $sql = "
            SELECT
                m.*,
                p.nome AS produto_nome,
                p.codigo AS produto_codigo
            FROM est_movimentacoes m
            LEFT JOIN est_produtos p ON p.id = m.produto_id
            WHERE m.tipo = 'entrada'
        ";

        $conditions = [];
        $params     = [];

        if ($cnpj !== '') {
            $conditions[]    = "m.nfe_emitente_cnpj = :cnpj";
            $params[':cnpj'] = $cnpj;
        }

        // Tambem busca via pedidos de compra vinculados
        $conditions[] = "m.pedido_compra_id IN (
            SELECT id FROM est_pedidos_compra WHERE fornecedor_id = :fid
        )";
        $params[':fid'] = $fornecedorId;

        if (!empty($conditions)) {
            $sql .= " AND (" . implode(' OR ', $conditions) . ")";
        }

        $sql .= " ORDER BY m.created_at DESC LIMIT 200";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (\Exception $e) {
            return [];
        }
    }

    private function calcularResumo(array $pedidos, array $movimentacoes): array
    {
        $totalPedidos      = count($pedidos);
        $totalMovimentacoes = count($movimentacoes);
        $valorTotal        = 0.0;
        $ultimaCompra      = null;

        foreach ($pedidos as $p) {
            $valorTotal += (float)($p->valor_total ?? 0);
            if (!empty($p->data_pedido)) {
                if ($ultimaCompra === null || $p->data_pedido > $ultimaCompra) {
                    $ultimaCompra = $p->data_pedido;
                }
            }
        }

        return [
            'total_pedidos'        => $totalPedidos,
            'total_movimentacoes'  => $totalMovimentacoes,
            'valor_total_comprado' => $valorTotal,
            'ultima_compra'        => $ultimaCompra,
        ];
    }

    // ---------------------------------------------------------------
    // VERIFICACAO DE DUPLICATAS
    // ---------------------------------------------------------------

    /**
     * Verifica se ja existe um fornecedor com o mesmo documento (CNPJ/CPF)
     * para o mesmo usuario_id. Exclui o proprio registro no caso de update.
     */
    public function documentoExists(string $documento, int $usuarioId, ?int $excludeId = null): bool
    {
        $doc = preg_replace('/\D/', '', $documento);
        if ($doc === '') {
            return false;
        }
        // Compara removendo formatacao dos dois lados: banco pode ter '57.378.542/0001-10' ou '57378542000110'
        $sql    = "SELECT COUNT(*) AS total FROM {$this->table}
                   WHERE REPLACE(REPLACE(REPLACE(REPLACE(documento, '.', ''), '/', ''), '-', ''), ' ', '') = ?
                     AND usuario_id = ?";
        $params = [$doc, $usuarioId];
        if ($excludeId !== null) {
            $sql     .= ' AND id != ?';
            $params[] = $excludeId;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int)($stmt->fetch(PDO::FETCH_OBJ)->total ?? 0) > 0;
    }

    /**
     * Verifica se ja existe um fornecedor com o mesmo e-mail
     * para o mesmo usuario_id. Exclui o proprio registro no caso de update.
     */
    public function emailExists(string $email, int $usuarioId, ?int $excludeId = null): bool
    {
        $email = strtolower(trim($email));
        if ($email === '') {
            return false;
        }
        $sql    = "SELECT COUNT(*) AS total FROM {$this->table} WHERE email = ? AND usuario_id = ?";
        $params = [$email, $usuarioId];
        if ($excludeId !== null) {
            $sql     .= ' AND id != ?';
            $params[] = $excludeId;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int)($stmt->fetch(PDO::FETCH_OBJ)->total ?? 0) > 0;
    }

    // ---------------------------------------------------------------
    // ESCRITA
    // ---------------------------------------------------------------

    public function create(array $data): string|false
    {
        $fields = array_intersect_key($data, array_flip(array_merge(['usuario_id'], $this->allowedFields)));

        $cols   = implode(', ', array_keys($fields));
        $placeholders = implode(', ', array_map(fn($k) => ':' . $k, array_keys($fields)));

        $sql  = "INSERT INTO {$this->table} ({$cols}) VALUES ({$placeholders})";
        $stmt = $this->pdo->prepare($sql);

        foreach ($fields as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }

        if ($stmt->execute()) {
            return $this->pdo->lastInsertId();
        }
        return false;
    }

    public function update(int $id, array $data): bool
    {
        $fields = array_intersect_key($data, array_flip($this->allowedFields));

        if (empty($fields)) {
            return false;
        }

        $setParts = array_map(fn($k) => "{$k} = :{$k}", array_keys($fields));
        $sql      = "UPDATE {$this->table} SET " . implode(', ', $setParts) . " WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        foreach ($fields as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("UPDATE {$this->table} SET status = 'inativo' WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
