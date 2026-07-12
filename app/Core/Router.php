<?php

class Router
{
    private array $routes = [];

    public function add(string $path, callable|array $handler): void
    {
        $this->routes[$path] = $handler;
    }

    public function dispatch(string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $path = rtrim($path, '/') ?: '/';
        if (!isset($this->routes[$path])) {
            http_response_code(404);
            echo 'صفحة غير موجودة (404)';
            return;
        }
        $handler = $this->routes[$path];
        if (is_array($handler)) {
            [$class, $method] = $handler;
            (new $class())->$method();
        } else {
            $handler();
        }
    }
}
