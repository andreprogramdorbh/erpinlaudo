<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\View;

class DashboardController extends Controller
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    public function index(): void
    {
        $usuario    = Auth::user();
        $uid        = $usuario->id;
        $hoje       = date('Y-m-d');
        $mesAtual   = date('Y-m');
        $mesPassado = date('Y-m', strtotime('-1 month'));

        // --- Contas a Receber ---
        $stmt = $this->pdo->prepare("
            SELECT
                COALESCE(SUM(CASE WHEN status IN ('pendente','aberta') THEN valor ELSE 0 END),0) AS total_aberto,
                COALESCE(SUM(CASE WHEN status = 'recebida' THEN valor ELSE 0 END),0)             AS total_recebido,
                COALESCE(SUM(CASE WHEN status IN ('pendente','aberta') AND data_vencimento < :hoje THEN valor ELSE 0 END),0) AS total_vencido,
                COUNT(CASE WHEN status IN ('pendente','aberta') THEN 1 END)                      AS qtd_aberto,
                COUNT(CASE WHEN status = 'recebida' THEN 1 END)                                  AS qtd_recebido,
                COUNT(CASE WHEN status IN ('pendente','aberta') AND data_vencimento < :hoje2 THEN 1 END) AS qtd_vencido
            FROM contas_receber WHERE usuario_id = :uid
        ");
        $stmt->execute([':uid'=>$uid,':hoje'=>$hoje,':hoje2'=>$hoje]);
        $receber = $stmt->fetch(\PDO::FETCH_OBJ);

        // --- Contas a Pagar ---
        $stmt = $this->pdo->prepare("
            SELECT
                COALESCE(SUM(CASE WHEN status = 'aberta' THEN valor ELSE 0 END),0) AS total_aberto,
                COALESCE(SUM(CASE WHEN status = 'paga'   THEN valor ELSE 0 END),0) AS total_pago,
                COALESCE(SUM(CASE WHEN status = 'aberta' AND data_vencimento < :hoje THEN valor ELSE 0 END),0) AS total_vencido,
                COUNT(CASE WHEN status = 'aberta' THEN 1 END)                      AS qtd_aberto,
                COUNT(CASE WHEN status = 'aberta' AND data_vencimento < :hoje2 THEN 1 END) AS qtd_vencido
            FROM contas_pagar WHERE usuario_id = :uid
        ");
        $stmt->execute([':uid'=>$uid,':hoje'=>$hoje,':hoje2'=>$hoje]);
        $pagar = $stmt->fetch(\PDO::FETCH_OBJ);

        // --- Resultado do mes ---
        $stmt = $this->pdo->prepare("
            SELECT
                COALESCE(SUM(CASE WHEN tipo='receber' AND DATE_FORMAT(data_ref,'%Y-%m')=:mes  THEN valor ELSE 0 END),0) AS recebido_mes,
                COALESCE(SUM(CASE WHEN tipo='pagar'   AND DATE_FORMAT(data_ref,'%Y-%m')=:mes2 THEN valor ELSE 0 END),0) AS pago_mes
            FROM (
                SELECT valor,'receber' AS tipo, data_recebimento AS data_ref FROM contas_receber WHERE usuario_id=:uid
                UNION ALL
                SELECT valor,'pagar'   AS tipo, data_pagamento   AS data_ref FROM contas_pagar   WHERE usuario_id=:uid2
            ) t
        ");
        $stmt->execute([':uid'=>$uid,':uid2'=>$uid,':mes'=>$mesAtual,':mes2'=>$mesAtual]);
        $resultado = $stmt->fetch(\PDO::FETCH_OBJ);

        // --- Evolucao mensal 12 meses ---
        $stmt = $this->pdo->prepare("
            SELECT DATE_FORMAT(data_vencimento,'%Y-%m') AS mes,
                   COALESCE(SUM(CASE WHEN tipo='receber' THEN valor ELSE 0 END),0) AS receber,
                   COALESCE(SUM(CASE WHEN tipo='pagar'   THEN valor ELSE 0 END),0) AS pagar
            FROM (
                SELECT valor,data_vencimento,'receber' AS tipo FROM contas_receber WHERE usuario_id=:uid  AND data_vencimento>=DATE_SUB(CURDATE(),INTERVAL 12 MONTH)
                UNION ALL
                SELECT valor,data_vencimento,'pagar'   AS tipo FROM contas_pagar   WHERE usuario_id=:uid2 AND data_vencimento>=DATE_SUB(CURDATE(),INTERVAL 12 MONTH)
            ) t
            GROUP BY mes ORDER BY mes ASC
        ");
        $stmt->execute([':uid'=>$uid,':uid2'=>$uid]);
        $evolucaoMensal = $stmt->fetchAll(\PDO::FETCH_OBJ);

        // --- Notas Fiscais ---
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) AS total_nfs,
                   COALESCE(SUM(valor_total),0) AS valor_total,
                   COUNT(CASE WHEN DATE_FORMAT(data_emissao,'%Y-%m')=:mes THEN 1 END) AS nfs_mes,
                   COALESCE(SUM(CASE WHEN DATE_FORMAT(data_emissao,'%Y-%m')=:mes2 THEN valor_total ELSE 0 END),0) AS valor_mes,
                   COUNT(CASE WHEN status='emitida'   THEN 1 END) AS emitidas,
                   COUNT(CASE WHEN status='cancelada' THEN 1 END) AS canceladas
            FROM notas_fiscais WHERE usuario_id=:uid
        ");
        $stmt->execute([':uid'=>$uid,':mes'=>$mesAtual,':mes2'=>$mesAtual]);
        $nfs = $stmt->fetch(\PDO::FETCH_OBJ);

        // --- Clientes ---
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) AS total,
                   COUNT(CASE WHEN DATE_FORMAT(created_at,'%Y-%m')=:mes        THEN 1 END) AS novos_mes,
                   COUNT(CASE WHEN DATE_FORMAT(created_at,'%Y-%m')=:mes_passado THEN 1 END) AS novos_mes_passado
            FROM clientes WHERE usuario_id=:uid
        ");
        $stmt->execute([':uid'=>$uid,':mes'=>$mesAtual,':mes_passado'=>$mesPassado]);
        $clientes = $stmt->fetch(\PDO::FETCH_OBJ);

        // --- CRM Leads ---
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) AS total,
                   COUNT(CASE WHEN status_lead='novo'         THEN 1 END) AS novos,
                   COUNT(CASE WHEN status_lead='qualificado'  THEN 1 END) AS qualificados,
                   COUNT(CASE WHEN status_lead='oportunidade' THEN 1 END) AS oportunidades,
                   COUNT(CASE WHEN status_lead='convertido'   THEN 1 END) AS convertidos,
                   COUNT(CASE WHEN status_lead='perdido'      THEN 1 END) AS perdidos,
                   COUNT(CASE WHEN DATE_FORMAT(created_at,'%Y-%m')=:mes THEN 1 END) AS novos_mes
            FROM crm_leads WHERE usuario_id=:uid
        ");
        $stmt->execute([':uid'=>$uid,':mes'=>$mesAtual]);
        $leads = $stmt->fetch(\PDO::FETCH_OBJ);

        // --- CRM Oportunidades ---
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) AS total,
                   COUNT(CASE WHEN status_oportunidade='aberta'  THEN 1 END) AS abertas,
                   COUNT(CASE WHEN status_oportunidade='ganha'   THEN 1 END) AS ganhas,
                   COUNT(CASE WHEN status_oportunidade='perdida' THEN 1 END) AS perdidas,
                   COALESCE(SUM(CASE WHEN status_oportunidade='aberta' THEN valor_estimado ELSE 0 END),0) AS pipeline_valor,
                   COALESCE(SUM(CASE WHEN status_oportunidade='ganha'  THEN valor_estimado ELSE 0 END),0) AS ganho_valor,
                   COALESCE(AVG(CASE WHEN status_oportunidade='aberta' THEN probabilidade  ELSE NULL END),0) AS prob_media
            FROM crm_oportunidades WHERE usuario_id=:uid
        ");
        $stmt->execute([':uid'=>$uid]);
        $oportunidades = $stmt->fetch(\PDO::FETCH_OBJ);

        // --- Funil por etapa ---
        $stmt = $this->pdo->prepare("
            SELECT etapa_funil, COUNT(*) AS qtd, COALESCE(SUM(valor_estimado),0) AS valor
            FROM crm_oportunidades
            WHERE usuario_id=:uid AND status_oportunidade='aberta'
            GROUP BY etapa_funil
            ORDER BY FIELD(etapa_funil,'qualificacao','proposta','negociacao','fechamento')
        ");
        $stmt->execute([':uid'=>$uid]);
        $funilEtapas = $stmt->fetchAll(\PDO::FETCH_OBJ);

        // --- Proximos vencimentos (7 dias) ---
        $stmt = $this->pdo->prepare("
            SELECT cr.descricao, cr.valor, cr.data_vencimento, cr.status,
                   c.razao_social AS cliente_nome
            FROM contas_receber cr
            LEFT JOIN clientes c ON c.id=cr.cliente_id
            WHERE cr.usuario_id=:uid
              AND cr.status IN ('pendente','aberta')
              AND cr.data_vencimento BETWEEN :hoje AND DATE_ADD(:hoje2,INTERVAL 7 DAY)
            ORDER BY cr.data_vencimento ASC LIMIT 8
        ");
        $stmt->execute([':uid'=>$uid,':hoje'=>$hoje,':hoje2'=>$hoje]);
        $proximosVencimentos = $stmt->fetchAll(\PDO::FETCH_OBJ);

        // --- Contas vencidas ---
        $stmt = $this->pdo->prepare("
            SELECT cr.descricao, cr.valor, cr.data_vencimento,
                   c.razao_social AS cliente_nome,
                   DATEDIFF(:hoje,cr.data_vencimento) AS dias_atraso
            FROM contas_receber cr
            LEFT JOIN clientes c ON c.id=cr.cliente_id
            WHERE cr.usuario_id=:uid
              AND cr.status IN ('pendente','aberta')
              AND cr.data_vencimento < :hoje2
            ORDER BY cr.data_vencimento ASC LIMIT 6
        ");
        $stmt->execute([':uid'=>$uid,':hoje'=>$hoje,':hoje2'=>$hoje]);
        $contasVencidas = $stmt->fetchAll(\PDO::FETCH_OBJ);

        // --- Ultimas interacoes CRM ---
        $stmt = $this->pdo->prepare("
            SELECT i.tipo_interacao, i.descricao, i.created_at,
                   COALESCE(l.nome_contato, o.titulo) AS nome_referencia,
                   IF(i.lead_id IS NOT NULL,'lead','oportunidade') AS origem,
                   COALESCE(i.lead_id, i.oportunidade_id) AS ref_id
            FROM crm_interacoes i
            LEFT JOIN crm_leads l ON l.id=i.lead_id
            LEFT JOIN crm_oportunidades o ON o.id=i.oportunidade_id
            WHERE i.usuario_id=:uid
            ORDER BY i.created_at DESC LIMIT 6
        ");
        $stmt->execute([':uid'=>$uid]);
        $ultimasInteracoes = $stmt->fetchAll(\PDO::FETCH_OBJ);

        // --- Faturamento mensal 6 meses ---
        $stmt = $this->pdo->prepare("
            SELECT DATE_FORMAT(data_emissao,'%Y-%m') AS mes, COUNT(*) AS qtd,
                   COALESCE(SUM(valor_total),0) AS valor
            FROM notas_fiscais
            WHERE usuario_id=:uid AND data_emissao>=DATE_SUB(CURDATE(),INTERVAL 6 MONTH)
              AND status IN ('emitida','importada')
            GROUP BY mes ORDER BY mes ASC
        ");
        $stmt->execute([':uid'=>$uid]);
        $faturamentoMensal = $stmt->fetchAll(\PDO::FETCH_OBJ);

        // --- Ultimas NFs ---
        $stmt = $this->pdo->prepare("
            SELECT nf.numero_nf, nf.valor_total, nf.data_emissao, nf.status,
                   c.razao_social AS cliente_nome
            FROM notas_fiscais nf
            LEFT JOIN clientes c ON c.id=nf.cliente_id
            WHERE nf.usuario_id=:uid
            ORDER BY nf.data_emissao DESC, nf.id DESC LIMIT 5
        ");
        $stmt->execute([':uid'=>$uid]);
        $ultimasNfs = $stmt->fetchAll(\PDO::FETCH_OBJ);

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
        ]);
    }
}
