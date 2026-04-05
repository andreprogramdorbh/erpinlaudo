<?php
namespace App\Models;

use App\Core\Model;
use PDO;

class Apuracao extends Model
{
    protected string $table = 'apuracoes';

    public function findById(int $id): object|false
    {
        $sql = "SELECT a.*,
                       m.nome AS medico_nome, m.crm AS medico_crm,
                       cl.razao_social AS cliente_nome,
                       c.nome AS contrato_nome, c.numero AS contrato_numero
                FROM {$this->table} a
                LEFT JOIN medicos m ON m.id = a.medico_id
                LEFT JOIN clientes cl ON cl.id = a.cliente_id
                LEFT JOIN contratos c ON c.id = a.contrato_id
                WHERE a.id = :id LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function findByUsuarioId(int $usuarioId, array $filtros = []): array
    {
        $where  = ['a.usuario_id = :usuario_id'];
        $params = [':usuario_id' => $usuarioId];
        if (!empty($filtros['contrato_id_raw'])) {
            $where[] = 'a.contrato_id = :contrato_id';
            $params[':contrato_id'] = (int) $filtros['contrato_id_raw'];
        }
        if (!empty($filtros['tipo'])) {
            $where[] = 'a.tipo = :tipo';
            $params[':tipo'] = $filtros['tipo'];
        }
        if (!empty($filtros['status'])) {
            $where[] = 'a.status = :status';
            $params[':status'] = $filtros['status'];
        }
        if (!empty($filtros['medico_id'])) {
            $where[] = 'a.medico_id = :medico_id';
            $params[':medico_id'] = $filtros['medico_id'];
        }
        if (!empty($filtros['cliente_id'])) {
            $where[] = 'a.cliente_id = :cliente_id';
            $params[':cliente_id'] = $filtros['cliente_id'];
        }
        if (!empty($filtros['periodo_inicio'])) {
            $where[] = 'a.periodo_inicio >= :periodo_inicio';
            $params[':periodo_inicio'] = $filtros['periodo_inicio'];
        }
        if (!empty($filtros['periodo_fim'])) {
            $where[] = 'a.periodo_fim <= :periodo_fim';
            $params[':periodo_fim'] = $filtros['periodo_fim'];
        }
        $sql = "SELECT a.*,
                       m.nome AS medico_nome, m.crm AS medico_crm,
                       cl.razao_social AS cliente_nome,
                       c.nome AS contrato_nome, c.numero AS contrato_numero
                FROM {$this->table} a
                LEFT JOIN medicos m ON m.id = a.medico_id
                LEFT JOIN clientes cl ON cl.id = a.cliente_id
                LEFT JOIN contratos c ON c.id = a.contrato_id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY a.created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function create(array $data): string|false
    {
        $sql = "INSERT INTO {$this->table}
                (usuario_id, contrato_id, numero, tipo, medico_id, cliente_id,
                 periodo_inicio, periodo_fim, total_exames, total_normal, total_urgencia,
                 valor_total, valor_venda_total, status, origem, arquivo_import, log_execucao)
                VALUES
                (:usuario_id, :contrato_id, :numero, :tipo, :medico_id, :cliente_id,
                 :periodo_inicio, :periodo_fim, :total_exames, :total_normal, :total_urgencia,
                 :valor_total, :valor_venda_total, :status, :origem, :arquivo_import, :log_execucao)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':usuario_id'     => $data['usuario_id'],
            ':contrato_id'    => $data['contrato_id'],
            ':numero'         => $data['numero'],
            ':tipo'           => $data['tipo'],
            ':medico_id'      => $data['medico_id'] ?: null,
            ':cliente_id'     => $data['cliente_id'] ?: null,
            ':periodo_inicio' => $data['periodo_inicio'] ?? null,
            ':periodo_fim'    => $data['periodo_fim'] ?? null,
            ':total_exames'   => $data['total_exames'] ?? 0,
            ':total_normal'   => $data['total_normal'] ?? 0,
            ':total_urgencia' => $data['total_urgencia'] ?? 0,
            ':valor_total'       => $data['valor_total'] ?? 0,
            ':valor_venda_total' => $data['valor_venda_total'] ?? 0,
            ':status'            => $data['status'] ?? 'rascunho',
            ':origem'         => $data['origem'] ?? 'manual',
            ':arquivo_import' => $data['arquivo_import'] ?? null,
            ':log_execucao'   => $data['log_execucao'] ?? null,
        ]);
        return $this->pdo->lastInsertId() ?: false;
    }

