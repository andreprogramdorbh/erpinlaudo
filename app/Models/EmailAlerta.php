<?php

namespace App\Models;

use App\Core\Model;
use PDO;

/**
 * Model: EmailAlerta
 * Gerencia os alertas de e-mail configurados por módulo (financeiro, faturamento, crm).
 */
class EmailAlerta extends Model
{
    protected string $table = 'email_alertas';

    // -------------------------------------------------------------------------
    // Consultas
    // -------------------------------------------------------------------------

    /**
     * Retorna todos os alertas de um usuário, agrupados por módulo.
     * Inclui os padrões do sistema (usuario_id = 0) que ainda não foram
     * personalizados pelo usuário.
     */
    public function findAllByUsuario(int $usuarioId): array
    {
        // Padrões do sistema ainda não clonados para este usuário
        $sql = "
            SELECT a.*
            FROM {$this->table} a
            WHERE a.usuario_id = :uid
            UNION
            SELECT s.*
            FROM {$this->table} s
            WHERE s.usuario_id = 0
              AND s.codigo NOT IN (
                  SELECT codigo FROM {$this->table} WHERE usuario_id = :uid2
              )
            ORDER BY modulo ASC, id ASC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':uid' => $usuarioId, ':uid2' => $usuarioId]);
        return $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];
    }

    /**
     * Retorna um alerta pelo código e usuário (ou padrão do sistema).
     */
    public function findByCodigo(string $codigo, int $usuarioId): object|false
    {
        $sql = "SELECT * FROM {$this->table}
                WHERE codigo = :codigo
                  AND (usuario_id = :uid OR usuario_id = 0)
                ORDER BY usuario_id DESC
                LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':codigo' => $codigo, ':uid' => $usuarioId]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    /**
     * Retorna um alerta pelo ID.
     */
    public function findById(int $id): object|false
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    /**
     * Retorna alertas ativos de um módulo para processamento pelo cron.
     */
    public function findAtivosParaDisparo(string $modulo, int $usuarioId): array
    {
        $sql = "SELECT * FROM {$this->table}
                WHERE ativo = 1
                  AND modulo = :modulo
                  AND (usuario_id = :uid OR usuario_id = 0)
                ORDER BY id ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':modulo' => $modulo, ':uid' => $usuarioId]);
        return $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];
    }

    // -------------------------------------------------------------------------
    // Escrita
    // -------------------------------------------------------------------------

    /**
     * Cria ou atualiza (upsert) um alerta para o usuário.
     * Se o alerta é padrão (usuario_id=0), cria uma cópia personalizada.
     */
    public function salvar(int $usuarioId, array $data): bool
    {
        // Verifica se já existe registro personalizado
        $existente = $this->pdo->prepare(
            "SELECT id FROM {$this->table} WHERE usuario_id = :uid AND codigo = :codigo LIMIT 1"
        );
        $existente->execute([':uid' => $usuarioId, ':codigo' => $data['codigo']]);
        $row = $existente->fetch(PDO::FETCH_OBJ);

        if ($row) {
            return $this->atualizar((int) $row->id, $usuarioId, $data);
        }

        return $this->inserir($usuarioId, $data);
    }

    private function inserir(int $usuarioId, array $data): bool
    {
        $sql = "INSERT INTO {$this->table}
                    (usuario_id, codigo, modulo, nome, descricao, antecedencia_dias,
                     frequencia, hora_disparo, destinatarios, cc,
                     assunto_template, corpo_template, ativo)
                VALUES
                    (:usuario_id, :codigo, :modulo, :nome, :descricao, :antecedencia_dias,
                     :frequencia, :hora_disparo, :destinatarios, :cc,
                     :assunto_template, :corpo_template, :ativo)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($this->buildParams($usuarioId, $data));
    }

    private function atualizar(int $id, int $usuarioId, array $data): bool
    {
        $sql = "UPDATE {$this->table}
                SET nome              = :nome,
                    descricao         = :descricao,
                    antecedencia_dias = :antecedencia_dias,
                    frequencia        = :frequencia,
                    hora_disparo      = :hora_disparo,
                    destinatarios     = :destinatarios,
                    cc                = :cc,
                    assunto_template  = :assunto_template,
                    corpo_template    = :corpo_template,
                    ativo             = :ativo,
                    updated_at        = NOW()
                WHERE id = :id AND usuario_id = :usuario_id";
        $params = $this->buildParams($usuarioId, $data);
        $params[':id'] = $id;
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Ativa ou desativa um alerta.
     */
    public function toggleAtivo(int $id, int $usuarioId, bool $ativo): bool
    {
        // Se o alerta é padrão (usuario_id=0), clona antes de alterar
        $alerta = $this->findById($id);
        if (!$alerta) {
            return false;
        }

        if ((int) $alerta->usuario_id === 0) {
            // Clonar padrão para o usuário
            $data = (array) $alerta;
            unset($data['id'], $data['created_at'], $data['updated_at']);
            $data['ativo'] = $ativo ? 1 : 0;
            return $this->inserir($usuarioId, $data);
        }

        $stmt = $this->pdo->prepare(
            "UPDATE {$this->table} SET ativo = :ativo, updated_at = NOW()
             WHERE id = :id AND usuario_id = :uid"
        );
        return $stmt->execute([':ativo' => $ativo ? 1 : 0, ':id' => $id, ':uid' => $usuarioId]);
    }

    /**
     * Registra disparo no log.
     */
    public function registrarDisparo(int $alertaId, int $usuarioId, string $destinatario,
                                     string $assunto, string $status = 'enviado',
                                     ?string $erro = null, ?string $referencia = null): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO email_alertas_log
                 (alerta_id, usuario_id, destinatario, assunto, status, erro, referencia)
             VALUES (:alerta_id, :uid, :dest, :assunto, :status, :erro, :ref)"
        );
        $stmt->execute([
            ':alerta_id' => $alertaId,
            ':uid'       => $usuarioId,
            ':dest'      => $destinatario,
            ':assunto'   => $assunto,
            ':status'    => $status,
            ':erro'      => $erro,
            ':ref'       => $referencia,
        ]);

        // Atualiza contador e data do último disparo
        $this->pdo->prepare(
            "UPDATE {$this->table}
             SET ultimo_disparo = NOW(), total_disparos = total_disparos + 1
             WHERE id = :id"
        )->execute([':id' => $alertaId]);
    }

    /**
     * Retorna o log de disparos de um alerta.
     */
    public function findLog(int $alertaId, int $limit = 50): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM email_alertas_log
             WHERE alerta_id = :id
             ORDER BY disparado_em DESC
             LIMIT :lim"
        );
        $stmt->bindValue(':id', $alertaId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function buildParams(int $usuarioId, array $data): array
    {
        return [
            ':usuario_id'       => $usuarioId,
            ':codigo'           => $data['codigo'],
            ':modulo'           => $data['modulo'],
            ':nome'             => $data['nome'],
            ':descricao'        => $data['descricao'] ?? null,
            ':antecedencia_dias'=> (int) ($data['antecedencia_dias'] ?? 3),
            ':frequencia'       => $data['frequencia'] ?? 'unico',
            ':hora_disparo'     => $data['hora_disparo'] ?? '08:00:00',
            ':destinatarios'    => is_array($data['destinatarios'])
                                    ? json_encode($data['destinatarios'])
                                    : ($data['destinatarios'] ?? '["admin"]'),
            ':cc'               => isset($data['cc'])
                                    ? (is_array($data['cc']) ? json_encode($data['cc']) : $data['cc'])
                                    : null,
            ':assunto_template' => $data['assunto_template'] ?? '',
            ':corpo_template'   => $data['corpo_template'] ?? '',
            ':ativo'            => isset($data['ativo']) ? (int) $data['ativo'] : 1,
        ];
    }
}
