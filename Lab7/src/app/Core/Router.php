<?php
// app/Core/Router.php — маршрутизатор: по методу и адресу запроса выбирает контроллер.

declare(strict_types=1);

namespace App\Core;

final class Router
{
    // Таблица маршрутов
    private array $routes = [];

    public function get(string $path, array $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, array $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    // Для REST API:
    public function put(string $path, array $handler): void
    {
        $this->add('PUT', $path, $handler);
    }

    public function patch(string $path, array $handler): void
    {
        $this->add('PATCH', $path, $handler);
    }

    public function delete(string $path, array $handler): void
    {
        $this->add('DELETE', $path, $handler);
    }

    private function add(string $method, string $path, array $handler): void
    {
        $this->routes[] = ['method' => $method, 'path' => $path, 'handler' => $handler];
    }

    // Находит подходящий маршрут и передаёт управление контроллеру
    public function dispatch(string $method, string $uri): void
    {
        $path = rtrim(parse_url($uri, PHP_URL_PATH) ?: '/', '/') ?: '/';

        $allowed = []; // методы, которые есть у этого адреса

        foreach ($this->routes as $route) {
            $params = $this->match($route['path'], $path);
            if ($params === null) {
                continue;
            }
            if ($route['method'] !== $method) {
                $allowed[] = $route['method'];
                continue;
            }

            [$class, $action] = $route['handler'];
            $controller = new $class();
            $controller->$action(...$params);
            return;
        }

        // Ни один маршрут не подошёл
        if ($allowed && is_api_request()) {
            header('Allow: ' . implode(', ', array_unique($allowed)));
            json_error('Метод ' . $method . ' для этого адреса не поддерживается', 405);
        }
        if (is_api_request()) {
            json_error('Адрес ' . $path . ' не найден', 404);
        }
        
        http_response_code(404);
        view('errors/404', ['path' => $path]);
    }

    private function match(string $pattern, string $path): ?array
    {
        $regex = '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $pattern) . '$#';
        if (!preg_match($regex, $path, $matches)) {
            return null;
        }
        return array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
    }
}