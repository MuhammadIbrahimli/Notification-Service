<?php

declare(strict_types=1);

namespace NotificationService\Core;

class Response {
    private int $statusCode = 200;
    private array $headers = [];
    private mixed $body = null;

    public function status(int $code): self {
        $this->statusCode = $code;
        return $this;
    }

    public function header(string $name, string $value): self {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * @param array|object $data
     */
    public function json($data, ?int $statusCode = null): void {
        if ($statusCode !== null) {
            $this->statusCode = $statusCode;
        }

        $this->header('Content-Type', 'application/json; charset=utf-8');
        $this->body = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $this->send();
    }

    public function text(string $text, int $statusCode = null): void {
        if ($statusCode !== null) {
            $this->statusCode = $statusCode;
        }

        $this->header('Content-Type', 'text/plain; charset=utf-8');
        $this->body = $text;
        $this->send();
    }

    public function html(string $html, int $statusCode = null): void {
        if ($statusCode !== null) {
            $this->statusCode = $statusCode;
        }

        $this->header('Content-Type', 'text/html; charset=utf-8');
        $this->body = $html;
        $this->send();
    }

    public function send(): void {
        http_response_code($this->statusCode);

        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }

        if ($this->body !== null) {
            echo $this->body;
        }

        exit;
    }
}
