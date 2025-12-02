<?php

declare(strict_types=1);

namespace NotificationService\Queue;

use NotificationService\Database\DB;

class FileQueue implements QueueInterface
{
    private string $queueDir;
    private const MAX_RETRIES = 3;

    public function __construct()
    {
        $this->queueDir = dirname(__DIR__, 2) . '/storage/queue';
        
        if (!is_dir($this->queueDir)) {
            mkdir($this->queueDir, 0755, true);
        }
    }

    public function push(array $payload): int
    {
        // Используем базу данных для очереди
        return DB::insert(
            'INSERT INTO queue (payload, status) VALUES (:payload, :status)',
            [
                ':payload' => json_encode($payload),
                ':status' => 'pending',
            ]
        );
    }

    public function pop(): ?array
    {
        // Используем транзакцию для безопасного получения задачи
        try {
            DB::beginTransaction();

            $job = DB::fetch(
                'SELECT * FROM queue 
                 WHERE status = :status 
                 ORDER BY created_at ASC 
                 LIMIT 1 
                 FOR UPDATE',
                [':status' => 'pending']
            );

            if (!$job) {
                DB::rollback();
                return null;
            }

            DB::execute(
                'UPDATE queue SET status = :status WHERE id = :id',
                [
                    ':id' => $job['id'],
                    ':status' => 'processing',
                ]
            );

            DB::commit();

            return [
                'id' => (int)$job['id'],
                'payload' => json_decode($job['payload'], true) ?? [],
                'attempts' => (int)$job['attempts'],
            ];
        } catch (\Exception $e) {
            DB::rollback();
            return null;
        }
    }

    public function get(int $id): ?array
    {
        $job = DB::fetch(
            'SELECT * FROM queue WHERE id = :id',
            [':id' => $id]
        );

        if (!$job) {
            return null;
        }

        return [
            'id' => (int)$job['id'],
            'payload' => json_decode($job['payload'], true) ?? [],
            'status' => $job['status'],
            'attempts' => (int)$job['attempts'],
            'created_at' => $job['created_at'],
        ];
    }

    public function updateStatus(int $id, string $status): bool
    {
        return DB::execute(
            'UPDATE queue SET status = :status WHERE id = :id',
            [
                ':id' => $id,
                ':status' => $status,
            ]
        );
    }

    public function incrementAttempts(int $id): bool
    {
        return DB::execute(
            'UPDATE queue SET attempts = attempts + 1 WHERE id = :id',
            [':id' => $id]
        );
    }
}

