<?php

namespace App\Core;

use App\Models\Integracao;
use App\Services\CryptoService;
use App\Services\MailService;

/**
 * Envio de e-mails do sistema (ex.: redefinição de senha).
 * Nunca registrar tokens ou links completos em logs.
 */
class Mail
{
    /**
     * Envia e-mail com link de redefinição de senha.
     *
     * Estratégia de resolução da integração SMTP (em ordem de prioridade):
     *  1. Integração de e-mail cadastrada para o $usuarioId informado
     *  2. Integração de e-mail cadastrada para qualquer usuário ativo (fallback SaaS)
     *  3. Variáveis de ambiente MAIL_* (.env)
     *  4. Função mail() nativa do PHP (último recurso)
     *
     * O link contém o token; nunca logar o token ou o link completo.
     */
    public static function sendPasswordResetLink(string $toEmail, string $resetUrl, int $usuarioId = 0): bool
    {
        $subject = 'Redefinição de senha — ERP InLaudo';

        $bodyHtml = MailService::buildEmailHtml(
            'Redefinição de Senha',
            "<p style='margin:0 0 12px;'>Você solicitou a redefinição de senha no <strong>ERP InLaudo</strong>.</p>"
            . "<p style='margin:0 0 24px;'>Clique no botão abaixo para definir uma nova senha. O link é válido por <strong>60 minutos</strong>.</p>"
            . "<p style='text-align:center;margin:24px 0;'>"
            . "<a href='{$resetUrl}' style='background:#1a56db;color:#ffffff;padding:12px 28px;border-radius:6px;text-decoration:none;font-weight:700;font-size:15px;display:inline-block;'>Redefinir minha senha</a>"
            . "</p>"
            . "<p style='color:#9ca3af;font-size:13px;margin:0;'>Se você não solicitou isso, ignore este e-mail. Sua senha permanece a mesma.</p>"
        );

        $bodyText = "Você solicitou a redefinição de senha.\n\n"
            . "Acesse o link abaixo para definir uma nova senha (válido por 60 minutos):\n\n"
            . $resetUrl . "\n\n"
            . "Se você não solicitou isso, ignore este e-mail.\n\n"
            . "© " . date('Y') . " ERP InLaudo";

        // ─── Tenta resolver a integração SMTP ────────────────────────────────
        $service = null;

        try {
            $integracao = new Integracao();

            // 1. Integração do próprio usuário
            if ($usuarioId <= 0) {
                $usuarioId = (int)($_SESSION['user_id'] ?? 0);
            }

            $row = null;
            if ($usuarioId > 0) {
                $row = $integracao->findByNomeAndUsuarioId('email', $usuarioId);
                if ($row && ($row->status ?? 'ativo') !== 'ativo') {
                    $row = null; // integração inativa — ignora
                }
            }

            // 2. Fallback: qualquer integração de e-mail ativa no sistema
            //    (cobre o caso de sub-usuários que não têm integração própria)
            if (!$row) {
                $row = $integracao->findByProviderAtivo('email');
            }

            if ($row) {
                $cfg      = $integracao->getDecodedConfig($row);
                $password = '';

                if (!empty($cfg['password_enc'])) {
                    $crypto   = new CryptoService();
                    $password = $crypto->decryptString((string) $cfg['password_enc']);
                } elseif (!empty($cfg['password'])) {
                    $password = (string) $cfg['password'];
                }

                $service = new MailService([
                    'host'       => $cfg['host']       ?? '',
                    'port'       => (int)($cfg['port'] ?? 587),
                    'username'   => $cfg['username']   ?? '',
                    'password'   => $password,
                    'protocol'   => $cfg['protocol']   ?? 'tls',
                    'from_email' => $cfg['from_email'] ?? ($cfg['username'] ?? ''),
                    'from_name'  => $cfg['from_name']  ?? 'ERP InLaudo',
                ]);
            }

            // 3. Fallback: variáveis de ambiente (.env)
            if (!$service && MailService::isConfigured()) {
                $service = new MailService(); // carrega do .env
            }

        } catch (\Throwable $e) {
            error_log('[Mail::sendPasswordResetLink] Erro ao resolver integração SMTP: ' . $e->getMessage());
        }

        // ─── Envia via SMTP (se resolvido) ───────────────────────────────────
        if ($service) {
            try {
                return $service->send($toEmail, $subject, $bodyHtml, true);
            } catch (\Throwable $eHtml) {
                error_log('[Mail::sendPasswordResetLink] Falha HTML, tentando texto puro: ' . $eHtml->getMessage());
                try {
                    return $service->send($toEmail, $subject, $bodyText, false);
                } catch (\Throwable $eText) {
                    error_log('[Mail::sendPasswordResetLink] Falha texto puro: ' . $eText->getMessage());
                }
            }
        }

        // ─── 4. Último recurso: mail() nativa do PHP ─────────────────────────
        error_log('[Mail::sendPasswordResetLink] Nenhuma integração SMTP disponível. Usando mail() nativa para: ' . $toEmail);

        $headers = implode("\r\n", [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: noreply@inlaudo.com.br',
        ]);

        $result = @mail($toEmail, $subject, $bodyHtml, $headers);

        if (!$result) {
            error_log('[Mail::sendPasswordResetLink] mail() nativa falhou para: ' . $toEmail);
        }

        return $result;
    }

    /**
     * Constrói um MailService a partir da integração de e-mail de um usuário,
     * com fallback para qualquer integração ativa no sistema.
     * Útil para outros métodos de envio que precisam do mesmo padrão de resolução.
     */
    public static function buildService(int $usuarioId = 0): ?MailService
    {
        try {
            $integracao = new Integracao();

            if ($usuarioId <= 0) {
                $usuarioId = (int)($_SESSION['user_id'] ?? 0);
            }

            $row = null;
            if ($usuarioId > 0) {
                $row = $integracao->findByNomeAndUsuarioId('email', $usuarioId);
                if ($row && ($row->status ?? 'ativo') !== 'ativo') {
                    $row = null;
                }
            }

            if (!$row) {
                $row = $integracao->findByProviderAtivo('email');
            }

            if (!$row) {
                return MailService::isConfigured() ? new MailService() : null;
            }

            $cfg      = $integracao->getDecodedConfig($row);
            $password = '';

            if (!empty($cfg['password_enc'])) {
                $crypto   = new CryptoService();
                $password = $crypto->decryptString((string) $cfg['password_enc']);
            } elseif (!empty($cfg['password'])) {
                $password = (string) $cfg['password'];
            }

            return new MailService([
                'host'       => $cfg['host']       ?? '',
                'port'       => (int)($cfg['port'] ?? 587),
                'username'   => $cfg['username']   ?? '',
                'password'   => $password,
                'protocol'   => $cfg['protocol']   ?? 'tls',
                'from_email' => $cfg['from_email'] ?? ($cfg['username'] ?? ''),
                'from_name'  => $cfg['from_name']  ?? 'ERP InLaudo',
            ]);

        } catch (\Throwable $e) {
            error_log('[Mail::buildService] ' . $e->getMessage());
            return null;
        }
    }
}
