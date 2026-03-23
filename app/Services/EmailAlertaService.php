<?php

namespace App\Services;

use App\Models\EmailAlerta;
use App\Models\User;
use App\Core\Database;
use App\Core\Audit\AuditLogger;
use PDO;

/**
 * EmailAlertaService
 * Processa e dispara os alertas de e-mail configurados por módulo.
 * Pode ser chamado por um cron job ou manualmente via controller.
 */
class EmailAlertaService
{
    private EmailAlerta $alertaModel;
    private MailService $mailService;
    private int $usuarioId;
    private PDO $pdo;

    public function __construct(int $usuarioId)
    {
        $this->usuarioId   = $usuarioId;
        $this->alertaModel = new EmailAlerta();
        $this->mailService = new MailService();
        $this->pdo         = Database::getInstance();
    }

    // -------------------------------------------------------------------------
    // Processamento por módulo
    // -------------------------------------------------------------------------

    /**
     * Processa todos os alertas ativos do usuário.
     * Retorna array com resumo de disparos.
     */
    public function processarTodos(): array
    {
        $resultado = ['enviados' => 0, 'falhas' => 0, 'ignorados' => 0];

        foreach (['financeiro', 'faturamento', 'crm'] as $modulo) {
            $parcial = $this->processarModulo($modulo);
            $resultado['enviados']  += $parcial['enviados'];
            $resultado['falhas']    += $parcial['falhas'];
            $resultado['ignorados'] += $parcial['ignorados'];
        }

        return $resultado;
    }

    public function processarModulo(string $modulo): array
    {
        $alertas   = $this->alertaModel->findAtivosParaDisparo($modulo, $this->usuarioId);
        $resultado = ['enviados' => 0, 'falhas' => 0, 'ignorados' => 0];

        foreach ($alertas as $alerta) {
            try {
                $disparos = $this->processarAlerta($alerta);
                $resultado['enviados']  += $disparos['enviados'];
                $resultado['falhas']    += $disparos['falhas'];
                $resultado['ignorados'] += $disparos['ignorados'];
            } catch (\Throwable $e) {
                AuditLogger::log('email_alerta_erro', [
                    'alerta_id' => $alerta->id,
                    'codigo'    => $alerta->codigo,
                    'error'     => $e->getMessage(),
                ]);
                $resultado['falhas']++;
            }
        }

        return $resultado;
    }

    /**
     * Processa um único alerta e retorna os registros que devem gerar disparo.
     */
    public function processarAlerta(object $alerta): array
    {
        $resultado = ['enviados' => 0, 'falhas' => 0, 'ignorados' => 0];
        $registros = $this->buscarRegistros($alerta);

        foreach ($registros as $registro) {
            $destinatarios = $this->resolverDestinatarios($alerta, $registro);
            $vars          = $this->extrairVariaveis($alerta, $registro);

            foreach ($destinatarios as $email) {
                if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $resultado['ignorados']++;
                    continue;
                }

                $assunto = $this->renderTemplate($alerta->assunto_template, $vars);
                $corpo   = $this->renderTemplate($alerta->corpo_template, $vars);

                try {
                    $this->mailService->send($email, $assunto, $corpo);
                    $this->alertaModel->registrarDisparo(
                        (int) $alerta->id, $this->usuarioId,
                        $email, $assunto, 'enviado', null,
                        $alerta->modulo . ':' . ($registro->id ?? '?')
                    );
                    $resultado['enviados']++;
                } catch (\Throwable $e) {
                    $this->alertaModel->registrarDisparo(
                        (int) $alerta->id, $this->usuarioId,
                        $email, $assunto, 'falha', $e->getMessage(),
                        $alerta->modulo . ':' . ($registro->id ?? '?')
                    );
                    $resultado['falhas']++;
                }
            }
        }

