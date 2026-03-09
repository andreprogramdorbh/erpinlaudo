<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Logger;
use App\Core\View;

class DashboardController extends Controller
{
    private \PDO $pdo;
    private Logger $logger;
    private array $columnCache = [];

    public function __construct()
    {
        $this->pdo = Database::getInstance();
        $this->logger = new Logger();
    }

    private function isMissingTable(\PDOException $e): bool
    {
        $code = (string) $e->getCode();
        if ($code === '42S02') {
            return true;
        }
        $msg = $e->getMessage();
        return (strpos($msg, 'Base table or view not found') !== false) || (strpos($msg, "doesn't exist") !== false);
    }

    private function hasColumn(string $table, string $column): bool
    {
        $key = strtolower($table . '.' . $column);
        if (array_key_exists($key, $this->columnCache)) {
            return (bool) $this->columnCache[$key];
        }

        try {
            $stmt = $this->pdo->prepare("
                SELECT 1
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = :t
                  AND COLUMN_NAME = :c
                LIMIT 1
            ");
            $stmt->execute([':t' => $table, ':c' => $column]);
            $exists = (bool) $stmt->fetchColumn();
        } catch (\Throwable $e) {
            $exists = false;
        }

        $this->columnCache[$key] = $exists;
        return $exists;
    }

    private function firstExistingColumn(string $table, array $candidates): ?string
    {
        foreach ($candidates as $col) {
            if ($this->hasColumn($table, (string) $col)) {
                return (string) $col;
            }
        }
        return null;
    }

    private function fetchObjSafe(string $label, string $sql, array $params, object $default): object
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch(\PDO::FETCH_OBJ) ?: $default;
        } catch (\PDOException $e) {
            if ($this->isMissingTable($e)) {
                $this->logger->warning('Dashboard: tabela ausente (ignorando)', [
                    'label' => $label,
                    'error' => $e->getMessage(),
                ]);
                $this->logger->auth('Dashboard: tabela ausente', [
                    'label' => $label,
                ]);
                return $default;
            }
            $this->logger->error('Dashboard: erro SQL', [
                'label' => $label,
                'error' => $e->getMessage(),
            ]);
            $this->logger->auth('Dashboard: erro SQL', [
                'label' => $label,
                'error' => $e->getMessage(),
            ]);
            return $default;
        }
    }

    private function fetchAllSafe(string $label, string $sql, array $params): array
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(\PDO::FETCH_OBJ) ?: [];
        } catch (\PDOException $e) {
            if ($this->isMissingTable($e)) {
                $this->logger->warning('Dashboard: tabela ausente (ignorando)', [
                    'label' => $label,
                    'error' => $e->getMessage(),
                ]);
                $this->logger->auth('Dashboard: tabela ausente', [
                    'label' => $label,
                ]);
                return [];
            }
            $this->logger->error('Dashboard: erro SQL', [
                'label' => $label,
                'error' => $e->getMessage(),
            ]);
            $this->logger->auth('Dashboard: erro SQL', [
                'label' => $label,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    public function index(): void
    {
        $usuario    = Auth::user();
        $uid        = $usuario->id;
        $hoje       = date('Y-m-d');
        $mesAtual   = date('Y-m');
        $mesPassado = date('Y-m', strtotime('-1 month'));

        // --- Contas a Receber ---
        $receber = $this->fetchObjSafe('contas_receber_resumo', "
            SELECT
                COALESCE(SUM(CASE WHEN status IN ('pendente','aberta') THEN valor ELSE 0 END),0) AS total_aberto,
                COALESCE(SUM(CASE WHEN status = 'recebida' THEN valor ELSE 0 END),0)             AS total_recebido,
                COALESCE(SUM(CASE WHEN status IN ('pendente','aberta') AND data_vencimento < :hoje THEN valor ELSE 0 END),0) AS total_vencido,
                COUNT(CASE WHEN status IN ('pendente','aberta') THEN 1 END)                      AS qtd_aberto,
                COUNT(CASE WHEN status = 'recebida' THEN 1 END)                                  AS qtd_recebido,
                COUNT(CASE WHEN status IN ('pendente','aberta') AND data_vencimento < :hoje2 THEN 1 END) AS qtd_vencido
            FROM contas_receber WHERE usuario_id = :uid
        ", [':uid'=>$uid,':hoje'=>$hoje,':hoje2'=>$hoje], (object)[
            'total_aberto' => 0,
            'total_recebido' => 0,
            'total_vencido' => 0,
            'qtd_aberto' => 0,
            'qtd_recebido' => 0,
            'qtd_vencido' => 0,
        ]);

        // --- Contas a Pagar ---
        $pagar = $this->fetchObjSafe('contas_pagar_resumo', "
            SELECT
                COALESCE(SUM(CASE WHEN status = 'aberta' THEN valor ELSE 0 END),0) AS total_aberto,
                COALESCE(SUM(CASE WHEN status = 'paga'   THEN valor ELSE 0 END),0) AS total_pago,
                COALESCE(SUM(CASE WHEN status = 'aberta' AND data_vencimento < :hoje THEN valor ELSE 0 END),0) AS total_vencido,
                COUNT(CASE WHEN status = 'aberta' THEN 1 END)                      AS qtd_aberto,
                COUNT(CASE WHEN status = 'aberta' AND data_vencimento < :hoje2 THEN 1 END) AS qtd_vencido
            FROM contas_pagar WHERE usuario_id = :uid
        ", [':uid'=>$uid,':hoje'=>$hoje,':hoje2'=>$hoje], (object)[
            'total_aberto' => 0,
            'total_pago' => 0,
            'total_vencido' => 0,
            'qtd_aberto' => 0,
            'qtd_vencido' => 0,
        ]);

        // --- Resultado do mes ---
        $resultado = $this->fetchObjSafe('resultado_mes', "
            SELECT
                COALESCE(SUM(CASE WHEN tipo='receber' AND DATE_FORMAT(data_ref,'%Y-%m')=:mes  THEN valor ELSE 0 END),0) AS recebido_mes,
                COALESCE(SUM(CASE WHEN tipo='pagar'   AND DATE_FORMAT(data_ref,'%Y-%m')=:mes2 THEN valor ELSE 0 END),0) AS pago_mes
            FROM (
                SELECT valor,'receber' AS tipo, data_recebimento AS data_ref FROM contas_receber WHERE usuario_id=:uid
                UNION ALL
                SELECT valor,'pagar'   AS tipo, data_pagamento   AS data_ref FROM contas_pagar   WHERE usuario_id=:uid2
            ) t
        ", [':uid'=>$uid,':uid2'=>$uid,':mes'=>$mesAtual,':mes2'=>$mesAtual], (object)[
            'recebido_mes' => 0,
            'pago_mes' => 0,
        ]);

        // --- Evolucao mensal 12 meses ---
        $evolucaoMensal = $this->fetchAllSafe('evolucao_mensal', "
            SELECT DATE_FORMAT(data_vencimento,'%Y-%m') AS mes,
                   COALESCE(SUM(CASE WHEN tipo='receber' THEN valor ELSE 0 END),0) AS receber,
                   COALESCE(SUM(CASE WHEN tipo='pagar'   THEN valor ELSE 0 END),0) AS pagar
            FROM (
                SELECT valor,data_vencimento,'receber' AS tipo FROM contas_receber WHERE usuario_id=:uid  AND data_vencimento>=DATE_SUB(CURDATE(),INTERVAL 12 MONTH)
                UNION ALL
                SELECT valor,data_vencimento,'pagar'   AS tipo FROM contas_pagar   WHERE usuario_id=:uid2 AND data_vencimento>=DATE_SUB(CURDATE(),INTERVAL 12 MONTH)
            ) t
            GROUP BY mes ORDER BY mes ASC
        ", [':uid'=>$uid,':uid2'=>$uid]);

        // --- Notas Fiscais ---
        $nfs = $this->fetchObjSafe('notas_fiscais_resumo', "
            SELECT COUNT(*) AS total_nfs,
                   COALESCE(SUM(valor_total),0) AS valor_total,
                   COUNT(CASE WHEN DATE_FORMAT(data_emissao,'%Y-%m')=:mes THEN 1 END) AS nfs_mes,
                   COALESCE(SUM(CASE WHEN DATE_FORMAT(data_emissao,'%Y-%m')=:mes2 THEN valor_total ELSE 0 END),0) AS valor_mes,
                   COUNT(CASE WHEN status='emitida'   THEN 1 END) AS emitidas,
                   COUNT(CASE WHEN status='cancelada' THEN 1 END) AS canceladas
            FROM notas_fiscais WHERE usuario_id=:uid
        ", [':uid'=>$uid,':mes'=>$mesAtual,':mes2'=>$mesAtual], (object)[
            'total_nfs' => 0,
            'valor_total' => 0,
            'nfs_mes' => 0,
            'valor_mes' => 0,
            'emitidas' => 0,
            'canceladas' => 0,
        ]);

        // --- Clientes ---
        // Alguns bancos antigos não possuem created_at em clientes.
        // Se não houver uma coluna de data, calcula apenas o total e zera os "novos".
        $clienteDateCol = $this->firstExistingColumn('clientes', ['created_at', 'data_cadastro', 'data_criacao', 'dt_cadastro', 'cadastrado_em', 'created']);
        if ($clienteDateCol) {
            $clientes = $this->fetchObjSafe('clientes_resumo', "
                SELECT COUNT(*) AS total,
                       COUNT(CASE WHEN DATE_FORMAT({$clienteDateCol},'%Y-%m')=:mes         THEN 1 END) AS novos_mes,
                       COUNT(CASE WHEN DATE_FORMAT({$clienteDateCol},'%Y-%m')=:mes_passado THEN 1 END) AS novos_mes_passado
                FROM clientes WHERE usuario_id=:uid
            ", [':uid'=>$uid,':mes'=>$mesAtual,':mes_passado'=>$mesPassado], (object)[
                'total' => 0,
                'novos_mes' => 0,
                'novos_mes_passado' => 0,
            ]);
        } else {
            $this->logger->warning('Dashboard: clientes sem coluna de data (novos_mes indisponível)', [
                'label' => 'clientes_resumo',
            ]);
            $this->logger->auth('Dashboard: clientes sem coluna de data', [
                'label' => 'clientes_resumo',
            ]);
            $clientes = $this->fetchObjSafe('clientes_total_only', "
                SELECT COUNT(*) AS total,
                       0 AS novos_mes,
                       0 AS novos_mes_passado
                FROM clientes WHERE usuario_id=:uid
            ", [':uid'=>$uid], (object)[
                'total' => 0,
                'novos_mes' => 0,
                'novos_mes_passado' => 0,
            ]);
        }

        // --- CRM Leads ---
        $leads = $this->fetchObjSafe('crm_leads_resumo', "
            SELECT COUNT(*) AS total,
                   COUNT(CASE WHEN status_lead='novo'         THEN 1 END) AS novos,
                   COUNT(CASE WHEN status_lead='qualificado'  THEN 1 END) AS qualificados,
                   COUNT(CASE WHEN status_lead='oportunidade' THEN 1 END) AS oportunidades,
                   COUNT(CASE WHEN status_lead='convertido'   THEN 1 END) AS convertidos,
                   COUNT(CASE WHEN status_lead='perdido'      THEN 1 END) AS perdidos,
                   COUNT(CASE WHEN DATE_FORMAT(created_at,'%Y-%m')=:mes THEN 1 END) AS novos_mes
            FROM crm_leads WHERE usuario_id=:uid
        ", [':uid'=>$uid,':mes'=>$mesAtual], (object)[
            'total' => 0,
            'novos' => 0,
            'qualificados' => 0,
            'oportunidades' => 0,
            'convertidos' => 0,
            'perdidos' => 0,
            'novos_mes' => 0,
        ]);

        // --- CRM Oportunidades ---
        $probCol = $this->firstExistingColumn('crm_oportunidades', ['probabilidade_sucesso', 'probabilidade']);
        $probExpr = $probCol
            ? "COALESCE(AVG(CASE WHEN status_oportunidade='aberta' THEN {$probCol} ELSE NULL END),0)"
            : "0";

        $oportunidades = $this->fetchObjSafe('crm_oportunidades_resumo', "
            SELECT COUNT(*) AS total,
                   COUNT(CASE WHEN status_oportunidade='aberta'  THEN 1 END) AS abertas,
                   COUNT(CASE WHEN status_oportunidade='ganha'   THEN 1 END) AS ganhas,
                   COUNT(CASE WHEN status_oportunidade='perdida' THEN 1 END) AS perdidas,
                    COALESCE(SUM(CASE WHEN status_oportunidade='aberta' THEN valor_estimado ELSE 0 END),0) AS pipeline_valor,
                    COALESCE(SUM(CASE WHEN status_oportunidade='ganha'  THEN valor_estimado ELSE 0 END),0) AS ganho_valor,
                    {$probExpr} AS prob_media
            FROM crm_oportunidades WHERE usuario_id=:uid
        ", [':uid'=>$uid], (object)[
            'total' => 0,
            'abertas' => 0,
            'ganhas' => 0,
            'perdidas' => 0,
            'pipeline_valor' => 0,
            'ganho_valor' => 0,
            'prob_media' => 0,
        ]);

        // --- Funil por etapa ---
        $funilEtapas = $this->fetchAllSafe('crm_funil_etapas', "
            SELECT etapa_funil, COUNT(*) AS qtd, COALESCE(SUM(valor_estimado),0) AS valor
            FROM crm_oportunidades
            WHERE usuario_id=:uid AND status_oportunidade='aberta'
            GROUP BY etapa_funil
            ORDER BY FIELD(etapa_funil,'qualificacao','proposta','negociacao','fechamento')
        ", [':uid'=>$uid]);

        // --- Proximos vencimentos (7 dias) ---
        $proximosVencimentos = $this->fetchAllSafe('proximos_vencimentos', "
            SELECT cr.descricao, cr.valor, cr.data_vencimento, cr.status,
                   c.razao_social AS cliente_nome
            FROM contas_receber cr
            LEFT JOIN clientes c ON c.id=cr.cliente_id
            WHERE cr.usuario_id=:uid
              AND cr.status IN ('pendente','aberta')
              AND cr.data_vencimento BETWEEN :hoje AND DATE_ADD(:hoje2,INTERVAL 7 DAY)
            ORDER BY cr.data_vencimento ASC LIMIT 8
        ", [':uid'=>$uid,':hoje'=>$hoje,':hoje2'=>$hoje]);

        // --- Contas vencidas ---
        $contasVencidas = $this->fetchAllSafe('contas_vencidas', "
            SELECT cr.descricao, cr.valor, cr.data_vencimento,
                   c.razao_social AS cliente_nome,
                   DATEDIFF(:hoje,cr.data_vencimento) AS dias_atraso
            FROM contas_receber cr
            LEFT JOIN clientes c ON c.id=cr.cliente_id
            WHERE cr.usuario_id=:uid
              AND cr.status IN ('pendente','aberta')
              AND cr.data_vencimento < :hoje2
            ORDER BY cr.data_vencimento ASC LIMIT 6
        ", [':uid'=>$uid,':hoje'=>$hoje,':hoje2'=>$hoje]);

        // --- Ultimas interacoes CRM ---
        $interacaoDataCol = $this->firstExistingColumn('crm_interacoes', ['data_interacao', 'created_at']);
        $interacaoOrderExpr = $interacaoDataCol ? "i.{$interacaoDataCol}" : "i.id";
        $interacaoCreatedExpr = $interacaoDataCol ? "i.{$interacaoDataCol}" : "NULL";
        $interacaoResumoCol = $this->firstExistingColumn('crm_interacoes', ['resumo', 'descricao']);
        $interacaoResumoExpr = $interacaoResumoCol ? "i.{$interacaoResumoCol}" : "NULL";

        $leadNomeCol = $this->firstExistingColumn('crm_leads', ['nome_lead', 'nome_contato', 'nome']);
        $leadNomeExpr = $leadNomeCol ? "l.{$leadNomeCol}" : "NULL";
        $oppTituloCol = $this->firstExistingColumn('crm_oportunidades', ['titulo_oportunidade', 'titulo']);
        $oppTituloExpr = $oppTituloCol ? "o.{$oppTituloCol}" : "NULL";

        if ($this->hasColumn('crm_interacoes', 'related_id') && $this->hasColumn('crm_interacoes', 'related_type')) {
            $ultimasInteracoes = $this->fetchAllSafe('crm_ultimas_interacoes', "
                SELECT i.tipo_interacao,
                       {$interacaoResumoExpr} AS descricao,
                       {$interacaoCreatedExpr} AS created_at,
                       COALESCE({$leadNomeExpr}, {$oppTituloExpr}) AS nome_referencia,
                       i.related_type AS origem,
                       i.related_id AS ref_id
                FROM crm_interacoes i
                LEFT JOIN crm_leads l ON l.id=i.related_id AND i.related_type='lead'
                LEFT JOIN crm_oportunidades o ON o.id=i.related_id AND i.related_type='oportunidade'
                WHERE i.usuario_id=:uid
                ORDER BY {$interacaoOrderExpr} DESC, i.id DESC LIMIT 6
            ", [':uid'=>$uid]);
        } else {
            $ultimasInteracoes = $this->fetchAllSafe('crm_ultimas_interacoes', "
                SELECT i.tipo_interacao,
                       {$interacaoResumoExpr} AS descricao,
                       {$interacaoCreatedExpr} AS created_at,
                       COALESCE({$leadNomeExpr}, {$oppTituloExpr}) AS nome_referencia,
                       IF(i.lead_id IS NOT NULL,'lead','oportunidade') AS origem,
                       COALESCE(i.lead_id, i.oportunidade_id) AS ref_id
                FROM crm_interacoes i
                LEFT JOIN crm_leads l ON l.id=i.lead_id
                LEFT JOIN crm_oportunidades o ON o.id=i.oportunidade_id
                WHERE i.usuario_id=:uid
                ORDER BY {$interacaoOrderExpr} DESC, i.id DESC LIMIT 6
            ", [':uid'=>$uid]);
        }

        // --- Faturamento mensal 6 meses ---
        $faturamentoMensal = $this->fetchAllSafe('notas_fiscais_faturamento_mensal', "
            SELECT DATE_FORMAT(data_emissao,'%Y-%m') AS mes, COUNT(*) AS qtd,
                   COALESCE(SUM(valor_total),0) AS valor
            FROM notas_fiscais
            WHERE usuario_id=:uid AND data_emissao>=DATE_SUB(CURDATE(),INTERVAL 6 MONTH)
              AND status IN ('emitida','importada')
            GROUP BY mes ORDER BY mes ASC
        ", [':uid'=>$uid]);

        // --- Ultimas NFs ---
        $ultimasNfs = $this->fetchAllSafe('notas_fiscais_ultimas', "
            SELECT nf.numero_nf, nf.valor_total, nf.data_emissao, nf.status,
                   c.razao_social AS cliente_nome
            FROM notas_fiscais nf
            LEFT JOIN clientes c ON c.id=nf.cliente_id
            WHERE nf.usuario_id=:uid
            ORDER BY nf.data_emissao DESC, nf.id DESC LIMIT 5
        ", [':uid'=>$uid]);

        View::render('dashboard/index', [
            'title'               => 'Dashboard',
            'usuario'             => $usuario,
            'receber'             => $receber,
            'pagar'               => $pagar,
            'resultado'           => $resultado,
            'evolucaoMensal'      => $evolucaoMensal,
            'nfs'                 => $nfs,
            'faturamentoMensal'   => $faturamentoMensal,
            'ultimasNfs'          => $ultimasNfs,
            'clientes'            => $clientes,
            'leads'               => $leads,
            'oportunidades'       => $oportunidades,
            'funilEtapas'         => $funilEtapas,
            'ultimasInteracoes'   => $ultimasInteracoes,
            'proximosVencimentos' => $proximosVencimentos,
            'contasVencidas'      => $contasVencidas,
            'mesAtual'            => date('Y-m'),
            'hoje'                => date('Y-m-d'),
            '_layout'             => 'erp',
        ]);
    }
}