    public function update(int $id, array $data): bool
    {
        $campos = [];
        $params = [':id' => $id, ':usuario_id' => $data['usuario_id']];
        $permitidos = ['status','total_exames','total_normal','total_urgencia','valor_total','valor_venda_total',
                       'log_execucao','periodo_inicio','periodo_fim','arquivo_import','cliente_id'];
        foreach ($permitidos as $campo) {
            if (array_key_exists($campo, $data)) {
                $campos[] = "{$campo} = :{$campo}";
                $params[":{$campo}"] = $data[$campo];
            }
        }
        if (empty($campos)) return false;
        $campos[] = 'updated_at = NOW()';
        $sql = "UPDATE {$this->table} SET " . implode(', ', $campos) . " WHERE id = :id AND usuario_id = :usuario_id";
        return $this->pdo->prepare($sql)->execute($params);
    }

    public function delete(int $id, int $usuarioId): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE id = ? AND usuario_id = ? AND status IN ('rascunho','erro')");
        return $stmt->execute([$id, $usuarioId]);
    }

    public function gerarNumero(): string
    {
        return 'APU-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));
    }

    // Resumo por modalidade para a tela de apuração
    public function resumoPorModalidade(int $apuracaoId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT modalidade,
                    COUNT(*) AS total,
                    SUM(tipo_prioridade = 'normal') AS total_normal,
                    SUM(tipo_prioridade = 'urgencia') AS total_urgencia,
                    SUM(valor_calculado) AS valor
             FROM apuracao_itens
             WHERE apuracao_id = ?
             GROUP BY modalidade
             ORDER BY total DESC"
        );
        $stmt->execute([$apuracaoId]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    // Resumo por médico
    public function resumoPorMedico(int $apuracaoId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT medico_nome AS medico, medico_crm,
                    COUNT(*) AS total,
                    SUM(tipo_prioridade = 'normal') AS total_normal,
                    SUM(tipo_prioridade = 'urgencia') AS total_urgencia,
                    SUM(valor_calculado) AS valor
             FROM apuracao_itens
             WHERE apuracao_id = ?
             GROUP BY medico_nome, medico_crm
             ORDER BY total DESC"
        );
        $stmt->execute([$apuracaoId]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    // Resumo por unidade
    public function resumoPorUnidade(int $apuracaoId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT COALESCE(NULLIF(TRIM(unidade),''), 'Sem unidade') AS unidade,
                    COUNT(*) AS total,
                    SUM(tipo_prioridade = 'normal') AS total_normal,
                    SUM(tipo_prioridade = 'urgencia') AS total_urgencia,
                    SUM(valor_calculado) AS valor
             FROM apuracao_itens
             WHERE apuracao_id = ?
             GROUP BY unidade
             ORDER BY total DESC"
        );
        $stmt->execute([$apuracaoId]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function findByContratoId(int $contratoId, int $usuarioId, array $filtros = []): array
    {
        $where  = ['a.contrato_id = :contrato_id', 'a.usuario_id = :usuario_id'];
        $params = [':contrato_id' => $contratoId, ':usuario_id' => $usuarioId];

        if (!empty($filtros['status'])) {
            $where[] = 'a.status = :status';
            $params[':status'] = $filtros['status'];
        }
        if (!empty($filtros['periodo_inicio'])) {
            $where[] = 'a.periodo_inicio >= :periodo_inicio';
            $params[':periodo_inicio'] = $filtros['periodo_inicio'];
        }
        if (!empty($filtros['periodo_fim'])) {
            $where[] = 'a.periodo_fim <= :periodo_fim';
            $params[':periodo_fim'] = $filtros['periodo_fim'];
        }

        $sql = "SELECT a.*,
                       m.nome AS medico_nome, m.crm AS medico_crm,
                       cl.razao_social AS cliente_nome,
                       c.nome AS contrato_nome, c.numero AS contrato_numero
                FROM {$this->table} a
                LEFT JOIN medicos m ON m.id = a.medico_id
                LEFT JOIN clientes cl ON cl.id = a.cliente_id
                LEFT JOIN contratos c ON c.id = a.contrato_id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY a.created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }
}
