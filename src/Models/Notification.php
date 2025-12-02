<?php

declare(strict_types=1);

namespace NotificationService\Models;

use NotificationService\Database\DB;

class Notification
{
    private ?int $id = null;
    private string $channel;
    private array $payload;
    private string $status;
    private ?string $createdAt = null;
    private ?string $updatedAt = null;

    public function __construct(
        string $channel,
        array $payload,
        string $status = 'pending',
        ?int $id = null,
        ?string $createdAt = null,
        ?string $updatedAt = null
    ) {
        $this->id = $id;
        $this->channel = $channel;
        $this->payload = $payload;
        $this->status = $status;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public function save(): int
    {
        if ($this->id !== null) {
            return $this->update();
        }

        return $this->insert();
    }

    private function insert(): int
    {
        $this->id = DB::insert(
            'INSERT INTO notification_requests (channel, payload, status) 
             VALUES (:channel, :payload, :status)',
            [
                ':channel' => $this->channel,
                ':payload' => json_encode($this->payload),
                ':status' => $this->status,
            ]
        );

        return $this->id;
    }

    private function update(): int
    {
        DB::execute(
            'UPDATE notification_requests 
             SET status = :status, payload = :payload 
             WHERE id = :id',
            [
                ':id' => $this->id,
                ':status' => $this->status,
                ':payload' => json_encode($this->payload),
            ]
        );

        return $this->id;
    }

    public static function find(int $id): ?self
    {
        $data = DB::fetch(
            'SELECT * FROM notification_requests WHERE id = :id',
            [':id' => $id]
        );

        if (!$data) {
            return null;
        }

        return new self(
            $data['channel'],
            json_decode($data['payload'], true) ?? [],
            $data['status'],
            (int)$data['id'],
            $data['created_at'],
            $data['updated_at']
        );
    }

    public static function updateStatus(int $id, string $status): bool
    {
        return DB::execute(
            'UPDATE notification_requests SET status = :status WHERE id = :id',
            [
                ':id' => $id,
                ':status' => $status,
            ]
        );
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getChannel(): string
    {
        return $this->channel;
    }

    public function getPayload(): array
    {
        return $this->payload;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?string
    {
        return $this->updatedAt;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'channel' => $this->channel,
            'payload' => $this->payload,
            'status' => $this->status,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}

