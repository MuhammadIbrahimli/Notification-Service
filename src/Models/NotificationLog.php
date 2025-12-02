<?php

declare(strict_types=1);

namespace NotificationService\Models;

use NotificationService\Database\DB;

class NotificationLog
{
    private ?int $id = null;
    private int $requestId;
    private string $driver;
    private bool $success;
    private ?array $response = null;
    private ?string $errorMessage = null;
    private ?string $createdAt = null;

    public function __construct(
        int $requestId,
        string $driver,
        bool $success,
        ?array $response = null,
        ?string $errorMessage = null,
        ?int $id = null,
        ?string $createdAt = null
    ) {
        $this->id = $id;
        $this->requestId = $requestId;
        $this->driver = $driver;
        $this->success = $success;
        $this->response = $response;
        $this->errorMessage = $errorMessage;
        $this->createdAt = $createdAt;
    }

    public function save(): int
    {
        $this->id = DB::insert(
            'INSERT INTO notification_logs (request_id, driver, success, response, error_message) 
             VALUES (:request_id, :driver, :success, :response, :error_message)',
            [
                ':request_id' => $this->requestId,
                ':driver' => $this->driver,
                ':success' => $this->success ? 1 : 0,
                ':response' => $this->response ? json_encode($this->response) : null,
                ':error_message' => $this->errorMessage,
            ]
        );

        return $this->id;
    }

    public static function findByRequestId(int $requestId): array
    {
        $data = DB::fetchAll(
            'SELECT * FROM notification_logs 
             WHERE request_id = :request_id 
             ORDER BY created_at DESC',
            [':request_id' => $requestId]
        );

        return array_map(function ($row) {
            return new self(
                (int)$row['request_id'],
                $row['driver'],
                (bool)$row['success'],
                $row['response'] ? json_decode($row['response'], true) : null,
                $row['error_message'],
                (int)$row['id'],
                $row['created_at']
            );
        }, $data);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRequestId(): int
    {
        return $this->requestId;
    }

    public function getDriver(): string
    {
        return $this->driver;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getResponse(): ?array
    {
        return $this->response;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'request_id' => $this->requestId,
            'driver' => $this->driver,
            'success' => $this->success,
            'response' => $this->response,
            'error_message' => $this->errorMessage,
            'created_at' => $this->createdAt,
        ];
    }
}

