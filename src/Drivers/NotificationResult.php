<?php

declare(strict_types=1);

namespace NotificationService\Drivers;

class NotificationResult
{
    private bool $success;
    private ?string $message;
    private mixed $response;
    private ?int $statusCode;

    public function __construct(
        bool $success,
        ?string $message = null,
        mixed $response = null,
        ?int $statusCode = null
    ) {
        $this->success = $success;
        $this->message = $message;
        $this->response = $response;
        $this->statusCode = $statusCode;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function getResponse(): mixed
    {
        return $this->response;
    }

    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'response' => $this->response,
            'status_code' => $this->statusCode,
        ];
    }
}

