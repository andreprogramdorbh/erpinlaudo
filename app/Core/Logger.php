<?php

namespace App\Core;

class Logger
{
    private string $logDir = __DIR__ . '/../../storage/logs';
    private array $fileMap = [
        'auth' => 'auth.txt',
    ];

    public function __construct()
    {
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0755, true);
        }
        foreach ($this->fileMap as $fileName) {
            $path = $this->logDir . "/" . $fileName;
            if (!file_exists($path)) {
                file_put_contents($path, "");
            }
            @chmod($path, 0664);
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
        $this->log("auth", $message, $context);
    }

    /**
     * Log de erros originados no frontend (JavaScript)
     */
    public function clientError(string $message, array $context = []): void
    {
        $this->log("clienterror", $message, $context);
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
        $fileName = $this->fileMap[$type] ?? ($type . ".log");
        $logFile = $this->logDir . "/" . $fileName;

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

        $written = @file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
        if ($written === false && $type !== 'error') {
            $fallback = $this->logDir . "/error.log";
            @file_put_contents($fallback, $logMessage, FILE_APPEND | LOCK_EX);
        }
        if ($written === false && $type === 'error') {
            error_log($logMessage);
        }
    }
}
