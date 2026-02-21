<?php

namespace App\Services;

class MailService
{
    private array $config;

    public function __construct(array $config = [])
    {
        // Se não passar config, usa variáveis de ambiente
        if (empty($config)) {
            $this->config = $this->loadFromEnvironment();
        } else {
            $this->config = $config;
        }
    }

    /**
     * Carrega configurações de e-mail das variáveis de ambiente
     */
    private function loadFromEnvironment(): array
    {
        return [
            'host' => getenv('MAIL_HOST') ?: $_ENV['MAIL_HOST'] ?? 'smtp.gmail.com',
            'port' => (int)(getenv('MAIL_PORT') ?: $_ENV['MAIL_PORT'] ?? 587),
            'username' => getenv('MAIL_USERNAME') ?: $_ENV['MAIL_USERNAME'] ?? '',
            'password' => getenv('MAIL_PASSWORD') ?: $_ENV['MAIL_PASSWORD'] ?? '',
            'protocol' => getenv('MAIL_ENCRYPTION') ?: $_ENV['MAIL_ENCRYPTION'] ?? 'tls',
            'from_email' => getenv('MAIL_FROM_EMAIL') ?: $_ENV['MAIL_FROM_EMAIL'] ?? '',
            'from_name' => getenv('MAIL_FROM_NAME') ?: $_ENV['MAIL_FROM_NAME'] ?? 'ERP InLaudo'
        ];
    }

    /**
     * Verifica se o e-mail está configurado
     */
    public static function isConfigured(): bool
    {
        $host = getenv('MAIL_HOST') ?: $_ENV['MAIL_HOST'] ?? '';
        $username = getenv('MAIL_USERNAME') ?: $_ENV['MAIL_USERNAME'] ?? '';
        $password = getenv('MAIL_PASSWORD') ?: $_ENV['MAIL_PASSWORD'] ?? '';
        
        return !empty($host) && !empty($username) && !empty($password);
    }

    private function headerSafe(string $value): string
    {
        return trim(str_replace(["\r", "\n"], '', $value));
    }

    private function readLine($fp): string
    {
        $line = fgets($fp, 515);
        return $line === false ? '' : $line;
    }

    private function readResponse($fp): string
    {
        $data = '';
        while (true) {
            $line = $this->readLine($fp);
            if ($line === '') {
                break;
            }
            $data .= $line;
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
            throw new \RuntimeException('SMTP: resposta inesperada. Esperado ' . $code . ', obtido: ' . $response);
        }
    }

    private function sendCmd($fp, string $cmd, ?int $expect = null): string
    {
        fwrite($fp, $cmd . "\r\n");
        $resp = $this->readResponse($fp);
        if ($expect !== null) {
            $this->expectCode($resp, $expect);
        }
        return $resp;
    }

