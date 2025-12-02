<?php

declare(strict_types=1);

namespace NotificationService\Drivers;

class SmsDriver implements NotificationDriverInterface
{
    private array $config;
    private const MAX_RETRIES = 3;
    private const RETRY_DELAY = 2; // секунды

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function send(array $payload): NotificationResult
    {
        $to = $payload['to'] ?? null;
        $message = $payload['message'] ?? '';

        if (empty($to)) {
            return new NotificationResult(
                false,
                'SMS recipient phone number is required'
            );
        }

        if (empty($message)) {
            return new NotificationResult(
                false,
                'SMS message is required'
            );
        }

        $apiUrl = $this->config['api_url'] ?? '';
        $apiKey = $this->config['api_key'] ?? '';
        $sender = $this->config['sender'] ?? 'Notification';

        if (empty($apiUrl) || empty($apiKey)) {
            return new NotificationResult(
                false,
                'SMS API configuration is missing'
            );
        }

        $requestData = [
            'to' => $to,
            'message' => $message,
            'sender' => $sender,
        ];

        $lastError = null;
        $lastStatusCode = null;

        for ($attempt = 1; $attempt <= self::MAX_RETRIES; $attempt++) {
            $result = $this->attemptSend($apiUrl, $apiKey, $requestData);

            if ($result['success']) {
                return new NotificationResult(
                    true,
                    'SMS sent successfully',
                    $result['response'],
                    $result['status_code']
                );
            }

            $lastError = $result['error'];
            $lastStatusCode = $result['status_code'];

            if ($attempt < self::MAX_RETRIES) {
                sleep(self::RETRY_DELAY * $attempt);
            }
        }

        return new NotificationResult(
            false,
            'Failed to send SMS after ' . self::MAX_RETRIES . ' attempts: ' . $lastError,
            null,
            $lastStatusCode ?? 500
        );
    }

    private function attemptSend(string $apiUrl, string $apiKey, array $data): array
    {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
            ],
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
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

        if ($httpCode >= 200 && $httpCode < 300) {
            $decoded = json_decode($response, true);
            return [
                'success' => true,
                'response' => $decoded ?? $response,
                'error' => null,
                'status_code' => $httpCode,
            ];
        }

        return [
            'success' => false,
            'response' => $response,
            'error' => "HTTP $httpCode: " . ($response ?: 'Unknown error'),
            'status_code' => $httpCode,
        ];
    }
}

