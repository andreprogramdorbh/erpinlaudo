<?php
namespace App\Models;

use App\Core\Model;
use PDO;

class Contrato extends Model
{
    protected string $table = 'contratos';

    public function findById(int $id): object|false
    {
        $sql = "SELECT c.*,
                       m.nome AS medico_nome, m.crm AS medico_crm,
                       cl.razao_social AS cliente_nome
                FROM {$this->table} c
                LEFT JOIN medicos m ON m.id = c.medico_id
                LEFT JOIN clientes cl ON cl.id = c.cliente_id
                WHERE c.id = :id LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function findByUsuarioId(int $usuarioId, array $filtros = []): array
    {
        $where  = ['c.usuario_id = :usuario_id'];
        $params = [':usuario_id' => $usuarioId];

        if (!empty($filtros['tipo_parte'])) {
            $where[] = 'c.tipo_parte = :tipo_parte';
            $params[':tipo_parte'] = $filtros['tipo_parte'];
        }
        if (!empty($filtros['status'])) {
            $where[] = 'c.status = :status';
            $params[':status'] = $filtros['status'];
        }
        if (!empty($filtros['q'])) {
            $where[] = '(c.nome LIKE :q OR c.numero LIKE :q OR m.nome LIKE :q OR cl.razao_social LIKE :q)';
            $params[':q'] = '%' . $filtros['q'] . '%';
        }

        $sql = "SELECT c.*,
                       m.nome AS medico_nome, m.crm AS medico_crm,
                       cl.razao_social AS cliente_nome
                FROM {$this->table} c
                LEFT JOIN medicos m ON m.id = c.medico_id
                LEFT JOIN clientes cl ON cl.id = c.cliente_id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY c.created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function create(array $data): string|false
    {
        $sql = "INSERT INTO {$this->table}
                (usuario_id, numero, nome, tipo_parte, medico_id, cliente_id,
                 data_inicio, data_fim, vigencia_tipo, recorrencia, valor, observacoes, status)
                VALUES
                (:usuario_id, :numero, :nome, :tipo_parte, :medico_id, :cliente_id,
                 :data_inicio, :data_fim, :vigencia_tipo, :recorrencia, :valor, :observacoes, :status)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':usuario_id'   => $data['usuario_id'],
            ':numero'       => $data['numero'],
            ':nome'         => $data['nome'],
            ':tipo_parte'   => $data['tipo_parte'],
            ':medico_id'    => $data['medico_id'] ?: null,
            ':cliente_id'   => $data['cliente_id'] ?: null,
            ':data_inicio'  => $data['data_inicio'],
            ':data_fim'     => $data['data_fim'] ?: null,
            ':vigencia_tipo'=> $data['vigencia_tipo'] ?? 'determinado',
            ':recorrencia'  => $data['recorrencia'] ?? 'mensal',
            ':valor'        => $data['valor'] ?? 0,
            ':observacoes'  => $data['observacoes'] ?? null,
            ':status'       => $data['status'] ?? 'ativo',
        ]);
        return $this->pdo->lastInsertId() ?: false;
    }

    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE {$this->table} SET
                nome = :nome, tipo_parte = :tipo_parte,
                medico_id = :medico_id, cliente_id = :cliente_id,
                data_inicio = :data_inicio, data_fim = :data_fim,
                vigencia_tipo = :vigencia_tipo, recorrencia = :recorrencia,
                valor = :valor, observacoes = :observacoes, status = :status,
                updated_at = NOW()
                WHERE id = :id AND usuario_id = :usuario_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':nome'         => $data['nome'],
            ':tipo_parte'   => $data['tipo_parte'],
            ':medico_id'    => $data['medico_id'] ?: null,
            ':cliente_id'   => $data['cliente_id'] ?: null,
            ':data_inicio'  => $data['data_inicio'],
            ':data_fim'     => $data['data_fim'] ?: null,
            ':vigencia_tipo'=> $data['vigencia_tipo'] ?? 'determinado',
            ':recorrencia'  => $data['recorrencia'] ?? 'mensal',
            ':valor'        => $data['valor'] ?? 0,
            ':observacoes'  => $data['observacoes'] ?? null,
            ':status'       => $data['status'] ?? 'ativo',
            ':id'           => $id,
            ':usuario_id'   => $data['usuario_id'],
        ]);
    }

    public function delete(int $id, int $usuarioId): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE id = ? AND usuario_id = ?");
        return $stmt->execute([$id, $usuarioId]);
    }

    public function gerarNumero(): string
    {
        return 'CTR-' . date('Y') . '-' . strtoupper(substr(uniqid(), -6));
    }

    // Modalidades vinculadas ao contrato
    public function getModalidades(int $contratoId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT cm.*, te.nome AS exame_nome
             FROM contrato_modalidades cm
             LEFT JOIN tabela_exames te ON te.id = cm.exame_id
             WHERE cm.contrato_id = ?"
        );
        $stmt->execute([$contratoId]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function saveModalidades(int $contratoId, array $modalidades): void
    {
        $this->pdo->prepare("DELETE FROM contrato_modalidades WHERE contrato_id = ?")->execute([$contratoId]);
        if (empty($modalidades)) return;
        $stmt = $this->pdo->prepare(
            "INSERT INTO contrato_modalidades (contrato_id, modalidade, exame_id) VALUES (?, ?, ?)"
        );
        foreach ($modalidades as $mod) {
            $stmt->execute([$contratoId, $mod['modalidade'], $mod['exame_id'] ?: null]);
        }
    }

    public function countByUsuarioId(int $usuarioId): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM {$this->table} WHERE usuario_id = ?");
        $stmt->execute([$usuarioId]);
        return (int) $stmt->fetchColumn();
    }
}
