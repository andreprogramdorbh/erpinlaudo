<?php

namespace App\Core;

use Exception;
use App\Middlewares\AuthMiddleware;

class Router
{
    protected static array $routes = [];
    protected static array $groupMiddleware = [];

    // Registro global de middlewares
    protected static array $middlewares = [
        'Auth'            => AuthMiddleware::class,
        'Permission'      => \App\Middlewares\PermissionMiddleware::class,
        'PortalCliente'   => \App\Middlewares\PortalClienteMiddleware::class,
        'WhatsappApiAuth' => \App\Middlewares\WhatsappApiAuthMiddleware::class,
    ];

    /* =========================
     * Registro de Rotas
     * ========================= */

    public static function get(string $uri, string $action): void
    {
        self::$routes['GET'][$uri] = [
            'action' => $action,
            'middleware' => self::$groupMiddleware
        ];
    }

    public static function post(string $uri, string $action): void
    {
        self::$routes['POST'][$uri] = [
            'action' => $action,
            'middleware' => self::$groupMiddleware
        ];
    }

    /* =========================
     * Agrupamento com Middleware
     * ========================= */

    public static function group(array $options, callable $callback): void
    {
        $previousMiddleware = self::$groupMiddleware;
        self::$groupMiddleware = array_merge($previousMiddleware, (array) ($options['middleware'] ?? []));
        $callback();
        self::$groupMiddleware = $previousMiddleware;
    }

    /* =========================
     * Dispatcher
     * ========================= */

    public static function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Normaliza barra final
        if ($uri !== '/' && str_ends_with($uri, '/')) {
            $uri = rtrim($uri, '/');
        }

        $route = self::$routes[$method][$uri] ?? null;
        $routeParams = [];

        if ($route === null && isset(self::$routes[$method])) {
            foreach (self::$routes[$method] as $routeUri => $routeConfig) {
                if (!str_contains($routeUri, '{')) {
                    continue;
                }

                if (preg_match_all('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', $routeUri, $paramMatches)) {
                    $paramNames = $paramMatches[1];

                    $pattern = '#^' . preg_quote($routeUri, '#') . '$#';
                    foreach ($paramNames as $paramName) {
                        $pattern = str_replace(preg_quote('{' . $paramName . '}', '#'), '([^/]+)', $pattern);
                    }

                    if (preg_match($pattern, $uri, $matches)) {
                        $route = $routeConfig;
                        foreach ($paramNames as $idx => $paramName) {
                            $routeParams[$paramName] = $matches[$idx + 1] ?? null;
                        }
                        break;
                    }
                }
            }
        }

        if ($route === null) {
            http_response_code(404);
            echo "404 - Página não encontrada";
            return;
        }

        // Executa middlewares
        foreach ($route['middleware'] as $middleware) {
            self::runMiddleware($middleware);
        }

        // Executa Controller@method
        [$controller, $action] = explode('@', $route['action']);

        // Suporte a rotas dinâmicas como /clientes/edit/:id
        // (Simulando uma detecção simples de parâmetro se necessário no futuro)

        $controllerClass = "App\\Controllers\\{$controller}";

        if (!class_exists($controllerClass)) {
            throw new Exception("Controller {$controllerClass} não encontrado");
        }

        $instance = new $controllerClass();

        if (!method_exists($instance, $action)) {
            throw new Exception("Método {$action} não encontrado em {$controllerClass}");
        }

        if (!empty($routeParams)) {
            call_user_func_array([$instance, $action], array_values($routeParams));
        } else {
            $parts = explode('/', $uri);
            $id = end($parts);
            if (is_numeric($id)) {
                call_user_func([$instance, $action], (int) $id);
            } else {
                call_user_func([$instance, $action]);
            }
        }
    }

    /* =========================
     * Execução de Middleware
     * ========================= */

    protected static function runMiddleware(string $middleware): void
    {
        $parts = explode(':', $middleware);
        $name = $parts[0];
        $param = $parts[1] ?? '';

        if (!isset(self::$middlewares[$name])) {
            throw new Exception("Middleware '{$name}' não encontrado.");
        }

        $middlewareClass = self::$middlewares[$name];

        if ($param) {
            (new $middlewareClass($param))->handle();
        } else {
            (new $middlewareClass())->handle();
        }
    }
}
