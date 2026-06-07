<?php
namespace App\Models;

use App\Core\Database;
use App\Core\Logger;

class EquipamentoCliente
{
    private \PDO   $pdo;
    private Logger $logger;
    private string $table = 'equipamentos_cliente';

    public function __construct()
    {
        $this->pdo    = Database::getInstance();
        $this->logger = new Logger();
    }

    // =========================================================================
    // Buscar ou criar equipamento por número de série + cliente
    // Previne duplicidade conforme regra de CRUD do sistema
    // =========================================================================
    public function findOrCreate(array $d): int
    {
        // Verificar duplicidade por numero_serie + usuario_id
        $stmt = $this->pdo->prepare(
            "SELECT id FROM {$this->table}
             WHERE usuario_id = :uid AND numero_serie = :serie
             LIMIT 1"
        );
        $stmt->execute([':uid' => $d['usuario_id'], ':serie' => $d['numero_serie']]);
        $row = $stmt->fetch(\PDO::FETCH_OBJ);
        if ($row) {
            // Atualizar dados se necessário
            $this->update((int)$row->id, $d);
            return (int)$row->id;
        }
        return $this->create($d);
    }

    public function create(array $d): int
    {
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO {$this->table}
                 (usuario_id, cliente_id, cliente_nome, produto_id, produto_nome,
                  produto_codigo, numero_serie, modelo, marca, data_instalacao,
                  vida_util_meses, depreciacao_mensal, observacoes)
                 VALUES
                 (:usuario_id, :cliente_id, :cliente_nome, :produto_id, :produto_nome,
                  :produto_codigo, :numero_serie, :modelo, :marca, :data_instalacao,
                  :vida_util_meses, :depreciacao_mensal, :observacoes)"
            );
            $stmt->execute([
                ':usuario_id'        => $d['usuario_id'],
                ':cliente_id'        => $d['cliente_id']        ?? null,
                ':cliente_nome'      => $d['cliente_nome']       ?? '',
                ':produto_id'        => $d['produto_id']         ?? null,
                ':produto_nome'      => $d['produto_nome']       ?? '',
                ':produto_codigo'    => $d['produto_codigo']     ?? null,
                ':numero_serie'      => $d['numero_serie']       ?? '',
                ':modelo'            => $d['modelo']             ?? null,
                ':marca'             => $d['marca']              ?? null,
                ':data_instalacao'   => $d['data_instalacao']    ?? null,
                ':vida_util_meses'   => $d['vida_util_meses']    ?? null,
                ':depreciacao_mensal'=> $d['depreciacao_mensal'] ?? null,
                ':observacoes'       => $d['observacoes']        ?? null,
            ]);
            return (int) $this->pdo->lastInsertId();
        } catch (\Throwable $e) {
            $this->logger->error('[EquipamentoCliente::create] ' . $e->getMessage());
            return 0;
        }
    }

    public function update(int $id, array $d): void
    {
        try {
            $sets   = [];
            $params = [':id' => $id];
            $campos = ['cliente_id','cliente_nome','produto_id','produto_nome','produto_codigo',
                       'modelo','marca','data_instalacao','vida_util_meses','depreciacao_mensal','observacoes','ativo'];
            foreach ($campos as $c) {
                if (array_key_exists($c, $d)) {
                    $sets[]         = "`{$c}` = :{$c}";
                    $params[":{$c}"] = $d[$c];
                }
            }
            if (empty($sets)) return;
            $this->pdo->prepare(
                "UPDATE {$this->table} SET " . implode(', ', $sets) . " WHERE id = :id"
            )->execute($params);
        } catch (\Throwable $e) {
            $this->logger->error('[EquipamentoCliente::update] ' . $e->getMessage());
        }
    }

    public function findById(int $id): object|null
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(\PDO::FETCH_OBJ) ?: null;
    }

    public function findByCliente(int $uid, int $clienteId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM {$this->table}
             WHERE usuario_id = :uid AND cliente_id = :cid AND ativo = 1
             ORDER BY produto_nome, numero_serie"
        );
        $stmt->execute([':uid' => $uid, ':cid' => $clienteId]);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    // Iniciar contador de vida útil ao faturar
    public function iniciarContador(int $id): void
    {
        try {
            $equip = $this->findById($id);
            if (!$equip) return;
            $dataInicio    = date('Y-m-d');
            $dataProxTroca = null;
            if (!empty($equip->vida_util_meses)) {
                $dataProxTroca = date('Y-m-d', strtotime("+{$equip->vida_util_meses} months"));
            }
            $this->pdo->prepare(
                "UPDATE {$this->table}
                 SET data_inicio_contador = :di, data_proxima_troca = :dt
                 WHERE id = :id"
            )->execute([':di' => $dataInicio, ':dt' => $dataProxTroca, ':id' => $id]);
        } catch (\Throwable $e) {
            $this->logger->error('[EquipamentoCliente::iniciarContador] ' . $e->getMessage());
        }
    }
}
