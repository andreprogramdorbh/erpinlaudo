<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Logger;
use App\Core\Router;
use Dotenv\Dotenv;

/*
|--------------------------------------------------------------------------
| Carregar variáveis de ambiente (.env)
|--------------------------------------------------------------------------
| Usando createImmutable:
| - Variáveis disponíveis em $_ENV e $_SERVER
| - NÃO usar getenv() para validação
*/
$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

/*
|--------------------------------------------------------------------------
| Validar variáveis de ambiente críticas do banco
|--------------------------------------------------------------------------
*/
$requiredEnvVars = ['DB_HOST', 'DB_DATABASE', 'DB_USERNAME'];

foreach ($requiredEnvVars as $var) {
    if (empty($_ENV[$var] ?? null)) {
        $uri = (string)($_SERVER['REQUEST_URI'] ?? '');
        if ($uri !== '' && (strpos($uri, '/login') === 0 || strpos($uri, '/dashboard') === 0 || strpos($uri, '/logout') === 0 || strpos($uri, '/reset-password') === 0 || strpos($uri, '/forgot-password') === 0)) {
            $logger->auth('Exception em rota de autenticação', [
                'uri'   => $uri,
                'error' => $exception->getMessage(),
                'file'  => $exception->getFile(),
                'line'  => $exception->getLine(),
            ]);
        }
        http_response_code(500);
        echo "❌ Erro de Configuração: Variável de ambiente '{$var}' não está configurada no arquivo .env\n";
        echo "Por favor, configure o arquivo .env com as credenciais corretas do banco de dados.\n";
        exit();
    }
}

/*
|--------------------------------------------------------------------------
| Configuração de ambiente (DEV / PROD)
|--------------------------------------------------------------------------
*/
$appEnv = $_ENV['APP_ENV'] ?? 'dev';
$isProduction = ($appEnv === 'prod');

if ($isProduction) {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);

    set_error_handler(function ($severity, $message, $file, $line) {
        $logger = new Logger();
        $logger->error($message, [
            'file' => $file,
            'line' => $line,
            'severity' => $severity,
            'severity_name' => getSeverityName($severity)
        ]);
        return true;
    });

    set_exception_handler(function ($exception) {
        $logger = new Logger();
        $logger->error("Exceção não capturada: " . $exception->getMessage(), [
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ]);
        http_response_code(500);
        echo "Ocorreu um erro inesperado. Por favor, tente novamente mais tarde.";
    });
} else {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
    
    set_error_handler(function ($severity, $message, $file, $line) {
        $logger = new Logger();
        $logger->debug("Erro detectado", [
            'message' => $message,
            'file' => $file,
            'line' => $line,
            'severity' => $severity
        ]);
        
        // Em desenvolvimento, exibir o erro
        echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd; margin: 10px; border-radius: 4px;'>";
        echo "❌ ERRO [{$severity}]: {$message}\n";
        echo "📁 Arquivo: {$file}:{$line}\n";
        echo "</pre>";
    });

    set_exception_handler(function ($exception) {
        $logger = new Logger();
        $logger->debug("Exceção não capturada", [
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ]);
        
        // Em desenvolvimento, exibir a exceção
        echo "<pre style='background: #ffe0e0; padding: 10px; border: 1px solid #cc0000; margin: 10px; border-radius: 4px;'>";
        echo "❌ EXCEÇÃO: " . $exception->getMessage() . "\n";
        echo "📁 Arquivo: " . $exception->getFile() . ":" . $exception->getLine() . "\n";
        echo "📋 Trace:\n" . $exception->getTraceAsString();
        echo "</pre>";
    });
}

/*
|--------------------------------------------------------------------------
| Timezone
|--------------------------------------------------------------------------
*/
date_default_timezone_set('America/Sao_Paulo');

/*
|--------------------------------------------------------------------------
| Sessão segura
|--------------------------------------------------------------------------
*/
ini_set('session.use_only_cookies', 1);
ini_set('session.use_strict_mode', 1);

session_set_cookie_params([
    'lifetime' => 3600,
    'path' => '/',
    'secure' => $isProduction,
    'httponly' => true,
    'samesite' => 'Lax'
]);

session_start();

/*
|--------------------------------------------------------------------------
| Middleware global (timeout de sessão)
|--------------------------------------------------------------------------
*/
(new \App\Middlewares\SessionTimeoutMiddleware())->handle();

/*
|--------------------------------------------------------------------------
| CSRF Token
|--------------------------------------------------------------------------
*/
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/*
|--------------------------------------------------------------------------
| Rotas
|--------------------------------------------------------------------------
*/
require_once dirname(__DIR__) . '/routes/web.php';
require_once dirname(__DIR__) . '/routes/api.php';

/*
|--------------------------------------------------------------------------
| Dispatcher
|--------------------------------------------------------------------------
*/
Router::dispatch();

/**
 * Função auxiliar para obter o nome da severidade do erro
 */
function getSeverityName($severity) {
    $severityMap = [
        E_ERROR => 'E_ERROR',
        E_WARNING => 'E_WARNING',
        E_PARSE => 'E_PARSE',
        E_NOTICE => 'E_NOTICE',
        E_CORE_ERROR => 'E_CORE_ERROR',
        E_CORE_WARNING => 'E_CORE_WARNING',
        E_COMPILE_ERROR => 'E_COMPILE_ERROR',
        E_COMPILE_WARNING => 'E_COMPILE_WARNING',
        E_USER_ERROR => 'E_USER_ERROR',
        E_USER_WARNING => 'E_USER_WARNING',
        E_USER_NOTICE => 'E_USER_NOTICE',
        E_STRICT => 'E_STRICT',
        E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
        E_DEPRECATED => 'E_DEPRECATED',
        E_USER_DEPRECATED => 'E_USER_DEPRECATED',
    ];
    
    return $severityMap[$severity] ?? 'UNKNOWN';
}
