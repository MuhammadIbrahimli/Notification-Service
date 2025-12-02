<?php

declare(strict_types=1);

namespace NotificationService\Controllers;

use NotificationService\Core\Request;
use NotificationService\Core\Response;
use NotificationService\Services\NotificationService;

class NotificationController
{
    private NotificationService $notificationService;

    public function __construct()
    {
        $this->notificationService = new NotificationService();
    }

    public function send(Request $request): void
    {
        $response = new Response();

        $channel = $request->post('channel');
        $to = $request->post('to');
        $message = $request->post('message');
        $subject = $request->post('subject');
        $payload = $request->post('payload', []);

        // Валидация обязательных полей
        if (empty($channel)) {
            $response->status(400)->json([
                'error' => 'Validation Error',
                'message' => 'Channel is required',
            ]);
            return;
        }

        if (empty($to)) {
            $response->status(400)->json([
                'error' => 'Validation Error',
                'message' => 'Recipient (to) is required',
            ]);
            return;
        }

        if (empty($message)) {
            $response->status(400)->json([
                'error' => 'Validation Error',
                'message' => 'Message is required',
            ]);
            return;
        }

        try {
            $requestId = $this->notificationService->send(
                $channel,
                $to,
                $message,
                $subject,
                is_array($payload) ? $payload : []
            );

            $response->status(200)->json([
                'status' => 'queued',
                'request_id' => $requestId,
            ]);
        } catch (\InvalidArgumentException $e) {
            $response->status(400)->json([
                'error' => 'Bad Request',
                'message' => $e->getMessage(),
            ]);
        } catch (\Exception $e) {
            $response->status(500)->json([
                'error' => 'Internal Server Error',
                'message' => 'Failed to queue notification',
            ]);
        }
    }

    public function status(Request $request, string $id): void
    {
        $response = new Response();

        $requestId = (int)$id;

        if ($requestId <= 0) {
            $response->status(400)->json([
                'error' => 'Bad Request',
                'message' => 'Invalid request ID',
            ]);
            return;
        }

        $status = $this->notificationService->getStatus($requestId);

        if ($status === null) {
            $response->status(404)->json([
                'error' => 'Not Found',
                'message' => 'Notification request not found',
            ]);
            return;
        }

        $response->status(200)->json($status);
    }

    public function logs(Request $request, string $id): void
    {
        $response = new Response();

        $requestId = (int)$id;

        if ($requestId <= 0) {
            $response->status(400)->json([
                'error' => 'Bad Request',
                'message' => 'Invalid request ID',
            ]);
            return;
        }

        $status = $this->notificationService->getStatus($requestId);

        if ($status === null) {
            $response->status(404)->json([
                'error' => 'Not Found',
                'message' => 'Notification request not found',
            ]);
            return;
        }

        $logs = $this->notificationService->getLogs($requestId);

        $response->status(200)->json([
            'request_id' => $requestId,
            'logs' => $logs,
        ]);
    }

    public function health(): void
    {
        $response = new Response();

        try {
            // Проверка подключения к БД
            \NotificationService\Database\DB::getConnection();
            
            $response->status(200)->json([
                'status' => 'healthy',
                'timestamp' => date('c'),
                'version' => '1.0.0',
            ]);
        } catch (\Exception $e) {
            $response->status(503)->json([
                'status' => 'unhealthy',
                'message' => 'Database connection failed',
                'timestamp' => date('c'),
            ]);
        }
    }
}

