<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class TabelaExame extends Model
{
    protected string $table = 'tabela_exames';

    public function findByUsuarioId(int $usuarioId, array $filtros = []): array
    {
        $where = ['usuario_id = :usuario_id'];
        $params = [':usuario_id' => $usuarioId];

        $modalidade = trim((string) ($filtros['modalidade'] ?? ''));
        if ($modalidade !== '') {
            $where[] = 'modalidade = :modalidade';
            $params[':modalidade'] = $modalidade;
        }

        $q = trim((string) ($filtros['pesquisa'] ?? ''));
        if ($q !== '') {
            $where[] = '(nome_exame LIKE :q OR modalidade LIKE :q)';
            $params[':q'] = '%' . $q . '%';
        }

        $sql = "SELECT *
                FROM {$this->table}
                WHERE " . implode(' AND ', $where) . "
                ORDER BY nome_exame ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function create(array $data): string|false
    {
        $sql = "INSERT INTO {$this->table}
                (usuario_id, nome_exame, modalidade, valor_padrao)
                VALUES (:usuario_id, :nome_exame, :modalidade, :valor_padrao)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':usuario_id', $data['usuario_id'], PDO::PARAM_INT);
        $stmt->bindValue(':nome_exame', $data['nome_exame']);
        $stmt->bindValue(':modalidade', $data['modalidade']);
        $stmt->bindValue(':valor_padrao', $data['valor_padrao']);

        if ($stmt->execute()) {
            return $this->pdo->lastInsertId();
        }

        return false;
    }
}
