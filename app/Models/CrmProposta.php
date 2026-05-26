<?php

namespace App\Models;

use App\Core\Database;

class CrmProposta
{
    private \PDO $pdo;
    private string $table = 'crm_propostas';

    public function __construct()
    {
        $this->pdo = Database::getInstance();
        $this->garantirTabelas();
    }

    // ─── Auto-migration ──────────────────────────────────────────────────────

    private function garantirTabelas(): void
    {
        try {
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS crm_propostas (
                  id                    INT AUTO_INCREMENT PRIMARY KEY,
                  usuario_id            INT NOT NULL,
                  numero                VARCHAR(20) NOT NULL,
                  oportunidade_id       INT NULL,
                  lead_id               INT NULL,
                  cliente_id            INT NULL,
                  cliente_nome          VARCHAR(255) NOT NULL,
                  cliente_razao_social  VARCHAR(255) NULL,
                  cliente_cnpj_cpf      VARCHAR(20)  NULL,
                  cliente_email         VARCHAR(255) NULL,
                  cliente_telefone      VARCHAR(20)  NULL,
                  cliente_endereco      TEXT         NULL,
                  cliente_cidade        VARCHAR(100) NULL,
                  cliente_estado        CHAR(2)      NULL,
                  cliente_cep           VARCHAR(10)  NULL,
                  cliente_responsavel   VARCHAR(255) NULL,
                  titulo                VARCHAR(255) NOT NULL,
                  descricao             TEXT         NULL,
                  validade_proposta     DATE         NOT NULL,
                  status                ENUM('gerada','enviada','visualizada','aceita','recusada','expirada') NOT NULL DEFAULT 'gerada',
                  subtotal              DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                  desconto_tipo         ENUM('percentual','fixo') NULL,
                  desconto_valor        DECIMAL(12,2) NULL DEFAULT 0.00,
                  desconto_total        DECIMAL(12,2) NULL DEFAULT 0.00,
                  total                 DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                  prazo_entrega         VARCHAR(100) NULL,
                  condicao_pagamento    VARCHAR(255) NULL,
                  frete_tipo            ENUM('cif','fob','sem_frete','a_calcular') NULL DEFAULT 'a_calcular',
                  frete_valor           DECIMAL(10,2) NULL DEFAULT 0.00,
                  local_entrega         TEXT NULL,
                  observacoes           TEXT NULL,
                  notas_internas        TEXT NULL,
                  enviado_em            DATETIME NULL,
                  visualizado_em        DATETIME NULL,
                  aceito_em             DATETIME NULL,
                  pdf_path              VARCHAR(500) NULL,
                  token_acesso          VARCHAR(64) NULL,
                  created_at            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  updated_at            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                  INDEX idx_prop_usuario     (usuario_id),
                  INDEX idx_prop_status      (status),
                  INDEX idx_prop_numero      (numero)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS crm_proposta_itens (
                  id              INT AUTO_INCREMENT PRIMARY KEY,
                  proposta_id     INT NOT NULL,
                  produto_id      INT NULL,
                  codigo          VARCHAR(50)  NULL,
                  descricao       VARCHAR(500) NOT NULL,
                  unidade         VARCHAR(20)  NULL DEFAULT 'un',
                  quantidade      DECIMAL(10,3) NOT NULL DEFAULT 1.000,
                  preco_custo     DECIMAL(12,2) NULL DEFAULT 0.00,
                  margem_lucro    DECIMAL(5,2) NULL DEFAULT 0.00,
                  preco_unitario  DECIMAL(12,2) NOT NULL,
                  desconto_item   DECIMAL(5,2) NULL DEFAULT 0.00,
                  total_item      DECIMAL(12,2) NOT NULL,
                  ordem           SMALLINT NOT NULL DEFAULT 0,
                  created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  INDEX idx_item_proposta (proposta_id),
                  CONSTRAINT fk_item_proposta FOREIGN KEY (proposta_id)
                    REFERENCES crm_propostas(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS crm_proposta_historico (
                  id            INT AUTO_INCREMENT PRIMARY KEY,
                  proposta_id   INT NOT NULL,
                  usuario_id    INT NULL,
                  status_de     VARCHAR(30) NULL,
                  status_para   VARCHAR(30) NOT NULL,
                  observacao    TEXT NULL,
                  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  INDEX idx_hist_proposta (proposta_id),
                  CONSTRAINT fk_hist_proposta FOREIGN KEY (proposta_id)
                    REFERENCES crm_propostas(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        } catch (\Throwable $e) {
            error_log('[CrmProposta] garantirTabelas: ' . $e->getMessage());
        }
    }

    // ─── Geração de número ───────────────────────────────────────────────────

    public function gerarNumero(int $usuarioId): string
    {
        $ano  = date('Y');
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) AS total FROM {$this->table}
             WHERE usuario_id = ? AND YEAR(created_at) = ?"
        );
        $stmt->execute([$usuarioId, $ano]);
        $total = (int) $stmt->fetchObject()->total;
        return sprintf('PROP-%s-%04d', $ano, $total + 1);
    }

    // ─── CRUD ────────────────────────────────────────────────────────────────

    public function findByUsuarioId(int $usuarioId, array $filtros = []): array
    {
        $sql    = "SELECT p.*,
                          u.name AS responsavel_nome
                   FROM {$this->table} p
                   LEFT JOIN users u ON u.id = p.usuario_id
                   WHERE p.usuario_id = ?";
        $params = [$usuarioId];

        if (!empty($filtros['status'])) {
            $sql     .= " AND p.status = ?";
            $params[] = $filtros['status'];
        }
        if (!empty($filtros['busca'])) {
            $sql     .= " AND (p.numero LIKE ? OR p.cliente_nome LIKE ? OR p.titulo LIKE ?)";
            $like     = '%' . $filtros['busca'] . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $sql .= " ORDER BY p.created_at DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    public function findById(int $id): object|false
    {
        $stmt = $this->pdo->prepare(
            "SELECT p.*, u.name AS responsavel_nome, u.email AS responsavel_email
             FROM {$this->table} p
             LEFT JOIN users u ON u.id = p.usuario_id
             WHERE p.id = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetchObject();
    }

    public function findByToken(string $token): object|false
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM {$this->table} WHERE token_acesso = ?"
        );
        $stmt->execute([$token]);
        return $stmt->fetchObject();
    }

    public function create(array $data): string|false
    {
        $campos = [
            'usuario_id', 'numero', 'oportunidade_id', 'lead_id', 'cliente_id',
            'cliente_nome', 'cliente_razao_social', 'cliente_cnpj_cpf',
            'cliente_email', 'cliente_telefone', 'cliente_endereco',
            'cliente_cidade', 'cliente_estado', 'cliente_cep', 'cliente_responsavel',
            'titulo', 'descricao', 'validade_proposta', 'status',
            'subtotal', 'desconto_tipo', 'desconto_valor', 'desconto_total', 'total',
            'prazo_entrega', 'condicao_pagamento', 'frete_tipo', 'frete_valor',
            'local_entrega', 'observacoes', 'notas_internas', 'token_acesso',
        ];

        $data    = array_intersect_key($data, array_flip($campos));
        $cols    = implode(', ', array_keys($data));
        $holders = implode(', ', array_fill(0, count($data), '?'));

        $stmt = $this->pdo->prepare(
            "INSERT INTO {$this->table} ({$cols}) VALUES ({$holders})"
        );
        $stmt->execute(array_values($data));
        return $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $campos = [
            'oportunidade_id', 'lead_id', 'cliente_id',
            'cliente_nome', 'cliente_razao_social', 'cliente_cnpj_cpf',
            'cliente_email', 'cliente_telefone', 'cliente_endereco',
            'cliente_cidade', 'cliente_estado', 'cliente_cep', 'cliente_responsavel',
            'titulo', 'descricao', 'validade_proposta', 'status',
            'subtotal', 'desconto_tipo', 'desconto_valor', 'desconto_total', 'total',
            'prazo_entrega', 'condicao_pagamento', 'frete_tipo', 'frete_valor',
            'local_entrega', 'observacoes', 'notas_internas',
            'enviado_em', 'visualizado_em', 'aceito_em', 'pdf_path',
        ];

        $data = array_intersect_key($data, array_flip($campos));
        if (empty($data)) return false;

        $sets   = implode(', ', array_map(fn($c) => "{$c} = ?", array_keys($data)));
        $vals   = array_values($data);
        $vals[] = $id;

        $stmt = $this->pdo->prepare("UPDATE {$this->table} SET {$sets} WHERE id = ?");
        return $stmt->execute($vals);
    }

    public function updateStatus(int $id, string $status, int $usuarioId, string $obs = ''): bool
    {
        $proposta = $this->findById($id);
        if (!$proposta) return false;

        $camposExtra = [];
        if ($status === 'enviada')    $camposExtra['enviado_em']    = date('Y-m-d H:i:s');
        if ($status === 'visualizada') $camposExtra['visualizado_em'] = date('Y-m-d H:i:s');
        if ($status === 'aceita')     $camposExtra['aceito_em']     = date('Y-m-d H:i:s');

        $this->update($id, array_merge(['status' => $status], $camposExtra));

        // Registrar no histórico
        $stmt = $this->pdo->prepare(
            "INSERT INTO crm_proposta_historico
             (proposta_id, usuario_id, status_de, status_para, observacao)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([$id, $usuarioId, $proposta->status, $status, $obs]);

        return true;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE id = ?");
        return $stmt->execute([$id]);
    }

    // ─── Itens ───────────────────────────────────────────────────────────────

    public function getItens(int $propostaId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM crm_proposta_itens
             WHERE proposta_id = ? ORDER BY ordem ASC, id ASC"
        );
        $stmt->execute([$propostaId]);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    public function salvarItens(int $propostaId, array $itens): void
    {
        // Remove itens anteriores
        $this->pdo->prepare("DELETE FROM crm_proposta_itens WHERE proposta_id = ?")
                  ->execute([$propostaId]);

        $stmt = $this->pdo->prepare(
            "INSERT INTO crm_proposta_itens
             (proposta_id, produto_id, codigo, descricao, unidade, quantidade,
              preco_custo, margem_lucro, preco_unitario, desconto_item, total_item, ordem)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        foreach ($itens as $i => $item) {
            $qtd       = (float) ($item['quantidade']     ?? 1);
            $preco     = (float) ($item['preco_unitario'] ?? 0);
            $descItem  = (float) ($item['desconto_item']  ?? 0);
            $totalItem = $qtd * $preco * (1 - $descItem / 100);

            $stmt->execute([
                $propostaId,
                $item['produto_id']    ?? null,
                $item['codigo']        ?? null,
                $item['descricao']     ?? '',
                $item['unidade']       ?? 'un',
                $qtd,
                $item['preco_custo']   ?? 0,
                $item['margem_lucro']  ?? 0,
                $preco,
                $descItem,
                round($totalItem, 2),
                $i,
            ]);
        }
    }

    public function recalcularTotais(int $propostaId): void
    {
        $stmt = $this->pdo->prepare(
            "SELECT SUM(total_item) AS subtotal FROM crm_proposta_itens WHERE proposta_id = ?"
        );
        $stmt->execute([$propostaId]);
        $subtotal = (float) ($stmt->fetchObject()->subtotal ?? 0);

        $proposta = $this->findById($propostaId);
        $descTipo = $proposta->desconto_tipo  ?? null;
        $descVal  = (float) ($proposta->desconto_valor ?? 0);
        $frete    = (float) ($proposta->frete_valor    ?? 0);

        $descTotal = 0;
        if ($descTipo === 'percentual') {
            $descTotal = $subtotal * ($descVal / 100);
        } elseif ($descTipo === 'fixo') {
            $descTotal = $descVal;
        }

        $total = $subtotal - $descTotal + $frete;

        $this->pdo->prepare(
            "UPDATE {$this->table}
             SET subtotal = ?, desconto_total = ?, total = ?
             WHERE id = ?"
        )->execute([round($subtotal, 2), round($descTotal, 2), round($total, 2), $propostaId]);
    }

    // ─── Histórico ───────────────────────────────────────────────────────────

    public function getHistorico(int $propostaId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT h.*, u.name AS usuario_nome
             FROM crm_proposta_historico h
             LEFT JOIN users u ON u.id = h.usuario_id
             WHERE h.proposta_id = ?
             ORDER BY h.created_at DESC"
        );
        $stmt->execute([$propostaId]);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    // ─── Busca de oportunidade para importar dados ────────────────────────────

    public function buscarOportunidade(int $opId, int $usuarioId): object|false
    {
        $stmt = $this->pdo->prepare(
            "SELECT o.*,
                    COALESCE(l.nome, c.razao_social, c.nome_fantasia) AS cliente_nome_display,
                    COALESCE(l.cnpj, c.cpf_cnpj) AS cliente_doc,
                    COALESCE(l.email, c.email) AS cliente_email_display,
                    COALESCE(l.telefone, c.telefone) AS cliente_tel_display,
                    l.id AS lead_id_ref,
                    c.id AS cliente_id_ref,
                    c.razao_social, c.nome_fantasia, c.cpf_cnpj,
                    c.email AS c_email, c.telefone AS c_telefone,
                    c.endereco, c.numero AS c_numero, c.complemento,
                    c.bairro, c.cidade, c.estado, c.cep,
                    c.responsavel_nome
             FROM crm_oportunidades o
             LEFT JOIN crm_leads l ON l.id = o.lead_id
             LEFT JOIN clientes  c ON c.id = o.cliente_id
             WHERE o.id = ? AND o.usuario_id = ?"
        );
        $stmt->execute([$opId, $usuarioId]);
        return $stmt->fetchObject();
    }

    // ─── KPIs ────────────────────────────────────────────────────────────────

    public function kpisByUsuarioId(int $usuarioId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT
               COUNT(*) AS total,
               SUM(CASE WHEN status = 'gerada'    THEN 1 ELSE 0 END) AS geradas,
               SUM(CASE WHEN status = 'enviada'   THEN 1 ELSE 0 END) AS enviadas,
               SUM(CASE WHEN status = 'aceita'    THEN 1 ELSE 0 END) AS aceitas,
               SUM(CASE WHEN status = 'recusada'  THEN 1 ELSE 0 END) AS recusadas,
               SUM(CASE WHEN status = 'expirada'  THEN 1 ELSE 0 END) AS expiradas,
               SUM(CASE WHEN status = 'aceita'    THEN total ELSE 0 END) AS valor_aceito,
               SUM(CASE WHEN status IN ('gerada','enviada','visualizada') THEN total ELSE 0 END) AS valor_pipeline
             FROM {$this->table}
             WHERE usuario_id = ?"
        );
        $stmt->execute([$usuarioId]);
        return (array) $stmt->fetchObject();
    }
    // ─── Aceite / Assinatura ──────────────────────────────────────────────────

    public function registrarEventoAceite(int $propostaId, string $evento, array $dados = []): void
    {
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO crm_proposta_aceite
                    (proposta_id, evento, nome_assinante, ip, user_agent,
                     assinatura_tipo, assinatura_imagem_path, motivo_recusa)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $propostaId,
                $evento,
                $dados['nome_assinante']         ?? null,
                $dados['ip']                     ?? null,
                $dados['user_agent']             ?? null,
                $dados['assinatura_tipo']        ?? null,
                $dados['assinatura_imagem_path'] ?? null,
                $dados['motivo_recusa']          ?? null,
            ]);
        } catch (\Throwable $e) {
            // Tabela pode não existir ainda — não bloquear o fluxo
            error_log('[CrmProposta] registrarEventoAceite: ' . $e->getMessage());
        }
    }

    public function getEventosAceite(int $propostaId): array
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT * FROM crm_proposta_aceite WHERE proposta_id = ? ORDER BY created_at ASC"
            );
            $stmt->execute([$propostaId]);
            return $stmt->fetchAll(\PDO::FETCH_OBJ);
        } catch (\Throwable $e) {
            return [];
        }
    }

    // ─── Propostas por cliente (para o portal) ───────────────────────────────

    public function findByClienteIdAndTenantId(int $clienteId, int $tenantId, array $filtros = []): array
    {
        $where  = ['p.usuario_id = :uid'];
        $params = [':uid' => $tenantId];

        // Filtrar pelo cliente_id (campo na tabela crm_propostas pode ser cliente_id ou cliente_nome)
        // Tentamos pelo campo cliente_id se existir, senão pelo nome
        $where[]          = '(p.cliente_id = :cid OR p.cliente_nome = (SELECT COALESCE(razao_social, nome_fantasia, nome) FROM clientes WHERE id = :cid2 AND usuario_id = :uid2 LIMIT 1))';
        $params[':cid']   = $clienteId;
        $params[':cid2']  = $clienteId;
        $params[':uid2']  = $tenantId;

        if (!empty($filtros['status'])) {
            $where[]            = 'p.status = :status';
            $params[':status']  = $filtros['status'];
        }

        $sql = "SELECT p.* FROM {$this->table} p WHERE " . implode(' AND ', $where) . " ORDER BY p.created_at DESC";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(\PDO::FETCH_OBJ);
        } catch (\Throwable $e) {
            error_log('[CrmProposta] findByClienteIdAndTenantId: ' . $e->getMessage());
            return [];
        }
    }


}