<?php
namespace App\Models;

use App\Core\Model;
use PDO;

/**
 * Model: EmpresaConfig
 * Gerencia os dados da empresa cadastrada pelo usuário no ERP.
 * Relação 1:1 com a tabela users (usuario_id UNIQUE).
 */
class EmpresaConfig extends Model
{
    protected string $table = 'empresa_config';

    // ---------------------------------------------------------------
    // Busca os dados da empresa pelo usuario_id
    // ---------------------------------------------------------------
    public function findByUsuarioId(int $usuarioId): object|false
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM {$this->table} WHERE usuario_id = :uid LIMIT 1"
        );
        $stmt->execute([':uid' => $usuarioId]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    // ---------------------------------------------------------------
    // Cria ou atualiza (upsert) os dados da empresa
    // ---------------------------------------------------------------
    public function upsert(int $usuarioId, array $data): bool
    {
        $existing = $this->findByUsuarioId($usuarioId);

        if ($existing) {
            return $this->update($existing->id, $data);
        }

        $data['usuario_id'] = $usuarioId;
        return $this->insert($data) > 0;
    }

    // ---------------------------------------------------------------
    // Insere novo registro
    // ---------------------------------------------------------------
    private function insert(array $data): int
    {
        $cols = implode(', ', array_map(fn($k) => "`$k`", array_keys($data)));
        $phs  = implode(', ', array_map(fn($k) => ":$k", array_keys($data)));
        $stmt = $this->pdo->prepare(
            "INSERT INTO {$this->table} ($cols) VALUES ($phs)"
        );
        $params = [];
        foreach ($data as $k => $v) {
            $params[":$k"] = $v;
        }
        try {
            $stmt->execute($params);
            return (int) $this->pdo->lastInsertId();
        } catch (\PDOException $e) {
            error_log('[EmpresaConfig::insert] ' . $e->getMessage());
            return 0;
        }
    }

    // ---------------------------------------------------------------
    // Atualiza registro existente pelo id
    // ---------------------------------------------------------------
    private function update(int $id, array $data): bool
    {
        $sets = implode(', ', array_map(fn($k) => "`$k` = :$k", array_keys($data)));
        $stmt = $this->pdo->prepare(
            "UPDATE {$this->table} SET $sets WHERE id = :__id"
        );
        $params = [':__id' => $id];
        foreach ($data as $k => $v) {
            $params[":$k"] = $v;
        }
        try {
            $stmt->execute($params);
            return true;
        } catch (\PDOException $e) {
            error_log('[EmpresaConfig::update] ' . $e->getMessage());
            return false;
        }
    }

    // ---------------------------------------------------------------
    // Retorna o caminho relativo do logo salvo
    // ---------------------------------------------------------------
    public function getLogoPath(int $usuarioId): string
    {
        $empresa = $this->findByUsuarioId($usuarioId);
        return ($empresa && !empty($empresa->logo_path)) ? $empresa->logo_path : '';
    }

    // ---------------------------------------------------------------
    // Retorna os dados de assinatura da empresa
    // Usado em Propostas, Contratos e demais documentos.
    // ---------------------------------------------------------------
    public function getAssinatura(int $usuarioId): array
    {
        $empresa = $this->findByUsuarioId($usuarioId);
        if (!$empresa) {
            return [
                'nome'                  => '',
                'cargo'                 => '',
                'rubrica'               => '',
                'imagem_path'           => '',
                'usar_imagem'           => false,
                'autenticacao_texto'    => '',
                'autenticacao_ativa'    => false,
            ];
        }
        return [
            'nome'               => $empresa->assinatura_nome ?? '',
            'cargo'              => $empresa->assinatura_cargo ?? '',
            'rubrica'            => $empresa->assinatura_rubrica ?? '',
            'imagem_path'        => $empresa->assinatura_imagem_path ?? '',
            'usar_imagem'        => (bool)($empresa->usar_assinatura_imagem ?? false),
            'autenticacao_texto' => $empresa->autenticacao_texto ?? '',
            'autenticacao_ativa' => (bool)($empresa->autenticacao_ativa ?? true),
        ];
    }

    // ---------------------------------------------------------------
    // Salva o caminho da imagem de assinatura
    // ---------------------------------------------------------------
    public function saveAssinaturaImagem(int $usuarioId, string $path): bool
    {
        $empresa = $this->findByUsuarioId($usuarioId);
        if (!$empresa) return false;
        return $this->update($empresa->id, ['assinatura_imagem_path' => $path]);
    }
}
