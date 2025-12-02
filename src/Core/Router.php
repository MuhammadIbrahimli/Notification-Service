<?php

declare(strict_types=1);

namespace NotificationService\Core;

use Closure;

class Router
{
    private array $routes = [];
    private array $middlewares = [];

    public function get(string $path, callable|string $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, callable|string $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    public function put(string $path, callable|string $handler): void
    {
        $this->addRoute('PUT', $path, $handler);
    }

    public function delete(string $path, callable|string $handler): void
    {
        $this->addRoute('DELETE', $path, $handler);
    }

    public function addRoute(string $method, string $path, callable|string $handler): void
    {
        $pattern = $this->convertToRegex($path);
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'pattern' => $pattern,
            'handler' => $handler,
        ];
    }

    public function dispatch(Request $request): void
    {
        $method = $request->getMethod();
        $path = $request->getPath();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['pattern'], $path, $matches)) {
                array_shift($matches);
                
                $handler = $route['handler'];
                $params = $this->extractParams($route['path'], $matches);

                if (is_string($handler) && strpos($handler, '@') !== false) {
                    [$class, $method] = explode('@', $handler);
                    $handler = [new $class(), $method];
                }

                if ($handler instanceof Closure || is_callable($handler)) {
                    call_user_func_array($handler, array_merge([$request], $params));
                    return;
                }
            }
        }

        $this->notFound();
    }

    private function convertToRegex(string $path): string
    {
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $path);
        return '#^' . $pattern . '$#';
    }

    private function extractParams(string $path, array $matches): array
    {
        preg_match_all('/\{([a-zA-Z0-9_]+)\}/', $path, $paramNames);
        $params = [];

        foreach ($paramNames[1] as $index => $name) {
            $params[$name] = $matches[$index] ?? null;
        }

        return array_values($params);
    }

    private function notFound(): void
    {
        $response = new Response();
        $response->status(404)->json([
            'error' => 'Not Found',
            'message' => 'The requested resource was not found.',
        ]);
    }
}