    public function send(string $toEmail, string $subject, string $body): bool
    {
        $host = (string)($this->config['host'] ?? '');
        $port = (int)($this->config['port'] ?? 587);
        $username = (string)($this->config['username'] ?? '');
        $password = (string)($this->config['password'] ?? '');
        $protocol = strtolower((string)($this->config['protocol'] ?? 'tls'));

        $fromEmail = (string)($this->config['from_email'] ?? $username);
        $fromName = (string)($this->config['from_name'] ?? 'ERP InLaudo');

        if ($host === '' || $port <= 0 || $fromEmail === '') {
            throw new \RuntimeException('Configuração de e-mail incompleta. Verifique as variáveis MAIL_* no .env');
        }

        $remote = ($protocol === 'ssl')
            ? "ssl://{$host}:{$port}"
            : "tcp://{$host}:{$port}";

        $errno = 0;
        $errstr = '';
        $fp = stream_socket_client($remote, $errno, $errstr, 10, STREAM_CLIENT_CONNECT);
        if (!$fp) {
            throw new \RuntimeException('SMTP: falha ao conectar: ' . $errstr);
        }

        stream_set_timeout($fp, 10);

        try {
            $greet = $this->readResponse($fp);
            $this->expectCode($greet, 220);

            $this->sendCmd($fp, 'EHLO erp.inlaudo.local', 250);

            if ($protocol === 'tls') {
                $this->sendCmd($fp, 'STARTTLS', 220);
                $cryptoOk = stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                if ($cryptoOk !== true) {
                    throw new \RuntimeException('SMTP: falha ao iniciar TLS.');
                }
                $this->sendCmd($fp, 'EHLO erp.inlaudo.local', 250);
            }

            if ($username !== '' && $password !== '') {
                $this->sendCmd($fp, 'AUTH LOGIN', 334);
                $this->sendCmd($fp, base64_encode($username), 334);
                $this->sendCmd($fp, base64_encode($password), 235);
            }

            $fromEmailSafe = $this->headerSafe($fromEmail);
            $toEmailSafe = $this->headerSafe($toEmail);
            $subjectSafe = $this->headerSafe($subject);
            $fromNameSafe = $this->headerSafe($fromName);

            $this->sendCmd($fp, 'MAIL FROM:<' . $fromEmailSafe . '>', 250);
            $this->sendCmd($fp, 'RCPT TO:<' . $toEmailSafe . '>', 250);
            $this->sendCmd($fp, 'DATA', 354);

            $headers = [];
            $headers[] = 'From: ' . ($fromNameSafe !== '' ? '"' . addslashes($fromNameSafe) . '" ' : '') . '<' . $fromEmailSafe . '>';
            $headers[] = 'To: <' . $toEmailSafe . '>';
            $headers[] = 'Subject: ' . $subjectSafe;
            $headers[] = 'Date: ' . date('r');
            $headers[] = 'MIME-Version: 1.0';
            $headers[] = 'Content-Type: text/plain; charset=UTF-8';
            $headers[] = 'Content-Transfer-Encoding: 8bit';

            $data = implode("\r\n", $headers) . "\r\n\r\n" . $body;
            $data = str_replace("\n.", "\n..", $data);

            fwrite($fp, $data . "\r\n.\r\n");
            $resp = $this->readResponse($fp);
            $this->expectCode($resp, 250);

            $this->sendCmd($fp, 'QUIT', 221);
            fclose($fp);

            return true;
        } catch (\Throwable $e) {
            try {
                $this->sendCmd($fp, 'QUIT');
            } catch (\Throwable $ignored) {
            }
            fclose($fp);
            throw $e;
        }
    }

    /**
     * Envia e-mail de boas-vindas para novo usuário
     */
    public function sendWelcomeEmail(string $toEmail, string $toName, string $resetLink, string $tempPassword): bool
    {
        $subject = 'Bem-vindo ao ERP InLaudo - Acesso ao Sistema';
        $body = "Olá, {$toName}!\n\n";
        $body .= "Sua conta foi criada no ERP InLaudo.\n\n";
        $body .= "Seus dados de acesso:\n";
        $body .= "E-mail: {$toEmail}\n";
        $body .= "Senha temporária: {$tempPassword}\n\n";
        $body .= "IMPORTANTE: Por segurança, você deve definir sua própria senha no primeiro acesso.\n";
        $body .= "Clique no link abaixo para criar sua senha definitiva:\n";
        $body .= "{$resetLink}\n\n";
        $body .= "Este link expira em 60 minutos.\n\n";
        $body .= "Atenciosamente,\n";
        $body .= "Equipe ERP InLaudo";

        return $this->send($toEmail, $subject, $body);
    }

    /**
     * Envia e-mail de reset de senha
     */
    public function sendPasswordResetEmail(string $toEmail, string $toName, string $resetLink): bool
    {
        $subject = 'Redefinição de Senha - ERP InLaudo';
        $body = "Olá, {$toName}!\n\n";
        $body .= "Recebemos uma solicitação para redefinir sua senha no ERP InLaudo.\n\n";
        $body .= "Clique no link abaixo para criar uma nova senha:\n";
        $body .= "{$resetLink}\n\n";
        $body .= "Se você não solicitou esta redefinição, ignore este e-mail.\n";
        $body .= "Este link expira em 60 minutos.\n\n";
        $body .= "Atenciosamente,\n";
        $body .= "Equipe ERP InLaudo";

        return $this->send($toEmail, $subject, $body);
    }
}
