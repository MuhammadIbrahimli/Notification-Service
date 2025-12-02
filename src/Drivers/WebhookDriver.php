<?php

declare(strict_types=1);

namespace NotificationService\Drivers;

class WebhookDriver implements NotificationDriverInterface
{
    private array $config;
    private const MAX_RETRIES = 3;
    private const DEFAULT_RETRY_DELAY = 2; // секунды

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function send(array $payload): NotificationResult
    {
        $url = $payload['url'] ?? null;
        $data = $payload['data'] ?? $payload['payload'] ?? $payload;

        if (empty($url)) {
            return new NotificationResult(
                false,
                'Webhook URL is required'
            );
        }

        $method = strtoupper($payload['method'] ?? 'POST');
        $headers = $payload['headers'] ?? [];
        $timeout = $this->config['timeout'] ?? 10;
        $maxRetries = $this->config['retry_count'] ?? self::MAX_RETRIES;

        $lastError = null;
        $lastStatusCode = null;

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            $result = $this->attemptSend($url, $method, $data, $headers, $timeout);

            if ($result['success']) {
                return new NotificationResult(
                    true,
                    'Webhook sent successfully',
                    $result['response'],
                    $result['status_code']
                );
            }

            $lastError = $result['error'];
            $lastStatusCode = $result['status_code'];

            if ($attempt < $maxRetries) {
                sleep(self::DEFAULT_RETRY_DELAY * $attempt);
            }
        }

        return new NotificationResult(
            false,
            "Failed to send webhook after $maxRetries attempts: $lastError",
            null,
            $lastStatusCode ?? 500
        );
    }

    private function attemptSend(
        string $url,
        string $method,
        array $data,
        array $headers,
        int $timeout
    ): array {
        $ch = curl_init();

        $defaultHeaders = [
            'Content-Type: application/json',
            'User-Agent: NotificationService/1.0',
        ];

        $allHeaders = array_merge($defaultHeaders, $headers);

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $allHeaders,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'PATCH') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'GET') {
            if (!empty($data)) {
                $url .= (strpos($url, '?') !== false ? '&' : '?') . http_build_query($data);
                curl_setopt($ch, CURLOPT_URL, $url);
            }
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $errorNo = curl_errno($ch);

        curl_close($ch);

        if ($errorNo !== CURLE_OK) {
            return [
                'success' => false,
                'response' => null,
                'error' => $error ?: 'cURL error ' . $errorNo,
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

