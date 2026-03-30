<?php

namespace App\Models;

use App\Core\Model;
use PDO;

/**
 * Representa uma linha de modalidade/contrato/volume dentro de uma oportunidade.
 * Cada oportunidade pode ter N linhas, permitindo registrar múltiplas combinações.
 */
class CrmOportunidadeModalidade extends Model
{
    protected string $table = 'crm_oportunidade_modalidades';

    public function findByOportunidadeId(int $opId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM {$this->table}
             WHERE oportunidade_id = ?
             ORDER BY ordem ASC, id ASC"
        );
        $stmt->execute([$opId]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function create(array $data): string|false
    {
        $fields = [
            'oportunidade_id', 'modalidade', 'tipo_contrato',
            'volume_estimado_mes', 'observacao', 'ordem',
        ];

        $cols  = implode(', ', $fields);
        $binds = ':' . implode(', :', $fields);
        $sql   = "INSERT INTO {$this->table} ({$cols}) VALUES ({$binds})";
        $stmt  = $this->pdo->prepare($sql);

        foreach ($fields as $f) {
            $val = $data[$f] ?? null;
            $stmt->bindValue(':' . $f, ($val === '') ? null : $val);
        }

        return $stmt->execute() ? $this->pdo->lastInsertId() : false;
    }

    public function update(int $id, array $data): bool
    {
        $allowed = [
            'modalidade', 'tipo_contrato', 'volume_estimado_mes', 'observacao', 'ordem',
        ];

        $sets   = [];
        $params = [':id' => $id];

        foreach ($allowed as $f) {
            if (!array_key_exists($f, $data)) continue;
            $sets[]           = "{$f} = :{$f}";
            $params[':' . $f] = ($data[$f] === '') ? null : $data[$f];
        }

        if (empty($sets)) return false;

        $stmt = $this->pdo->prepare(
            "UPDATE {$this->table} SET " . implode(', ', $sets) . " WHERE id = :id"
        );
        return $stmt->execute($params);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /** Remove todas as linhas de uma oportunidade (usado ao re-salvar) */
    public function deleteByOportunidadeId(int $opId): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE oportunidade_id = ?");
        return $stmt->execute([$opId]);
    }

    /** Substitui todas as linhas de uma oportunidade de uma vez */
    public function replaceAll(int $opId, array $linhas): void
    {
        $this->deleteByOportunidadeId($opId);
        foreach ($linhas as $i => $linha) {
            $linha['oportunidade_id'] = $opId;
            $linha['ordem']           = $i + 1;
            $this->create($linha);
        }
    }
}
