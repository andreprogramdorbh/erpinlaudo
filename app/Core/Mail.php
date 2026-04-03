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
     * O link contém o token; nunca logar o token ou o link.
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

        try {
            if ($usuarioId <= 0) {
                $usuarioId = (int)($_SESSION['user_id'] ?? 0);
            }
            if ($usuarioId > 0) {
                $integracao = new Integracao();
                $row = $integracao->findByNomeAndUsuarioId('email', $usuarioId);
                if ($row && ($row->status ?? 'ativo') === 'ativo') {
                    $cfg = $integracao->getDecodedConfig($row);

                    $password = '';
                    if (!empty($cfg['password_enc'])) {
                        $crypto = new CryptoService();
                        $password = $crypto->decryptString((string) $cfg['password_enc']);
                    }

                    $service = new MailService([
                        'host'       => $cfg['host']       ?? '',
                        'port'       => $cfg['port']       ?? 587,
                        'username'   => $cfg['username']   ?? '',
                        'password'   => $password,
                        'protocol'   => $cfg['protocol']   ?? 'tls',
                        'from_email' => $cfg['from_email'] ?? ($cfg['username'] ?? ''),
                        'from_name'  => $cfg['from_name']  ?? 'ERP InLaudo',
                    ]);

                    try {
                        return $service->send($toEmail, $subject, $bodyHtml, true);
                    } catch (\Throwable $eHtml) {
                        // Fallback para texto puro se HTML falhar
                        return $service->send($toEmail, $subject, $bodyText, false);
                    }
                }
            }
        } catch (\Throwable $e) {
            // Ignora e usa fallback nativo abaixo
        }

        // Fallback: função mail() nativa com HTML
        $headers = implode("\r\n", [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: noreply@inlaudo.com.br',
        ]);

        return @mail($toEmail, $subject, $bodyHtml, $headers);
    }
}
