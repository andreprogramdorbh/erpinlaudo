<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class Cliente extends Model
{
    protected string $table = "clientes";

    // ------------------------------------------------------------------
    // Constantes de domínio — espelham CrmLead para compatibilidade
    // ------------------------------------------------------------------
    public const SEGMENTOS = [
        'clinica_imagem'     => 'Clínica de Imagem',
        'hospital'           => 'Hospital',
        'upa_pronto_socorro' => 'UPA / Pronto-Socorro',
        'laboratorio'        => 'Laboratório',
        'clinica_ortopedica' => 'Clínica Ortopédica',
        'clinica_oncologica' => 'Clínica Oncológica',
        'consultorio_medico' => 'Consultório Médico',
        'outro'              => 'Outro',
    ];

    public const ESPECIALIDADES = [
        'Tomografia',
        'Ressonância Magnética',
        'Raio-X',
        'Mamografia',
        'Ultrassom',
        'Densitometria Óssea',
        'PET-CT',
        'Medicina Nuclear',
        'Fluoroscopia',
        'Ecocardiograma',
        'PACS',
        'RIS',
        'HIS',
        'Teleradiologia',
        'Outro',
    ];

    // Campos que existem tanto em clientes quanto em crm_leads
    private const CAMPOS_COMUNS = [
        // Identificação
        'tipo', 'cpf_cnpj', 'razao_social', 'nome_fantasia',
        // Contato
        'email', 'telefone', 'celular',
        // Mídias sociais (unificadas)
        'website', 'instagram', 'linkedin', 'tiktok', 'facebook',
        // Endereço
        'endereco', 'numero', 'complemento', 'bairro', 'cidade', 'estado', 'cep',
        // Dados fiscais
        'cnae_principal', 'descricao_cnae',
        // Perfil clínico/comercial (vindo do CRM)
        'segmento_principal', 'especialidades_interesse',
        'volume_exames_mes', 'equipamentos_possui', 'sistema_atual',
        'num_medicos', 'num_unidades', 'acreditacao',
        // Responsável técnico/comercial
        'responsavel_nome', 'responsavel_cargo', 'responsavel_email', 'responsavel_telefone',
        // Controle
        'usuario_id', 'status',
        // Rastreabilidade CRM
        'crm_lead_id',
    ];

    // ------------------------------------------------------------------
    // Consultas
    // ------------------------------------------------------------------

    public function findByUsuarioId(int $usuarioId, array $filtros = []): array
    {
        $sql    = "SELECT * FROM {$this->table} WHERE usuario_id = ?";
        $params = [$usuarioId];

        if (!empty($filtros['status'])) {
            $sql     .= " AND status = ?";
            $params[] = $filtros['status'];
        }
        if (!empty($filtros['pesquisa'])) {
            $sql     .= " AND (razao_social LIKE ? OR nome_fantasia LIKE ? OR cpf_cnpj LIKE ?)";
            $busca    = "%{$filtros['pesquisa']}%";
            $params[] = $busca;
            $params[] = $busca;
            $params[] = $busca;
        }
        if (!empty($filtros['uf'])) {
            $sql     .= " AND estado = ?";
            $params[] = $filtros['uf'];
        }
        if (!empty($filtros['tipo'])) {
            $sql     .= " AND tipo = ?";
            $params[] = $filtros['tipo'];
        }

        $sql .= " ORDER BY id DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function findById(int $id): object|false
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function findByCpfCnpj(string $cpfCnpj): object|false
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE cpf_cnpj = ?");
        $stmt->execute([$cpfCnpj]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function findByCpfCnpjAndUsuarioId(string $cpfCnpj, int $usuarioId): object|false
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM {$this->table} WHERE cpf_cnpj = ? AND usuario_id = ?"
        );
        $stmt->execute([$cpfCnpj, $usuarioId]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function findByEmail(string $email): object|false
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    // ------------------------------------------------------------------
    // Escrita
    // ------------------------------------------------------------------

    public function create(array $data): string|false
    {
        $fields = [
            'tipo', 'cpf_cnpj', 'razao_social', 'nome_fantasia',
            'email', 'telefone', 'celular',
            'website', 'instagram', 'linkedin', 'tiktok', 'facebook',
            'endereco', 'numero', 'complemento', 'bairro', 'cidade', 'estado', 'cep',
            'cnae_principal', 'descricao_cnae',
            'segmento_principal', 'especialidades_interesse',
            'volume_exames_mes', 'equipamentos_possui', 'sistema_atual',
            'num_medicos', 'num_unidades', 'acreditacao',
            'responsavel_nome', 'responsavel_cargo', 'responsavel_email', 'responsavel_telefone',
            'usuario_id', 'status', 'crm_lead_id',
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
            'tipo', 'cpf_cnpj', 'razao_social', 'nome_fantasia',
            'email', 'telefone', 'celular',
            'website', 'instagram', 'linkedin', 'tiktok', 'facebook',
            'endereco', 'numero', 'complemento', 'bairro', 'cidade', 'estado', 'cep',
            'cnae_principal', 'descricao_cnae',
            'segmento_principal', 'especialidades_interesse',
            'volume_exames_mes', 'equipamentos_possui', 'sistema_atual',
            'num_medicos', 'num_unidades', 'acreditacao',
            'responsavel_nome', 'responsavel_cargo', 'responsavel_email', 'responsavel_telefone',
            'status', 'crm_lead_id',
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

    /** Soft delete — marca como inativo */
    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("UPDATE {$this->table} SET status = 'inativo' WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /** Hard delete permanente */
    public function forceDelete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE id = ?");
        return $stmt->execute([$id]);
    }

    // ------------------------------------------------------------------
    // Contadores / verificações
    // ------------------------------------------------------------------

    public function countByUsuarioId(int $usuarioId): int
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) AS total FROM {$this->table} WHERE usuario_id = ?"
        );
        $stmt->execute([$usuarioId]);
        return (int) ($stmt->fetch(PDO::FETCH_OBJ)->total ?? 0);
    }

    public function cpfCnpjExists(string $cpfCnpj, ?int $excludeId = null): bool
    {
        $sql    = "SELECT COUNT(*) AS total FROM {$this->table} WHERE cpf_cnpj = ?";
        $params = [$cpfCnpj];
        if ($excludeId !== null) {
            $sql     .= " AND id != ?";
            $params[] = $excludeId;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int) ($stmt->fetch(PDO::FETCH_OBJ)->total ?? 0) > 0;
    }

    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $sql    = "SELECT COUNT(*) AS total FROM {$this->table} WHERE email = ?";
        $params = [$email];
        if ($excludeId !== null) {
            $sql     .= " AND id != ?";
            $params[] = $excludeId;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int) ($stmt->fetch(PDO::FETCH_OBJ)->total ?? 0) > 0;
    }
}