        return $resultado;
    }

    // -------------------------------------------------------------------------
    // Busca de registros por código de alerta (queries PDO diretas)
    // -------------------------------------------------------------------------

    private function buscarRegistros(object $alerta): array
    {
        $dias = (int) ($alerta->antecedencia_dias ?? 0);
        $uid  = $this->usuarioId;

        switch ($alerta->codigo) {

            // ── Contas a Receber vencendo em N dias ──────────────────────────
            case 'financeiro_receber_vencer_3d':
                $dataAlvo = date('Y-m-d', strtotime("+{$dias} days"));
                $stmt = $this->pdo->prepare(
                    "SELECT cr.*, c.razao_social AS cliente_nome
                     FROM contas_receber cr
                     LEFT JOIN clientes c ON c.id = cr.cliente_id
                     WHERE cr.usuario_id = :uid
                       AND cr.status = 'aberta'
                       AND cr.data_vencimento = :data"
                );
                $stmt->execute([':uid' => $uid, ':data' => $dataAlvo]);
                return $stmt->fetchAll(PDO::FETCH_OBJ);

            // ── Contas a Receber em atraso ────────────────────────────────────
            case 'financeiro_receber_atraso':
                $hoje = date('Y-m-d');
                $stmt = $this->pdo->prepare(
                    "SELECT cr.*, c.razao_social AS cliente_nome
                     FROM contas_receber cr
                     LEFT JOIN clientes c ON c.id = cr.cliente_id
                     WHERE cr.usuario_id = :uid
                       AND cr.status = 'aberta'
                       AND cr.data_vencimento < :hoje"
                );
                $stmt->execute([':uid' => $uid, ':hoje' => $hoje]);
                return $stmt->fetchAll(PDO::FETCH_OBJ);

            // ── Contas a Pagar vencendo em N dias ────────────────────────────
            case 'financeiro_pagar_vencer_3d':
                $dataAlvo = date('Y-m-d', strtotime("+{$dias} days"));
                $stmt = $this->pdo->prepare(
                    "SELECT cp.*, f.nome AS fornecedor_nome
                     FROM contas_pagar cp
                     LEFT JOIN fornecedores f ON f.id = cp.fornecedor_id
                     WHERE cp.usuario_id = :uid
                       AND cp.status = 'aberta'
                       AND cp.data_vencimento = :data"
                );
                $stmt->execute([':uid' => $uid, ':data' => $dataAlvo]);
                return $stmt->fetchAll(PDO::FETCH_OBJ);

            // ── Contas a Pagar em atraso ──────────────────────────────────────
            case 'financeiro_pagar_atraso':
                $hoje = date('Y-m-d');
                $stmt = $this->pdo->prepare(
                    "SELECT cp.*, f.nome AS fornecedor_nome
                     FROM contas_pagar cp
                     LEFT JOIN fornecedores f ON f.id = cp.fornecedor_id
                     WHERE cp.usuario_id = :uid
                       AND cp.status = 'aberta'
                       AND cp.data_vencimento < :hoje"
                );
                $stmt->execute([':uid' => $uid, ':hoje' => $hoje]);
                return $stmt->fetchAll(PDO::FETCH_OBJ);

            // ── Resumo Financeiro Diário ──────────────────────────────────────
            case 'financeiro_resumo_diario':
                // Retorna um único objeto-resumo para o template
                $hoje = date('Y-m-d');
                $stmt = $this->pdo->prepare(
                    "SELECT
                        (SELECT COUNT(*) FROM contas_receber WHERE usuario_id = :uid AND status = 'aberta' AND data_vencimento < :hoje) AS receber_atrasadas,
                        (SELECT COALESCE(SUM(valor),0) FROM contas_receber WHERE usuario_id = :uid AND status = 'aberta' AND data_vencimento < :hoje) AS receber_valor_atrasado,
                        (SELECT COUNT(*) FROM contas_pagar WHERE usuario_id = :uid AND status = 'aberta' AND data_vencimento < :hoje) AS pagar_atrasadas,
                        (SELECT COALESCE(SUM(valor),0) FROM contas_pagar WHERE usuario_id = :uid AND status = 'aberta' AND data_vencimento < :hoje) AS pagar_valor_atrasado,
                        (SELECT COUNT(*) FROM contas_receber WHERE usuario_id = :uid AND status = 'aberta' AND data_vencimento BETWEEN :hoje AND DATE_ADD(:hoje, INTERVAL 7 DAY)) AS receber_proximos_7d,
                        (SELECT COUNT(*) FROM contas_pagar WHERE usuario_id = :uid AND status = 'aberta' AND data_vencimento BETWEEN :hoje AND DATE_ADD(:hoje, INTERVAL 7 DAY)) AS pagar_proximos_7d"
                );
                $stmt->execute([':uid' => $uid, ':hoje' => $hoje]);
                $row = $stmt->fetch(PDO::FETCH_OBJ);
                return $row ? [$row] : [];

            // ── Nota Fiscal Emitida hoje ──────────────────────────────────────
            case 'faturamento_fatura_emitida':
                $hoje = date('Y-m-d');
                $stmt = $this->pdo->prepare(
                    "SELECT nf.*, nf.numero_nf AS numero_fatura, c.razao_social AS cliente_nome
                     FROM notas_fiscais nf
                     LEFT JOIN clientes c ON c.id = nf.cliente_id
                     WHERE nf.usuario_id = :uid
                       AND nf.status = 'emitida'
                       AND nf.data_emissao = :hoje"
                );
                $stmt->execute([':uid' => $uid, ':hoje' => $hoje]);
                return $stmt->fetchAll(PDO::FETCH_OBJ);

            // ── Contas a Receber (faturamento) vencendo em N dias ─────────────
            // Nota: o módulo de faturamento usa contas_receber como base de cobranças
            case 'faturamento_fatura_vencer_2d':
                $dataAlvo = date('Y-m-d', strtotime("+{$dias} days"));
                $stmt = $this->pdo->prepare(
                    "SELECT cr.*, c.razao_social AS cliente_nome, cr.id AS numero_fatura
                     FROM contas_receber cr
                     LEFT JOIN clientes c ON c.id = cr.cliente_id
                     WHERE cr.usuario_id = :uid
                       AND cr.status = 'aberta'
                       AND cr.data_vencimento = :data"
                );
                $stmt->execute([':uid' => $uid, ':data' => $dataAlvo]);
                return $stmt->fetchAll(PDO::FETCH_OBJ);

            // ── Contas a Receber (faturamento) em atraso ──────────────────────
            case 'faturamento_fatura_atraso':
                $hoje = date('Y-m-d');
                $stmt = $this->pdo->prepare(
                    "SELECT cr.*, c.razao_social AS cliente_nome, cr.id AS numero_fatura
                     FROM contas_receber cr
                     LEFT JOIN clientes c ON c.id = cr.cliente_id
                     WHERE cr.usuario_id = :uid
                       AND cr.status = 'aberta'
                       AND cr.data_vencimento < :hoje"
                );
                $stmt->execute([':uid' => $uid, ':hoje' => $hoje]);
                return $stmt->fetchAll(PDO::FETCH_OBJ);

            // ── Lead sem contato há 7+ dias ───────────────────────────────────
            case 'crm_lead_sem_contato':
                $limite = date('Y-m-d', strtotime('-7 days'));
                $stmt = $this->pdo->prepare(
                    "SELECT l.*
                     FROM crm_leads l
                     WHERE l.usuario_id = :uid
                       AND l.status_lead IN ('novo', 'em_contato', 'qualificado')
                       AND (l.data_proximo_contato IS NULL OR l.data_proximo_contato <= :limite)"
                );
                $stmt->execute([':uid' => $uid, ':limite' => $limite]);
                return $stmt->fetchAll(PDO::FETCH_OBJ);

            // ── Lead com próximo contato hoje ─────────────────────────────────
            case 'crm_lead_proximo_contato_hoje':
                $hoje = date('Y-m-d');
                $stmt = $this->pdo->prepare(
                    "SELECT l.*
                     FROM crm_leads l
                     WHERE l.usuario_id = :uid
                       AND l.data_proximo_contato = :hoje"
                );
                $stmt->execute([':uid' => $uid, ':hoje' => $hoje]);
                return $stmt->fetchAll(PDO::FETCH_OBJ);

            // ── Oportunidade vencendo em N dias ───────────────────────────────
            case 'crm_oportunidade_vencer_3d':
                $dataAlvo = date('Y-m-d', strtotime("+{$dias} days"));
                $stmt = $this->pdo->prepare(
                    "SELECT o.*, COALESCE(l.nome_lead, c.razao_social) AS nome_contato
                     FROM crm_oportunidades o
                     LEFT JOIN crm_leads l ON l.id = o.lead_id
                     LEFT JOIN clientes  c ON c.id = o.cliente_id
                     WHERE o.usuario_id = :uid
                       AND o.status_oportunidade = 'aberta'
                       AND o.data_fechamento_prevista = :data"
                );
                $stmt->execute([':uid' => $uid, ':data' => $dataAlvo]);
                return $stmt->fetchAll(PDO::FETCH_OBJ);

            // ── Oportunidade com fechamento vencido ───────────────────────────
            case 'crm_oportunidade_vencida':
                $hoje = date('Y-m-d');
                $stmt = $this->pdo->prepare(
                    "SELECT o.*, COALESCE(l.nome_lead, c.razao_social) AS nome_contato
                     FROM crm_oportunidades o
                     LEFT JOIN crm_leads l ON l.id = o.lead_id
                     LEFT JOIN clientes  c ON c.id = o.cliente_id
                     WHERE o.usuario_id = :uid
                       AND o.status_oportunidade = 'aberta'
                       AND o.data_fechamento_prevista < :hoje"
                );
                $stmt->execute([':uid' => $uid, ':hoje' => $hoje]);
                return $stmt->fetchAll(PDO::FETCH_OBJ);

            default:
                return [];
        }
    }

    // -------------------------------------------------------------------------
    // Resolução de destinatários
    // -------------------------------------------------------------------------

    private function resolverDestinatarios(object $alerta, object $registro): array
    {
        $tokens = json_decode($alerta->destinatarios ?? '["admin"]', true) ?: ['admin'];
        $emails = [];

        foreach ($tokens as $token) {
            if (filter_var($token, FILTER_VALIDATE_EMAIL)) {
                $emails[] = $token;
                continue;
            }

            switch ($token) {
                case 'admin':
                case 'financeiro':
                    // Busca usuários com role admin ou superadmin
                    $users = (new User())->findByRole(['admin', 'superadmin']);
                    foreach ($users as $u) {
                        $emails[] = $u->email;
                    }
                    break;

                case 'vendedor':
                    // Usuário responsável pelo registro (se disponível)
                    if (!empty($registro->usuario_id)) {
                        $u = (new User())->findById((int) $registro->usuario_id);
                        if ($u) {
                            $emails[] = $u->email;
                        }
                    }
                    break;

                case 'cliente':
                    // E-mail do cliente no registro
                    if (!empty($registro->cliente_email)) {
                        $emails[] = $registro->cliente_email;
                    } elseif (!empty($registro->email)) {
                        $emails[] = $registro->email;
                    }
                    break;
            }
        }

        // CC
        if (!empty($alerta->cc)) {
            $cc = json_decode($alerta->cc, true) ?: [];
            foreach ($cc as $e) {
                if (filter_var($e, FILTER_VALIDATE_EMAIL)) {
                    $emails[] = $e;
                }
            }
        }

        return array_unique(array_filter($emails));
    }

    // -------------------------------------------------------------------------
    // Extração de variáveis para templates
    // -------------------------------------------------------------------------

    private function extrairVariaveis(object $alerta, object $registro): array
    {
        $hoje = date('d/m/Y');
        $vars = [
            '{data}'    => $hoje,
            '{sistema}' => 'ERP InLaudo',
        ];

        // Variáveis comuns de vencimento
        if (!empty($registro->data_vencimento)) {
            $venc = strtotime($registro->data_vencimento);
            $diff = (int) floor((time() - $venc) / 86400);
            $vars['{vencimento}'] = date('d/m/Y', $venc);
            $vars['{dias}']       = abs($diff);
        }

        if (!empty($registro->data_fechamento_prevista)) {
            $venc = strtotime($registro->data_fechamento_prevista);
            $diff = (int) floor((time() - $venc) / 86400);
            $vars['{data_fechamento}'] = date('d/m/Y', $venc);
            $vars['{dias}']            = abs($diff);
        }

        // Valor
        if (isset($registro->valor)) {
            $vars['{valor}'] = number_format((float) $registro->valor, 2, ',', '.');
        }

        // Financeiro — Contas a Receber
        $vars['{cliente}']    = $registro->cliente_nome ?? $registro->razao_social ?? '';
        $vars['{descricao}']  = $registro->descricao ?? '';

        // Financeiro — Contas a Pagar
        $vars['{fornecedor}'] = $registro->fornecedor_nome ?? '';

        // Faturamento
        $vars['{numero_fatura}'] = $registro->numero ?? $registro->id ?? '';

        // CRM — Lead
        $vars['{lead}']           = $registro->nome_lead ?? '';
        $vars['{empresa}']        = $registro->cnpj ?? '';
        $vars['{telefone}']       = $registro->telefone ?? $registro->celular ?? '';
        $vars['{status_lead}']    = $registro->status_lead ?? '';
        $vars['{ultimo_contato}'] = !empty($registro->updated_at)
            ? date('d/m/Y', strtotime($registro->updated_at)) : '—';
        $vars['{proximo_contato}'] = !empty($registro->data_proximo_contato)
            ? date('d/m/Y', strtotime($registro->data_proximo_contato)) : '—';

        // CRM — Oportunidade
        $vars['{oportunidade}']  = $registro->titulo_oportunidade ?? $registro->nome_contato ?? '';
        $vars['{probabilidade}'] = $registro->probabilidade_sucesso ?? '';
        $vars['{etapa}']         = $registro->etapa_funil ?? '';

        // Resumo financeiro diário
        $vars['{receber_atrasadas}']      = $registro->receber_atrasadas ?? '';
        $vars['{receber_valor_atrasado}'] = isset($registro->receber_valor_atrasado)
            ? number_format((float) $registro->receber_valor_atrasado, 2, ',', '.') : '';
        $vars['{pagar_atrasadas}']        = $registro->pagar_atrasadas ?? '';
        $vars['{pagar_valor_atrasado}']   = isset($registro->pagar_valor_atrasado)
            ? number_format((float) $registro->pagar_valor_atrasado, 2, ',', '.') : '';
        $vars['{receber_proximos_7d}']    = $registro->receber_proximos_7d ?? '';
        $vars['{pagar_proximos_7d}']      = $registro->pagar_proximos_7d ?? '';

        // Vendedor
        if (!empty($registro->usuario_id)) {
            $u = (new User())->findById((int) $registro->usuario_id);
            $vars['{vendedor}'] = $u ? $u->name : 'Vendedor';
        } else {
            $vars['{vendedor}'] = 'Equipe Comercial';
        }

        return $vars;
    }

    // -------------------------------------------------------------------------
    // Render de template
    // -------------------------------------------------------------------------

    private function renderTemplate(string $template, array $vars): string
    {
        return str_replace(array_keys($vars), array_values($vars), $template);
    }
}
