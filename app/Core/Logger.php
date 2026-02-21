<?php

namespace App\Core;

class Logger
{
    private string $logDir = __DIR__ . '/../../storage/logs';

    public function __construct()
    {
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0755, true);
        }
    }

    public function error(string $message, array $context = []): void
    {
        $this->log("error", $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->log("info", $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log("warning", $message, $context);
    }

    public function auth(string $message, array $context = []): void
    {
        $this->log("info", $message, $context);
    }

    /**
     * Log detalhado para debug de erros críticos
     * Inclui informações técnicas completas para diagnóstico
     */
    public function debug(string $message, array $context = []): void
    {
        $this->log("debug", $message, $context);
    }

    private function log(string $type, string $message, array $context = []): void
    {
        $timestamp = date("Y-m-d H:i:s");
        $logFile = $this->logDir . "/" . $type . ".log";

        $userId = $_SESSION["user_id"] ?? "-";
        $ipAddress = $_SERVER["REMOTE_ADDR"] ?? "-";
        $userAgent = $_SERVER["HTTP_USER_AGENT"] ?? "-";
        $requestUri = $_SERVER["REQUEST_URI"] ?? "-";
        $requestMethod = $_SERVER["REQUEST_METHOD"] ?? "-";

        $logMessage = "[{$timestamp}] [IP: {$ipAddress}] [User: {$userId}] [Method: {$requestMethod}] [URI: {$requestUri}] - {$message}";

        if (!empty($context)) {
            $logMessage .= " | Context: " . json_encode($context);
        }

        $logMessage .= " | User-Agent: {$userAgent}";
        $logMessage .= "\n";

        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }
}
