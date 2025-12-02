<?php

declare(strict_types=1);

namespace NotificationService\Services;

use NotificationService\Drivers\NotificationDriverInterface;
use NotificationService\Models\Notification;
use NotificationService\Models\NotificationLog;
use NotificationService\Queue\QueueInterface;
use NotificationService\Queue\FileQueue;

class NotificationService
{
    private DriverManager $driverManager;
    private QueueInterface $queue;

    public function __construct(?DriverManager $driverManager = null, ?QueueInterface $queue = null)
    {
        $this->driverManager = $driverManager ?? new DriverManager();
        $this->queue = $queue ?? new FileQueue();
    }

    public function send(
        string $channel,
        string $to,
        string $message,
        ?string $subject = null,
        array $additionalPayload = []
    ): int {
        // Валидация канала
        if (!$this->driverManager->hasDriver($channel)) {
            throw new \InvalidArgumentException("Unsupported channel: $channel");
        }

        // Подготовка payload
        $payload = array_merge([
            'to' => $to,
            'message' => $message,
            'subject' => $subject,
        ], $additionalPayload);

        // Создание записи запроса
        $notification = new Notification($channel, $payload, 'pending');
        $requestId = $notification->save();

        // Добавление в очередь
        $queuePayload = [
            'request_id' => $requestId,
            'channel' => $channel,
            'payload' => $payload,
        ];

        $this->queue->push($queuePayload);

        // Обновление статуса
        Notification::updateStatus($requestId, 'queued');

        return $requestId;
    }

    public function processFromQueue(array $queuePayload): bool
    {
        $requestId = $queuePayload['request_id'] ?? null;
        $channel = $queuePayload['channel'] ?? null;
        $payload = $queuePayload['payload'] ?? [];

        if (!$requestId || !$channel) {
            return false;
        }

        try {
            // Обновление статуса
            Notification::updateStatus($requestId, 'processing');

            // Получение драйвера
            $driver = $this->driverManager->getDriver($channel);

            // Отправка уведомления
            $result = $driver->send($payload);

            // Сохранение лога
            $log = new NotificationLog(
                $requestId,
                $channel,
                $result->isSuccess(),
                $result->getResponse(),
                $result->isSuccess() ? null : $result->getMessage()
            );
            $log->save();

            // Обновление статуса
            $status = $result->isSuccess() ? 'completed' : 'failed';
            Notification::updateStatus($requestId, $status);

            return $result->isSuccess();
        } catch (\Exception $e) {
            // Сохранение ошибки в лог
            if ($requestId) {
                $log = new NotificationLog(
                    $requestId,
                    $channel ?? 'unknown',
                    false,
                    null,
                    $e->getMessage()
                );
                $log->save();

                Notification::updateStatus($requestId, 'failed');
            }

            throw $e;
        }
    }

    public function getStatus(int $requestId): ?array
    {
        $notification = Notification::find($requestId);

        if (!$notification) {
            return null;
        }

        return $notification->toArray();
    }

    public function getLogs(int $requestId): array
    {
        $logs = NotificationLog::findByRequestId($requestId);

        return array_map(fn($log) => $log->toArray(), $logs);
    }
}

