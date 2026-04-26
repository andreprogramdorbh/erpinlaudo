<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class Medico extends Model
{
    protected string $table = 'medicos';

    // =========================================================
    // BUSCA POR ID
    // =========================================================

    public function findById(int $id): object|false
    {
        $sql = "SELECT m.*, e.especialidade AS especialidade_nome, e.subespecialidade AS especialidade_subespecialidade
                FROM {$this->table} m
                LEFT JOIN especialidades e ON e.id = m.especialidade_id
                WHERE m.id = :id
                LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function findByUsuarioId(int $usuarioId, array $filtros = []): array
    {
        $where = ['m.usuario_id = :usuario_id'];
        $params = [':usuario_id' => $usuarioId];

        $status = trim((string) ($filtros['status'] ?? 'ativo'));
        if ($status !== '') {
            $where[] = 'm.status = :status';
            $params[':status'] = $status;
        }

        $q = trim((string) ($filtros['pesquisa'] ?? ''));
        if ($q !== '') {
            $where[] = '(m.nome LIKE :q OR m.crm LIKE :q OR m.cpf LIKE :q OR e.especialidade LIKE :q)';
            $params[':q'] = '%' . $q . '%';
        }

        $sql = "SELECT m.*, e.especialidade AS especialidade_nome, e.subespecialidade AS especialidade_subespecialidade
                FROM {$this->table} m
                LEFT JOIN especialidades e
                  ON e.id = m.especialidade_id
                 AND e.usuario_id = m.usuario_id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY m.nome ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    // =========================================================
    // BUSCA POR CRM — verifica tabela medico_crms + campo legado
    // =========================================================

    /**
     * Busca um médico pelo CRM dentro do mesmo usuário.
     * Ordem de tentativas:
     *   1. Tabela medico_crms — CRM exato como veio na planilha
     *   2. Tabela medico_crms — apenas dígitos do CRM (LIKE)
     *   3. Campo legado medicos.crm — CRM exato
     *   4. Campo legado medicos.crm — apenas dígitos (LIKE)
     *
     * Toda normalização é feita em PHP para compatibilidade com MariaDB antigo.
     */
    public function findByCrm(int $usuarioId, string $crm): object|false
    {
        $crmLimpo = preg_replace('/\D/', '', $crm);

        // --- Busca 1: medico_crms — CRM exato ---
        $stmt = $this->pdo->prepare(
            "SELECT m.*, e.especialidade AS especialidade_nome
             FROM {$this->table} m
             INNER JOIN medico_crms mc ON mc.medico_id = m.id
             LEFT JOIN especialidades e ON e.id = m.especialidade_id
             WHERE m.usuario_id = :usuario_id
               AND mc.crm = :crm_raw
             LIMIT 1"
        );
        $stmt->execute([':usuario_id' => $usuarioId, ':crm_raw' => $crm]);
        $result = $stmt->fetch(PDO::FETCH_OBJ);
        if ($result) return $result;

        // --- Busca 2: medico_crms — apenas dígitos (LIKE) ---
        if ($crmLimpo !== '') {
            $stmt2 = $this->pdo->prepare(
                "SELECT m.*, e.especialidade AS especialidade_nome
                 FROM {$this->table} m
                 INNER JOIN medico_crms mc ON mc.medico_id = m.id
                 LEFT JOIN especialidades e ON e.id = m.especialidade_id
                 WHERE m.usuario_id = :usuario_id
                   AND mc.crm LIKE :crm_like
                 LIMIT 1"
            );
            $stmt2->execute([':usuario_id' => $usuarioId, ':crm_like' => '%' . $crmLimpo . '%']);
            $result2 = $stmt2->fetch(PDO::FETCH_OBJ);
            if ($result2) return $result2;
        }

        // --- Busca 3: campo legado medicos.crm — exato ---
        $stmt3 = $this->pdo->prepare(
            "SELECT m.*, e.especialidade AS especialidade_nome
             FROM {$this->table} m
             LEFT JOIN especialidades e ON e.id = m.especialidade_id
             WHERE m.usuario_id = :usuario_id
               AND m.crm = :crm_raw
             LIMIT 1"
        );
        $stmt3->execute([':usuario_id' => $usuarioId, ':crm_raw' => $crm]);
        $result3 = $stmt3->fetch(PDO::FETCH_OBJ);
        if ($result3) return $result3;

        // --- Busca 4: campo legado medicos.crm — apenas dígitos (LIKE) ---
        if ($crmLimpo !== '') {
            $stmt4 = $this->pdo->prepare(
                "SELECT m.*, e.especialidade AS especialidade_nome
                 FROM {$this->table} m
                 LEFT JOIN especialidades e ON e.id = m.especialidade_id
                 WHERE m.usuario_id = :usuario_id
                   AND m.crm LIKE :crm_like
                 LIMIT 1"
            );
            $stmt4->execute([':usuario_id' => $usuarioId, ':crm_like' => '%' . $crmLimpo . '%']);
            $result4 = $stmt4->fetch(PDO::FETCH_OBJ);
            if ($result4) return $result4;
        }

        return false;
    }

    // =========================================================
    // BUSCA POR NOME — fallback quando CRM não encontra
    // =========================================================

    /**
     * Busca um médico pelo nome (busca exata e parcial, case-insensitive).
     * Usado como fallback quando findByCrm não encontra resultado.
     */
    public function findByNome(int $usuarioId, string $nome): object|false
    {
        $nomeLimpo = trim($nome);
        if ($nomeLimpo === '') {
            return false;
        }

        // Busca 1: nome exato (case-insensitive)
        $stmt = $this->pdo->prepare(
            "SELECT m.*, e.especialidade AS especialidade_nome
             FROM {$this->table} m
             LEFT JOIN especialidades e ON e.id = m.especialidade_id
             WHERE m.usuario_id = :usuario_id
               AND LOWER(m.nome) = LOWER(:nome_exato)
             LIMIT 1"
        );
        $stmt->execute([':usuario_id' => $usuarioId, ':nome_exato' => $nomeLimpo]);
        $result = $stmt->fetch(PDO::FETCH_OBJ);
        if ($result) return $result;

        // Busca 2: nome contém a string buscada (busca parcial)
        $stmt2 = $this->pdo->prepare(
            "SELECT m.*, e.especialidade AS especialidade_nome
             FROM {$this->table} m
             LEFT JOIN especialidades e ON e.id = m.especialidade_id
             WHERE m.usuario_id = :usuario_id
               AND LOWER(m.nome) LIKE LOWER(:nome_like)
             ORDER BY m.nome ASC
             LIMIT 1"
        );
        $stmt2->execute([':usuario_id' => $usuarioId, ':nome_like' => '%' . $nomeLimpo . '%']);
        $result2 = $stmt2->fetch(PDO::FETCH_OBJ);
        if ($result2) return $result2;

        return false;
    }

    // =========================================================
    // BUSCA POR CPF — fallback secundário
    // =========================================================

    /**
     * Busca um médico pelo CPF (apenas dígitos) dentro do mesmo usuário.
     * Usado como fallback quando findByNome não encontra resultado.
     */
    public function findByCpf(int $usuarioId, string $cpf): object|false
    {
        $cpfLimpo = preg_replace('/\D/', '', $cpf);
        if ($cpfLimpo === '') {
            return false;
        }

        $stmt = $this->pdo->prepare(
            "SELECT m.*, e.especialidade AS especialidade_nome
             FROM {$this->table} m
             LEFT JOIN especialidades e ON e.id = m.especialidade_id
             WHERE m.usuario_id = :usuario_id
               AND REPLACE(REPLACE(m.cpf, '.', ''), '-', '') = :cpf
             LIMIT 1"
        );
        $stmt->execute([':usuario_id' => $usuarioId, ':cpf' => $cpfLimpo]);
        $result = $stmt->fetch(PDO::FETCH_OBJ);
        if ($result) return $result;

        return false;
    }

    // =========================================================
    // BUSCA POR ID EXTERNO (medico_crms.id) — preparado para
    // importações de planilha que trazem um ID de referência
    // =========================================================

    /**
     * Busca um médico pelo ID de um registro na tabela medico_crms.
     * Preparado para importações de planilha que trazem um ID externo
     * referenciando diretamente um CRM cadastrado.
     */
    public function findByIdMedCrm(int $usuarioId, int $medCrmId): object|false
    {
        $stmt = $this->pdo->prepare(
            "SELECT m.*, e.especialidade AS especialidade_nome
             FROM {$this->table} m
             INNER JOIN medico_crms mc ON mc.medico_id = m.id
             LEFT JOIN especialidades e ON e.id = m.especialidade_id
             WHERE m.usuario_id = :usuario_id
               AND mc.id = :med_crm_id
             LIMIT 1"
        );
        $stmt->execute([':usuario_id' => $usuarioId, ':med_crm_id' => $medCrmId]);
        $result = $stmt->fetch(PDO::FETCH_OBJ);
        if ($result) return $result;

        return false;
    }

    // =========================================================
    // GERENCIAMENTO DE medico_crms
    // =========================================================

    /**
     * Retorna todos os CRMs cadastrados para um médico.
     */
    public function getCrms(int $medicoId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM medico_crms
             WHERE medico_id = :medico_id
             ORDER BY principal DESC, uf_crm ASC"
        );
        $stmt->execute([':medico_id' => $medicoId]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Salva (substitui) todos os CRMs de um médico.
     * Recebe array de ['crm' => '...', 'uf_crm' => '...', 'principal' => 0|1].
     * O CRM principal (principal=1) também é sincronizado nos campos medicos.crm e medicos.uf_crm.
     */
    public function saveCrms(int $medicoId, int $usuarioId, array $crms): bool
    {
        // Remover todos os CRMs existentes
        $stmtDel = $this->pdo->prepare(
            "DELETE FROM medico_crms WHERE medico_id = :medico_id"
        );
        $stmtDel->execute([':medico_id' => $medicoId]);

        if (empty($crms)) {
            return true;
        }

        $stmtIns = $this->pdo->prepare(
            "INSERT INTO medico_crms (medico_id, usuario_id, crm, uf_crm, principal)
             VALUES (:medico_id, :usuario_id, :crm, :uf_crm, :principal)"
        );

        $crmPrincipal = null;
        $ufPrincipal  = null;

        foreach ($crms as $item) {
            $crmVal = trim((string)($item['crm'] ?? ''));
            $ufVal  = strtoupper(trim((string)($item['uf_crm'] ?? '')));
            if ($crmVal === '' || strlen($ufVal) !== 2) {
                continue;
            }
            $isPrincipal = (int)(bool)($item['principal'] ?? 0);
            $stmtIns->execute([
                ':medico_id'  => $medicoId,
                ':usuario_id' => $usuarioId,
                ':crm'        => $crmVal,
                ':uf_crm'     => $ufVal,
                ':principal'  => $isPrincipal,
            ]);
            if ($isPrincipal) {
                $crmPrincipal = $crmVal;
                $ufPrincipal  = $ufVal;
            }
        }

        // Sincronizar CRM principal nos campos legados da tabela medicos
        if ($crmPrincipal !== null) {
            $stmtUpd = $this->pdo->prepare(
                "UPDATE {$this->table}
                 SET crm = :crm, uf_crm = :uf_crm, updated_at = CURRENT_TIMESTAMP
                 WHERE id = :id"
            );
            $stmtUpd->execute([
                ':crm'    => $crmPrincipal,
                ':uf_crm' => $ufPrincipal,
                ':id'     => $medicoId,
            ]);
        }

        return true;
    }

    // =========================================================
    // CRUD PRINCIPAL
    // =========================================================

    public function create(array $data): string|false
    {
        $sql = "INSERT INTO {$this->table}
                (usuario_id, nome, crm, uf_crm, cpf, email, telefone, especialidade_id, subespecialidade, rqe, assinatura_digital, status)
                VALUES
                (:usuario_id, :nome, :crm, :uf_crm, :cpf, :email, :telefone, :especialidade_id, :subespecialidade, :rqe, :assinatura_digital, :status)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':usuario_id', $data['usuario_id'], PDO::PARAM_INT);
        $stmt->bindValue(':nome', $data['nome']);
        $stmt->bindValue(':crm', $data['crm']);
        $stmt->bindValue(':uf_crm', $data['uf_crm']);
        $stmt->bindValue(':cpf', $data['cpf']);
        $stmt->bindValue(':email', $data['email']);
        $stmt->bindValue(':telefone', $data['telefone']);
        $stmt->bindValue(':especialidade_id', $data['especialidade_id'], PDO::PARAM_INT);
        $stmt->bindValue(':subespecialidade', $data['subespecialidade'] ?? null);
        $stmt->bindValue(':rqe', $data['rqe'] ?? null);
        $stmt->bindValue(':assinatura_digital', $data['assinatura_digital'] ?? null);
        $stmt->bindValue(':status', $data['status'] ?? 'ativo');

        if ($stmt->execute()) {
            $newId = (int)$this->pdo->lastInsertId();
            // Inserir CRM principal na tabela medico_crms automaticamente
            if (!empty($data['crm']) && !empty($data['uf_crm'])) {
                $stmtCrm = $this->pdo->prepare(
                    "INSERT IGNORE INTO medico_crms (medico_id, usuario_id, crm, uf_crm, principal)
                     VALUES (:medico_id, :usuario_id, :crm, :uf_crm, 1)"
                );
                $stmtCrm->execute([
                    ':medico_id'  => $newId,
                    ':usuario_id' => $data['usuario_id'],
                    ':crm'        => $data['crm'],
                    ':uf_crm'     => $data['uf_crm'],
                ]);
            }
            return (string)$newId;
        }

        return false;
    }

    public function update(int $id, array $data): bool
    {
        $allowedFields = [
            'nome',
            'crm',
            'uf_crm',
            'cpf',
            'email',
            'telefone',
            'especialidade_id',
            'subespecialidade',
            'rqe',
            'assinatura_digital',
            'status',
        ];

        $updateFields = [];
        $params = [':id' => $id];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $updateFields[] = "{$field} = :{$field}";
                $params[":{$field}"] = $data[$field];
            }
        }

        if (empty($updateFields)) {
            return false;
        }

        $sql = "UPDATE {$this->table}
                SET " . implode(', ', $updateFields) . ", updated_at = CURRENT_TIMESTAMP
                WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }
}
