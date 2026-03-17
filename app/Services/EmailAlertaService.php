<?php

namespace App\Services;

use App\Models\EmailAlerta;
use App\Models\ContaPagar;
use App\Models\ContaReceber;
use App\Models\CrmLead;
use App\Models\CrmOportunidade;
use App\Models\User;
use App\Core\Audit\AuditLogger;

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

    public function __construct(int $usuarioId)
    {
        $this->usuarioId   = $usuarioId;
        $this->alertaModel = new EmailAlerta();
        $this->mailService = new MailService();
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
        $alertas = $this->alertaModel->findAtivosParaDisparo($modulo, $this->usuarioId);
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
    // Busca de registros por código de alerta
    // -------------------------------------------------------------------------

    private function buscarRegistros(object $alerta): array
    {
        $dias = (int) $alerta->antecedencia_dias;
        $uid  = $this->usuarioId;

        switch ($alerta->codigo) {

            // Contas a Receber vencendo em N dias
            case 'financeiro_receber_vencer_3d':
                return (new ContaReceber())->findAll([
                    'usuario_id' => $uid,
                    'status'     => 'aberta',
                    'vencimento_de' => date('Y-m-d', strtotime("+{$dias} days")),
                    'vencimento_ate' => date('Y-m-d', strtotime("+{$dias} days")),
                ]);

            // Contas a Receber em atraso
            case 'financeiro_receber_atraso':
                return (new ContaReceber())->findAll([
                    'usuario_id'  => $uid,
                    'status'      => 'aberta',
                    'vencimento_ate' => date('Y-m-d', strtotime('-1 day')),
                ]);

            // Contas a Pagar vencendo em N dias
            case 'financeiro_pagar_vencer_3d':
                return (new ContaPagar())->findAll([
                    'usuario_id' => $uid,
                    'status'     => 'aberta',
                    'vencimento_de' => date('Y-m-d', strtotime("+{$dias} days")),
                    'vencimento_ate' => date('Y-m-d', strtotime("+{$dias} days")),
                ]);

            // Contas a Pagar em atraso
            case 'financeiro_pagar_atraso':
                return (new ContaPagar())->findAll([
                    'usuario_id'  => $uid,
                    'status'      => 'aberta',
                    'vencimento_ate' => date('Y-m-d', strtotime('-1 day')),
                ]);

            // Leads sem contato há 7+ dias
            case 'crm_lead_sem_contato':
                return (new CrmLead())->findAll([
                    'usuario_id' => $uid,
                    'status_lead' => ['novo', 'em_contato', 'qualificado'],
                    'proximo_contato_ate' => date('Y-m-d', strtotime('-7 days')),
                ]);

            // Leads com próximo contato hoje
            case 'crm_lead_proximo_contato_hoje':
                return (new CrmLead())->findAll([
                    'usuario_id'          => $uid,
                    'data_proximo_contato' => date('Y-m-d'),
                ]);

            // Oportunidades vencendo em N dias
            case 'crm_oportunidade_vencer_3d':
                return (new CrmOportunidade())->findAll([
                    'usuario_id'           => $uid,
                    'status_oportunidade'  => 'aberta',
                    'fechamento_de'        => date('Y-m-d', strtotime("+{$dias} days")),
                    'fechamento_ate'       => date('Y-m-d', strtotime("+{$dias} days")),
                ]);

            // Oportunidades com fechamento vencido
            case 'crm_oportunidade_vencida':
                return (new CrmOportunidade())->findAll([
                    'usuario_id'          => $uid,
                    'status_oportunidade' => 'aberta',
                    'fechamento_ate'      => date('Y-m-d', strtotime('-1 day')),
                ]);

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
        $vars['{oportunidade}']  = $registro->titulo ?? $registro->nome_lead ?? '';
        $vars['{probabilidade}'] = $registro->probabilidade_sucesso ?? '';
        $vars['{etapa}']         = $registro->etapa_funil ?? '';

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
