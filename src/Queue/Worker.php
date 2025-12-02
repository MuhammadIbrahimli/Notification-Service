<?php

declare(strict_types=1);

namespace NotificationService\Queue;

use NotificationService\Core\Env;
use NotificationService\Services\NotificationService;

class Worker {
    private QueueInterface $queue;
    private NotificationService $notificationService;
    private bool $running = true;
    private int $sleepSeconds = 5;

    public function __construct() {
        Env::load();
        $this->queue = new FileQueue();
        $this->notificationService = new NotificationService();
    }

    public function start(): void {
        echo "Queue worker started...\n";
        echo "Press Ctrl+C to stop\n\n";

        // Обработка сигналов для корректного завершения
        if (function_exists('pcntl_signal')) {
            pcntl_signal(SIGTERM, [$this, 'handleSignal']);
            pcntl_signal(SIGINT, [$this, 'handleSignal']);
        }

        while ($this->running) {
            $this->processNextJob();
            $this->sleep();
        }

        echo "\nQueue worker stopped.\n";
    }

    public function handleSignal(int $signal): void {
        echo "\nReceived signal $signal, stopping worker...\n";
        $this->running = false;
    }

    private function processNextJob(): void {
        if (function_exists('pcntl_signal_dispatch')) {
            pcntl_signal_dispatch();
        }

        $job = $this->queue->pop();

        if ($job === null) {
            return;
        }

        $jobId = $job['id'];
        $payload = $job['payload'];
        $attempts = $job['attempts'];
        $channel = $payload['channel'] ?? 'unknown';
        $to = $payload['payload']['to'] ?? 'unknown';

        echo "\n[" . date('Y-m-d H:i:s') . "] Processing job #$jobId\n";
        echo "  Channel: $channel\n";
        echo "  To: $to\n";
        echo "  Attempt: " . ($attempts + 1) . "\n";

        try {
            $result = $this->notificationService->processFromQueue($payload);

            if ($result) {
                $this->queue->updateStatus($jobId, 'completed');
                echo "  ✅ Job #$jobId completed successfully!\n";
            } else {
                echo "  ❌ Job #$jobId failed (check logs in database)\n";
                $this->handleJobFailure($jobId, $attempts, $payload);
            }
        } catch (\Exception $e) {
            echo "  ❌ Job #$jobId exception: " . $e->getMessage() . "\n";
            echo "  File: " . $e->getFile() . ":" . $e->getLine() . "\n";
            if ($e->getTraceAsString()) {
                echo "  Trace: " . substr($e->getTraceAsString(), 0, 200) . "...\n";
            }
            $this->handleJobFailure($jobId, $attempts, $payload);
        }
    }

    private function handleJobFailure(int $jobId, int $attempts, array $payload): void {
        $this->queue->incrementAttempts($jobId);

        if ($attempts >= 2) {
            $this->queue->updateStatus($jobId, 'failed');
            echo "  ⚠️  Job #$jobId failed after maximum attempts (3).\n";
            echo "  Check notification_logs table in database for details.\n";

            // Показываем информацию о последней ошибке из логов
            $this->showLastError($payload['request_id'] ?? null);
        } else {
            $this->queue->updateStatus($jobId, 'pending');
            echo "  🔄 Job #$jobId will be retried...\n";
        }
    }

    private function showLastError(?int $requestId): void {
        if (!$requestId) {
            return;
        }

        try {
            $logs = \NotificationService\Models\NotificationLog::findByRequestId($requestId);
            if (!empty($logs)) {
                $lastLog = $logs[0];
                if (!$lastLog->isSuccess() && $lastLog->getErrorMessage()) {
                    echo "  Error: " . $lastLog->getErrorMessage() . "\n";
                    $response = $lastLog->getResponse();
                    if ($response && isset($response['description'])) {
                        echo "  Telegram API: " . $response['description'] . "\n";
                    }
                }
            }
        } catch (\Exception $e) {
            // Игнорируем ошибки при получении логов
        }
    }

    private function sleep(): void {
        if (function_exists('usleep')) {
            usleep($this->sleepSeconds * 1000000);
        } else {
            sleep($this->sleepSeconds);
        }
    }
}

// Запуск воркера, если скрипт вызван напрямую
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

    $worker = new Worker();
    $worker->start();
}
