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
        $subject = 'Redefinição de senha - ERP InLaudo';
        $body = "Você solicitou a redefinição de senha.\n\n"
            . "Acesse o link abaixo para definir uma nova senha (válido por 60 minutos):\n\n"
            . $resetUrl . "\n\n"
            . "Se você não solicitou isso, ignore este e-mail.\n\n"
            . " 2026 InLaudo.";

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
                        'host' => $cfg['host'] ?? '',
                        'port' => $cfg['port'] ?? 587,
                        'username' => $cfg['username'] ?? '',
                        'password' => $password,
                        'protocol' => $cfg['protocol'] ?? 'tls',
                        'from_email' => $cfg['from_email'] ?? ($cfg['username'] ?? ''),
                        'from_name' => $cfg['from_name'] ?? 'ERP InLaudo',
                    ]);

                    return $service->sendText($toEmail, $subject, $body);
                }
            }
        } catch (\Throwable $e) {
        }

        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/plain; charset=UTF-8',
            'From: noreply@inlaudo.com.br',
        ];

        return @mail($toEmail, $subject, $body, implode("\r\n", $headers));
    }
}
