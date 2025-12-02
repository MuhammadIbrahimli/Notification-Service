<?php

declare(strict_types=1);

namespace NotificationService\Drivers;

class TelegramDriver implements NotificationDriverInterface {
    private array $config;
    private const MAX_RETRIES = 3;
    private const RETRY_DELAY = 1; // секунды

    public function __construct(array $config) {
        $this->config = $config;
    }

    public function send(array $payload): NotificationResult {
        $chatId = $payload['to'] ?? null;
        $message = $payload['message'] ?? '';
        $parseMode = $payload['parse_mode'] ?? 'HTML';

        if (empty($chatId)) {
            return new NotificationResult(
                false,
                'Telegram chat ID is required'
            );
        }

        if (empty($message)) {
            return new NotificationResult(
                false,
                'Telegram message is required'
            );
        }

        $botToken = trim($this->config['bot_token'] ?? '');
        $apiUrl = $this->config['api_url'] ?? 'https://api.telegram.org/bot';

        if (empty($botToken)) {
            return new NotificationResult(
                false,
                'Telegram bot token is not configured. Please set TELEGRAM_BOT_TOKEN in .env file'
            );
        }

        // Формируем правильный URL для Telegram API
        $baseUrl = rtrim($apiUrl, '/');
        if (substr($baseUrl, -4) !== '/bot') {
            $baseUrl = rtrim($baseUrl, '/') . '/bot';
        }
        $url = $baseUrl . $botToken . '/sendMessage';

        $requestData = [
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => $parseMode,
        ];

        if (isset($payload['reply_markup'])) {
            $requestData['reply_markup'] = $payload['reply_markup'];
        }

        $lastError = null;
        $lastStatusCode = null;
        $lastResponse = null;

        for ($attempt = 1; $attempt <= self::MAX_RETRIES; $attempt++) {
            $result = $this->attemptSend($url, $requestData);

            if ($result['success']) {
                return new NotificationResult(
                    true,
                    'Telegram message sent successfully',
                    $result['response'],
                    $result['status_code']
                );
            }

            $lastError = $result['error'];
            $lastStatusCode = $result['status_code'];
            $lastResponse = $result['response']; // Сохраняем ответ от Telegram API для отладки

            if ($attempt < self::MAX_RETRIES) {
                sleep(self::RETRY_DELAY * $attempt);
            }
        }

        return new NotificationResult(
            false,
            'Failed to send Telegram message after ' . self::MAX_RETRIES . ' attempts: ' . $lastError,
            $lastResponse,
            $lastStatusCode ?? 500
        );
    }

    private function attemptSend(string $url, array $data): array {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        if ($error) {
            return [
                'success' => false,
                'response' => null,
                'error' => $error,
                'status_code' => 0,
            ];
        }

        $decoded = json_decode($response, true);

        if ($httpCode === 200 && isset($decoded['ok']) && $decoded['ok'] === true) {
            return [
                'success' => true,
                'response' => $decoded,
                'error' => null,
                'status_code' => $httpCode,
            ];
        }

        // Улучшенная обработка ошибок от Telegram API
        $errorMessage = 'Unknown error';

        if ($decoded && isset($decoded['description'])) {
            $errorMessage = $decoded['description'];
        } elseif ($decoded && isset($decoded['error_code'])) {
            $errorMessage = "Error code: " . $decoded['error_code'];
            if (isset($decoded['description'])) {
                $errorMessage .= " - " . $decoded['description'];
            }
        } elseif ($httpCode === 404) {
            $errorMessage = "Not Found - Check if bot token is correct and URL is valid. URL: " . substr($url, 0, 50) . "...";
        } elseif ($response) {
            $errorMessage = $response;
        }

        return [
            'success' => false,
            'response' => $decoded,
            'error' => $errorMessage,
            'status_code' => $httpCode,
        ];
    }
}
