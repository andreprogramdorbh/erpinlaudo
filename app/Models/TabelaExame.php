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

    public function findById(int $id): ?object
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_OBJ);
        return $row ?: null;
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

    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE {$this->table}
                SET nome_exame = :nome_exame,
                    modalidade = :modalidade,
                    valor_padrao = :valor_padrao
                WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':nome_exame'   => $data['nome_exame'],
            ':modalidade'   => $data['modalidade'],
            ':valor_padrao' => $data['valor_padrao'],
            ':id'           => $id,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    // -------------------------------------------------------
    // Aba Precos: nivel, % rotina, % urgencia
    // -------------------------------------------------------
    public function savePrecos(int $id, array $data): bool
    {
        $valorPadrao = (float) $data['valor_padrao'];
        $percRotina  = (float) $data['perc_rotina'];
        $percUrgencia = (float) $data['perc_urgencia'];

        $valorRotina   = $valorPadrao + ($valorPadrao * $percRotina / 100);
        $valorUrgencia = $valorPadrao + ($valorPadrao * $percUrgencia / 100);

        $sql = "UPDATE {$this->table}
                SET nivel          = :nivel,
                    perc_rotina    = :perc_rotina,
                    perc_urgencia  = :perc_urgencia,
                    valor_rotina   = :valor_rotina,
                    valor_urgencia = :valor_urgencia
                WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':nivel'          => $data['nivel'] !== '' ? (int) $data['nivel'] : null,
            ':perc_rotina'    => $percRotina,
            ':perc_urgencia'  => $percUrgencia,
            ':valor_rotina'   => round($valorRotina, 2),
            ':valor_urgencia' => round($valorUrgencia, 2),
            ':id'             => $id,
        ]);
    }

    // -------------------------------------------------------
    // Aba Secao: encargos, custos operacionais, margem
    // -------------------------------------------------------
    public function saveSecao(int $id, array $data): bool
    {
        // Recupera valor_padrao para calcular preco_custo e preco_venda
        $exame = $this->findById($id);
        $valorBase = $exame ? (float) $exame->valor_padrao : 0.0;

        $icms       = (float) ($data['imposto_icms'] ?? 0);
        $ipi        = (float) ($data['imposto_ipi'] ?? 0);
        $pisCofins  = (float) ($data['imposto_pis_cofins'] ?? 0);
        $simples    = (float) ($data['imposto_simples'] ?? 0);
        $comissao   = (float) ($data['custo_comissao'] ?? 0);
        $moDireta   = (float) ($data['custo_mao_obra_direta'] ?? 0);
        $moIndireta = (float) ($data['custo_mao_obra_indireta'] ?? 0);
        $margem     = (float) ($data['margem_lucro'] ?? 0);

        // Preco de custo = valor_padrao + todos os percentuais de encargo e custo
        $totalPerc = $icms + $ipi + $pisCofins + $simples + $comissao + $moDireta + $moIndireta;
        $precoCusto = $valorBase + ($valorBase * $totalPerc / 100);
        $precoVenda = $precoCusto + ($precoCusto * $margem / 100);

        // Recalcular valores rotina/urgencia com base no preco_venda
        $percRotina   = (float) ($exame->perc_rotina ?? 0);
        $percUrgencia = (float) ($exame->perc_urgencia ?? 0);
        $valorRotina   = $precoVenda + ($precoVenda * $percRotina / 100);
        $valorUrgencia = $precoVenda + ($precoVenda * $percUrgencia / 100);

        $sql = "UPDATE {$this->table}
                SET imposto_icms          = :imposto_icms,
                    imposto_ipi           = :imposto_ipi,
                    imposto_pis_cofins    = :imposto_pis_cofins,
                    imposto_simples       = :imposto_simples,
                    custo_comissao        = :custo_comissao,
                    custo_mao_obra_direta = :custo_mao_obra_direta,
                    custo_mao_obra_indireta = :custo_mao_obra_indireta,
                    margem_lucro          = :margem_lucro,
                    preco_custo           = :preco_custo,
                    preco_venda           = :preco_venda,
                    valor_rotina          = :valor_rotina,
                    valor_urgencia        = :valor_urgencia
                WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':imposto_icms'           => $icms,
            ':imposto_ipi'            => $ipi,
            ':imposto_pis_cofins'     => $pisCofins,
            ':imposto_simples'        => $simples,
            ':custo_comissao'         => $comissao,
            ':custo_mao_obra_direta'  => $moDireta,
            ':custo_mao_obra_indireta'=> $moIndireta,
            ':margem_lucro'           => $margem,
            ':preco_custo'            => round($precoCusto, 2),
            ':preco_venda'            => round($precoVenda, 2),
            ':valor_rotina'           => round($valorRotina, 2),
            ':valor_urgencia'         => round($valorUrgencia, 2),
            ':id'                     => $id,
        ]);
    }

    // -------------------------------------------------------
    // Busca por TAG DICOM — motor de apuração
    // -------------------------------------------------------

    /**
     * Retorna todos os exames do usuário com suas tags DICOM agrupadas.
     * Resultado: array de objetos exame, cada um com propriedade tags_dicom (array de strings uppercase).
     * Usado pelo motor de apuração para match por TAG DICOM.
     */
    public function findAllWithTagsByUsuarioId(int $usuarioId): array
    {
        $exames = $this->findByUsuarioId($usuarioId);
        if (empty($exames)) return [];

        $ids = array_map(fn($e) => (int)$e->id, $exames);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->pdo->prepare(
            "SELECT exame_id, UPPER(TRIM(tag_valor)) AS tag_valor_upper
             FROM tabela_exames_tags
             WHERE exame_id IN ({$placeholders})"
        );
        $stmt->execute($ids);
        $tagRows = $stmt->fetchAll(PDO::FETCH_OBJ);

        $tagsPorExame = [];
        foreach ($tagRows as $row) {
            $tagsPorExame[(int)$row->exame_id][] = $row->tag_valor_upper;
        }

        foreach ($exames as $exame) {
            $exame->tags_dicom = $tagsPorExame[(int)$exame->id] ?? [];
        }

        return $exames;
    }

    // -------------------------------------------------------
    // TAGs DICOM
    // -------------------------------------------------------
    public function getTagsByExameId(int $exameId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM tabela_exames_tags WHERE exame_id = :exame_id ORDER BY tag_nome ASC"
        );
        $stmt->execute([':exame_id' => $exameId]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function addTag(int $exameId, string $tagNome, string $tagValor): bool
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO tabela_exames_tags (exame_id, tag_nome, tag_valor) VALUES (:exame_id, :tag_nome, :tag_valor)"
        );
        return $stmt->execute([
            ':exame_id'  => $exameId,
            ':tag_nome'  => trim($tagNome),
            ':tag_valor' => trim($tagValor),
        ]);
    }

    public function deleteTag(int $tagId): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM tabela_exames_tags WHERE id = :id");
        return $stmt->execute([':id' => $tagId]);
    }

    public function replaceAllTags(int $exameId, array $tags): bool
    {
        $this->pdo->prepare("DELETE FROM tabela_exames_tags WHERE exame_id = :exame_id")
            ->execute([':exame_id' => $exameId]);

        foreach ($tags as $tag) {
            $nome  = trim((string) ($tag['nome'] ?? ''));
            $valor = trim((string) ($tag['valor'] ?? ''));
            if ($nome !== '') {
                $this->addTag($exameId, $nome, $valor);
            }
        }
        return true;
    }
}
