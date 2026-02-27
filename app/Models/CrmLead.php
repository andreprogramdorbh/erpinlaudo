<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class CrmLead extends Model
{
    protected string $table = 'crm_leads';

    // ------------------------------------------------------------------
    // Constantes de domínio
    // ------------------------------------------------------------------
    public const STATUS = [
        'novo'        => 'Novo',
        'contatado'   => 'Contatado',
        'qualificado' => 'Qualificado',
        'descartado'  => 'Descartado',
    ];

    public const ORIGENS = [
        'indicacao'        => 'Indicação',
        'site'             => 'Site / Landing Page',
        'evento'           => 'Evento / Feira',
        'linkedin'         => 'LinkedIn',
        'prospeccao_ativa' => 'Prospecção Ativa',
        'parceiro'         => 'Parceiro Comercial',
        'outro'            => 'Outro',
    ];

    public const SEGMENTOS = [
        'clinica_imagem'      => 'Clínica de Imagem',
        'hospital'            => 'Hospital',
        'upa_pronto_socorro'  => 'UPA / Pronto-Socorro',
        'laboratorio'         => 'Laboratório',
        'clinica_ortopedica'  => 'Clínica Ortopédica',
        'clinica_oncologica'  => 'Clínica Oncológica',
        'consultorio_medico'  => 'Consultório Médico',
        'outro'               => 'Outro',
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
        'Outro',
    ];

    // ------------------------------------------------------------------
    // Consultas
    // ------------------------------------------------------------------

    public function findByUsuarioId(int $usuarioId, array $filtros = []): array
    {
        $where  = ['l.usuario_id = :uid'];
        $params = [':uid' => $usuarioId];

        if (!empty($filtros['status'])) {
            $where[]           = 'l.status_lead = :status';
            $params[':status'] = $filtros['status'];
        }
        if (!empty($filtros['segmento'])) {
            $where[]             = 'l.segmento_principal = :segmento';
            $params[':segmento'] = $filtros['segmento'];
        }
        if (!empty($filtros['origem'])) {
            $where[]           = 'l.origem = :origem';
            $params[':origem'] = $filtros['origem'];
        }
        if (!empty($filtros['q'])) {
            $where[]      = '(l.nome_lead LIKE :q OR l.email LIKE :q OR l.cnpj LIKE :q OR l.cidade LIKE :q)';
            $params[':q'] = '%' . $filtros['q'] . '%';
        }

        $sql = "SELECT l.*,
                       (SELECT COUNT(*) FROM crm_interacoes i
                        WHERE i.related_type = 'lead' AND i.related_id = l.id) AS total_interacoes
                FROM {$this->table} l
                WHERE " . implode(' AND ', $where) . "
                ORDER BY l.data_proximo_contato ASC, l.created_at DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function findById(int $id): object|false
    {
        $stmt = $this->pdo->prepare(
            "SELECT l.*,
                    (SELECT COUNT(*) FROM crm_interacoes i
                     WHERE i.related_type = 'lead' AND i.related_id = l.id) AS total_interacoes
             FROM {$this->table} l
             WHERE l.id = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function create(array $data): string|false
    {
        $fields = [
            'usuario_id','nome_lead','email','telefone','celular','cnpj','cpf','tipo_pessoa',
            'razao_social','nome_fantasia','cnae_principal','descricao_cnae',
            'endereco','numero','complemento','bairro','cidade','estado','cep',
            'origem','status_lead','segmento_principal','especialidades_interesse',
            'volume_exames_mes','equipamentos_possui','sistema_atual','num_medicos',
            'num_unidades','acreditacao','responsavel_nome','responsavel_cargo',
            'responsavel_email','responsavel_telefone','produtos_interesse',
            'data_proximo_contato','observacoes',
        ];

        $cols   = implode(', ', $fields);
        $binds  = ':' . implode(', :', $fields);
        $sql    = "INSERT INTO {$this->table} ({$cols}) VALUES ({$binds})";
        $stmt   = $this->pdo->prepare($sql);

        foreach ($fields as $f) {
            $val = $data[$f] ?? null;
            $stmt->bindValue(':' . $f, ($val === '') ? null : $val);
        }

        return $stmt->execute() ? $this->pdo->lastInsertId() : false;
    }

    public function update(int $id, array $data): bool
    {
        $allowed = [
            'nome_lead','email','telefone','celular','cnpj','cpf','tipo_pessoa',
            'razao_social','nome_fantasia','cnae_principal','descricao_cnae',
            'endereco','numero','complemento','bairro','cidade','estado','cep',
            'origem','status_lead','segmento_principal','especialidades_interesse',
            'volume_exames_mes','equipamentos_possui','sistema_atual','num_medicos',
            'num_unidades','acreditacao','responsavel_nome','responsavel_cargo',
            'responsavel_email','responsavel_telefone','produtos_interesse',
            'data_proximo_contato','observacoes',
            'convertido_em','convertido_id','convertido_em_data',
        ];

        $sets   = [];
        $params = [':id' => $id];

        foreach ($allowed as $f) {
            if (!array_key_exists($f, $data)) continue;
            $sets[]         = "{$f} = :{$f}";
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

    /** Conta leads por status para o dashboard */
    public function countByStatusAndUsuarioId(int $usuarioId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT status_lead, COUNT(*) AS total
             FROM {$this->table}
             WHERE usuario_id = ?
             GROUP BY status_lead"
        );
        $stmt->execute([$usuarioId]);
        $rows   = $stmt->fetchAll(PDO::FETCH_OBJ);
        $result = array_fill_keys(array_keys(self::STATUS), 0);
        foreach ($rows as $r) {
            $result[$r->status_lead] = (int) $r->total;
        }
        return $result;
    }
}
