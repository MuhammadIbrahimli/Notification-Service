<?php

declare(strict_types=1);

namespace NotificationService\Core;

class Request
{
    private array $queryParams = [];
    private array $bodyParams = [];
    private array $headers = [];
    private string $method = 'GET';
    private string $uri = '';
    private string $path = '';

    public function __construct()
    {
        $this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $this->uri = $_SERVER['REQUEST_URI'] ?? '/';
        $this->path = parse_url($this->uri, PHP_URL_PATH) ?? '/';
        $this->queryParams = $_GET ?? [];
        $this->headers = $this->parseHeaders();
        $this->bodyParams = $this->parseBody();
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->queryParams[$key] ?? $default;
    }

    public function post(string $key, mixed $default = null): mixed
    {
        return $this->bodyParams[$key] ?? $default;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->bodyParams[$key] ?? $this->queryParams[$key] ?? $default;
    }

    public function all(): array
    {
        return array_merge($this->queryParams, $this->bodyParams);
    }

    public function has(string $key): bool
    {
        return isset($this->queryParams[$key]) || isset($this->bodyParams[$key]);
    }

    public function header(string $key, ?string $default = null): ?string
    {
        $key = strtolower($key);
        return $this->headers[$key] ?? $default;
    }

    public function headers(): array
    {
        return $this->headers;
    }

    private function parseHeaders(): array
    {
        $headers = [];
        
        if (function_exists('getallheaders')) {
            $allHeaders = getallheaders();
            if ($allHeaders !== false) {
                foreach ($allHeaders as $key => $value) {
                    $headers[strtolower($key)] = $value;
                }
            }
        } else {
            foreach ($_SERVER as $key => $value) {
                if (strpos($key, 'HTTP_') === 0) {
                    $headerName = str_replace('_', '-', substr($key, 5));
                    $headers[strtolower($headerName)] = $value;
                }
            }
        }

        return $headers;
    }

    private function parseBody(): array
    {
        $body = file_get_contents('php://input');
        
        if (empty($body)) {
            return $_POST ?? [];
        }

        $contentType = $this->header('content-type', '');
        
        if (strpos($contentType, 'application/json') !== false) {
            $decoded = json_decode($body, true);
            return is_array($decoded) ? $decoded : [];
        }

        if (strpos($contentType, 'application/x-www-form-urlencoded') !== false) {
            parse_str($body, $parsed);
            return $parsed ?? [];
        }

        return [];
    }
}

