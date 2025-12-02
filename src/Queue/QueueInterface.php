<?php

declare(strict_types=1);

namespace NotificationService\Queue;

interface QueueInterface
{
    public function push(array $payload): int;

    public function pop(): ?array;

    public function get(int $id): ?array;

    public function updateStatus(int $id, string $status): bool;

    public function incrementAttempts(int $id): bool;
}

