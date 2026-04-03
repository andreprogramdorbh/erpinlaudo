<?php

namespace App\Services;

/**
 * MailService — Serviço de envio de e-mail via SMTP
 *
 * Suporta PHPMailer (quando disponível via composer) ou implementação
 * nativa com stream_socket_client como fallback.
 *
 * Suporta conteúdo HTML e texto puro, STARTTLS (porta 587) e SSL (porta 465).
 */
class MailService
{
    private array $config;

    public function __construct(array $config = [])
    {
        if (empty($config)) {
            $this->config = $this->loadFromEnvironment();
        } else {
            $this->config = $config;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Configuração
    // ─────────────────────────────────────────────────────────────────────────

    private function loadFromEnvironment(): array
    {
        return [
            'host'       => getenv('MAIL_HOST')       ?: ($_ENV['MAIL_HOST']       ?? 'smtp.gmail.com'),
            'port'       => (int)(getenv('MAIL_PORT') ?: ($_ENV['MAIL_PORT']       ?? 587)),
            'username'   => getenv('MAIL_USERNAME')   ?: ($_ENV['MAIL_USERNAME']   ?? ''),
            'password'   => getenv('MAIL_PASSWORD')   ?: ($_ENV['MAIL_PASSWORD']   ?? ''),
            'protocol'   => getenv('MAIL_ENCRYPTION') ?: ($_ENV['MAIL_ENCRYPTION'] ?? 'tls'),
            'from_email' => getenv('MAIL_FROM_EMAIL') ?: ($_ENV['MAIL_FROM_EMAIL'] ?? ''),
            'from_name'  => getenv('MAIL_FROM_NAME')  ?: ($_ENV['MAIL_FROM_NAME']  ?? 'ERP InLaudo'),
        ];
    }

    public static function isConfigured(): bool
    {
        $host     = getenv('MAIL_HOST')     ?: ($_ENV['MAIL_HOST']     ?? '');
        $username = getenv('MAIL_USERNAME') ?: ($_ENV['MAIL_USERNAME'] ?? '');
        $password = getenv('MAIL_PASSWORD') ?: ($_ENV['MAIL_PASSWORD'] ?? '');
        return !empty($host) && !empty($username) && !empty($password);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // API pública
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Envia e-mail (HTML ou texto puro).
     *
     * @param string      $toEmail  Destinatário
     * @param string      $subject  Assunto
     * @param string      $body     Corpo (HTML ou texto)
     * @param bool        $isHtml   true = HTML, false = texto puro
     * @param string|null $toName   Nome do destinatário (opcional)
     * @throws \RuntimeException em caso de falha
     */
    public function send(
        string $toEmail,
        string $subject,
        string $body,
        bool $isHtml = false,
        ?string $toName = null
    ): bool {
        // Tenta PHPMailer primeiro (se disponível)
        if (class_exists(\PHPMailer\PHPMailer\PHPMailer::class)) {
            return $this->sendWithPHPMailer($toEmail, $subject, $body, $isHtml, $toName);
        }

        // Fallback: implementação nativa via socket
        return $this->sendWithSocket($toEmail, $subject, $body, $isHtml, $toName);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Templates de e-mail pré-definidos
    // ─────────────────────────────────────────────────────────────────────────

    public function sendWelcomeEmail(
        string $toEmail,
        string $toName,
        string $resetLink,
        string $tempPassword
    ): bool {
        $subject = 'Bem-vindo ao ERP InLaudo — Acesso ao Sistema';

        $bodyHtml = $this->wrapHtmlTemplate(
            'Bem-vindo ao ERP InLaudo',
            "
            <p>Olá, <strong>{$toName}</strong>!</p>
            <p>Sua conta foi criada no <strong>ERP InLaudo</strong>.</p>
            <table style='border-collapse:collapse;margin:16px 0;'>
                <tr><td style='padding:4px 12px 4px 0;color:#555;'>E-mail:</td><td><strong>{$toEmail}</strong></td></tr>
                <tr><td style='padding:4px 12px 4px 0;color:#555;'>Senha temporária:</td><td><strong>{$tempPassword}</strong></td></tr>
            </table>
            <p style='color:#c0392b;'><strong>IMPORTANTE:</strong> Por segurança, defina sua própria senha no primeiro acesso.</p>
            <p style='text-align:center;margin:24px 0;'>
                <a href='{$resetLink}' style='background:#1a56db;color:#fff;padding:12px 28px;border-radius:6px;text-decoration:none;font-weight:600;'>
                    Criar minha senha
                </a>
            </p>
            <p style='color:#888;font-size:13px;'>Este link expira em 60 minutos.</p>
            "
        );

        $bodyText = "Olá, {$toName}!\n\nSua conta foi criada no ERP InLaudo.\n\n"
            . "E-mail: {$toEmail}\nSenha temporária: {$tempPassword}\n\n"
            . "Crie sua senha definitiva em: {$resetLink}\n\n"
            . "Este link expira em 60 minutos.\n\nEquipe ERP InLaudo";

        try {
            return $this->send($toEmail, $subject, $bodyHtml, true, $toName);
        } catch (\Throwable $e) {
            // Fallback para texto puro
            return $this->send($toEmail, $subject, $bodyText, false, $toName);
        }
    }

    public function sendPasswordResetEmail(
        string $toEmail,
        string $toName,
        string $resetLink
    ): bool {
        $subject = 'Redefinição de Senha — ERP InLaudo';

        $bodyHtml = $this->wrapHtmlTemplate(
            'Redefinição de Senha',
            "
            <p>Olá, <strong>{$toName}</strong>!</p>
            <p>Recebemos uma solicitação para redefinir sua senha no <strong>ERP InLaudo</strong>.</p>
            <p style='text-align:center;margin:24px 0;'>
                <a href='{$resetLink}' style='background:#1a56db;color:#fff;padding:12px 28px;border-radius:6px;text-decoration:none;font-weight:600;'>
                    Redefinir minha senha
                </a>
            </p>
            <p style='color:#888;font-size:13px;'>Se você não solicitou esta redefinição, ignore este e-mail. O link expira em 60 minutos.</p>
            "
        );

        $bodyText = "Olá, {$toName}!\n\nClique no link para redefinir sua senha:\n{$resetLink}\n\n"
            . "Se você não solicitou esta redefinição, ignore este e-mail.\n"
            . "Este link expira em 60 minutos.\n\nEquipe ERP InLaudo";

        try {
            return $this->send($toEmail, $subject, $bodyHtml, true, $toName);
        } catch (\Throwable $e) {
            return $this->send($toEmail, $subject, $bodyText, false, $toName);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Implementação com PHPMailer
    // ─────────────────────────────────────────────────────────────────────────

    private function sendWithPHPMailer(
        string $toEmail,
        string $subject,
        string $body,
        bool $isHtml,
        ?string $toName
    ): bool {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

        $host     = (string)($this->config['host']       ?? '');
        $port     = (int)($this->config['port']          ?? 587);
        $username = (string)($this->config['username']   ?? '');
        $password = (string)($this->config['password']   ?? '');
        $protocol = strtolower((string)($this->config['protocol'] ?? 'tls'));
        $fromEmail = (string)($this->config['from_email'] ?? $username);
        $fromName  = (string)($this->config['from_name']  ?? 'ERP InLaudo');

        $mail->isSMTP();
        $mail->Host       = $host;
        $mail->Port       = $port;
        $mail->SMTPAuth   = ($username !== '' && $password !== '');
        $mail->Username   = $username;
        $mail->Password   = $password;
        $mail->SMTPSecure = ($protocol === 'ssl')
            ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
            : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;

        $mail->CharSet  = 'UTF-8';
        $mail->Encoding = 'base64';

        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($toEmail, $toName ?? '');

        $mail->Subject = $subject;
        $mail->isHTML($isHtml);
        $mail->Body    = $body;
        if ($isHtml) {
            $mail->AltBody = strip_tags($body);
        }

        $mail->send();
        return true;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Implementação nativa via stream_socket_client
    // ─────────────────────────────────────────────────────────────────────────

    private function sendWithSocket(
        string $toEmail,
        string $subject,
        string $body,
        bool $isHtml,
        ?string $toName
    ): bool {
        $host      = (string)($this->config['host']       ?? '');
        $port      = (int)($this->config['port']          ?? 587);
        $username  = (string)($this->config['username']   ?? '');
        $password  = (string)($this->config['password']   ?? '');
        $protocol  = strtolower((string)($this->config['protocol'] ?? 'tls'));
        $fromEmail = (string)($this->config['from_email'] ?? $username);
        $fromName  = (string)($this->config['from_name']  ?? 'ERP InLaudo');

        if ($host === '' || $port <= 0 || $fromEmail === '') {
            throw new \RuntimeException(
                'Configuração de e-mail incompleta. Verifique host, porta e from_email.'
            );
        }

        // Contexto SSL permissivo para ambientes compartilhados (Hostinger)
        $context = stream_context_create([
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true,
            ],
        ]);

        $remote = ($protocol === 'ssl')
            ? "ssl://{$host}:{$port}"
            : "tcp://{$host}:{$port}";

        $errno  = 0;
        $errstr = '';
        $fp = stream_socket_client($remote, $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $context);
        if (!$fp) {
            throw new \RuntimeException(
                "SMTP: falha ao conectar em {$remote} — {$errstr} (código {$errno})"
            );
        }

        stream_set_timeout($fp, 15);

        try {
            $greet = $this->readResponse($fp);
            $this->expectCode($greet, 220);

            $ehloResp = $this->sendCmd($fp, 'EHLO ' . gethostname(), 250);

            if ($protocol === 'tls') {
                if (stripos($ehloResp, 'STARTTLS') === false) {
                    throw new \RuntimeException(
                        'SMTP: servidor não suporta STARTTLS. Tente usar SSL na porta 465.'
                    );
                }
                $this->sendCmd($fp, 'STARTTLS', 220);
                $cryptoOk = stream_socket_enable_crypto(
                    $fp,
                    true,
                    STREAM_CRYPTO_METHOD_TLS_CLIENT
                );
                if ($cryptoOk !== true) {
                    throw new \RuntimeException(
                        'SMTP: falha ao negociar TLS. Verifique se a porta 587 está liberada no servidor.'
                    );
                }
                $this->sendCmd($fp, 'EHLO ' . gethostname(), 250);
            }

            if ($username !== '' && $password !== '') {
                $this->sendCmd($fp, 'AUTH LOGIN', 334);
                $this->sendCmd($fp, base64_encode($username), 334);
                $authResp = $this->sendCmd($fp, base64_encode($password));
                $authCode = (int)substr(trim($authResp), 0, 3);
                if ($authCode !== 235) {
                    throw new \RuntimeException(
                        'SMTP: autenticação falhou (código ' . $authCode . '). '
                        . 'Verifique usuário/senha. Para Gmail use App Password (16 caracteres sem espaços).'
                    );
                }
            }

            $fromEmailSafe = $this->headerSafe($fromEmail);
            $toEmailSafe   = $this->headerSafe($toEmail);
            $subjectSafe   = $this->headerSafe($subject);
            $fromNameSafe  = $this->headerSafe($fromName);
            $toNameSafe    = $this->headerSafe($toName ?? '');

            $this->sendCmd($fp, 'MAIL FROM:<' . $fromEmailSafe . '>', 250);
            $this->sendCmd($fp, 'RCPT TO:<' . $toEmailSafe . '>', 250);
            $this->sendCmd($fp, 'DATA', 354);

            // Monta cabeçalhos MIME
            $boundary = '----=_Part_' . md5(uniqid('', true));
            $msgId    = '<' . uniqid('erpinlaudo', true) . '@' . gethostname() . '>';

            $headers   = [];
            $headers[] = 'From: ' . ($fromNameSafe !== '' ? '"' . addslashes($fromNameSafe) . '" ' : '') . '<' . $fromEmailSafe . '>';
            $headers[] = 'To: ' . ($toNameSafe !== '' ? '"' . addslashes($toNameSafe) . '" ' : '') . '<' . $toEmailSafe . '>';
            $headers[] = 'Subject: =?UTF-8?B?' . base64_encode($subjectSafe) . '?=';
            $headers[] = 'Date: ' . date('r');
            $headers[] = 'Message-ID: ' . $msgId;
            $headers[] = 'MIME-Version: 1.0';
            $headers[] = 'X-Mailer: ERP-InLaudo/1.0';

            if ($isHtml) {
                $headers[] = 'Content-Type: multipart/alternative; boundary="' . $boundary . '"';
                $textPart  = strip_tags($body);
                $dataParts = implode("\r\n", $headers) . "\r\n\r\n"
                    . '--' . $boundary . "\r\n"
                    . "Content-Type: text/plain; charset=UTF-8\r\n"
                    . "Content-Transfer-Encoding: base64\r\n\r\n"
                    . chunk_split(base64_encode($textPart)) . "\r\n"
                    . '--' . $boundary . "\r\n"
                    . "Content-Type: text/html; charset=UTF-8\r\n"
                    . "Content-Transfer-Encoding: base64\r\n\r\n"
                    . chunk_split(base64_encode($body)) . "\r\n"
                    . '--' . $boundary . "--\r\n";
                $data = $dataParts;
            } else {
                $headers[] = 'Content-Type: text/plain; charset=UTF-8';
                $headers[] = 'Content-Transfer-Encoding: base64';
                $data = implode("\r\n", $headers) . "\r\n\r\n"
                    . chunk_split(base64_encode($body)) . "\r\n";
            }

            // Escapa linhas que começam com ponto (RFC 5321)
            $data = preg_replace('/^\.$/m', '..', $data);

            fwrite($fp, $data . "\r\n.\r\n");
            $resp = $this->readResponse($fp);
            $this->expectCode($resp, 250);

            $this->sendCmd($fp, 'QUIT', 221);
            fclose($fp);

            return true;
        } catch (\Throwable $e) {
            try {
                fwrite($fp, "QUIT\r\n");
            } catch (\Throwable $ignored) {
            }
            fclose($fp);
            throw $e;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers SMTP
    // ─────────────────────────────────────────────────────────────────────────

    private function headerSafe(string $value): string
    {
        return trim(str_replace(["\r", "\n"], '', $value));
    }

    /** @param resource $fp */
    private function readLine($fp): string
    {
        $line = fgets($fp, 515);
        return $line === false ? '' : $line;
    }

    /** @param resource $fp */
    private function readResponse($fp): string
    {
        $data = '';
        $maxLines = 50;
        while ($maxLines-- > 0) {
            $line = $this->readLine($fp);
            if ($line === '') {
                break;
            }
            $data .= $line;
            // Linha final de resposta multi-linha: "NNN texto" (sem hífen após código)
            if (preg_match('/^\d{3} /', $line)) {
                break;
            }
        }
        return $data;
    }

    private function expectCode(string $response, int $code): void
    {
        $response = trim($response);
        if ($response === '' || (int)substr($response, 0, 3) !== $code) {
            throw new \RuntimeException(
                'SMTP: resposta inesperada. Esperado ' . $code . ', obtido: ' . $response
            );
        }
    }

    /** @param resource $fp */
    private function sendCmd($fp, string $cmd, ?int $expect = null): string
    {
        fwrite($fp, $cmd . "\r\n");
        $resp = $this->readResponse($fp);
        if ($expect !== null) {
            $this->expectCode($resp, $expect);
        }
        return $resp;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Template HTML padrão
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Gera um e-mail HTML completo com o layout padrão do ERP InLaudo.
     * Método estático para uso direto nos controllers sem instanciar o serviço.
     *
     * @param string $subtitle  Subtítulo exibido no cabeçalho (ex: 'Confirmação de Pagamento')
     * @param string $content   Conteúdo HTML interno (p, table, etc.)
     * @param string $accentColor Cor do cabeçalho em hex (padrão azul ERP)
     */
    public static function buildEmailHtml(
        string $subtitle,
        string $content,
        string $accentColor = '#1a56db'
    ): string {
        $year = date('Y');
        return <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
</head>
<body style="margin:0;padding:0;background:#f4f6f9;font-family:Arial,Helvetica,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f4f6f9;padding:32px 0;">
  <tr>
    <td align="center">
      <table width="600" cellpadding="0" cellspacing="0" border="0" style="background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.08);max-width:600px;">
        <tr>
          <td style="background:{$accentColor};padding:24px 32px;">
            <h1 style="margin:0;color:#ffffff;font-size:22px;font-weight:700;letter-spacing:-0.5px;">ERP InLaudo</h1>
            <p style="margin:4px 0 0;color:rgba(255,255,255,0.8);font-size:13px;">{$subtitle}</p>
          </td>
        </tr>
        <tr>
          <td style="padding:28px 32px;color:#374151;font-size:15px;line-height:1.7;">
            {$content}
          </td>
        </tr>
        <tr>
          <td style="background:#f9fafb;padding:16px 32px;border-top:1px solid #e5e7eb;text-align:center;">
            <p style="margin:0;color:#9ca3af;font-size:12px;">
              &copy; {$year} ERP InLaudo &mdash; E-mail automático, não responda.
            </p>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
</body>
</html>
HTML;
    }

    private function wrapHtmlTemplate(string $title, string $content): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{$title}</title>
</head>
<body style="margin:0;padding:0;background:#f4f6f9;font-family:Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f9;padding:32px 0;">
  <tr>
    <td align="center">
      <table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.08);">
        <!-- Cabeçalho -->
        <tr>
          <td style="background:#1a56db;padding:24px 32px;">
            <h1 style="margin:0;color:#fff;font-size:20px;font-weight:700;">ERP InLaudo</h1>
          </td>
        </tr>
        <!-- Conteúdo -->
        <tr>
          <td style="padding:32px;color:#333;font-size:15px;line-height:1.6;">
            {$content}
          </td>
        </tr>
        <!-- Rodapé -->
        <tr>
          <td style="background:#f8f9fa;padding:16px 32px;border-top:1px solid #e9ecef;">
            <p style="margin:0;color:#888;font-size:12px;text-align:center;">
              Este e-mail foi enviado automaticamente pelo ERP InLaudo. Não responda a este e-mail.
            </p>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
</body>
</html>
HTML;
    }
}
