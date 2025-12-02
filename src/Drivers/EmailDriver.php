<?php

declare(strict_types=1);

namespace NotificationService\Drivers;

class EmailDriver implements NotificationDriverInterface
{
    private array $config;
    private const MAX_RETRIES = 3;
    private const RETRY_DELAY = 1; // секунды

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function send(array $payload): NotificationResult
    {
        $to = $payload['to'] ?? null;
        $subject = $payload['subject'] ?? 'Notification';
        $message = $payload['message'] ?? '';
        $htmlMessage = $payload['html'] ?? null;

        if (empty($to)) {
            return new NotificationResult(
                false,
                'Email recipient is required'
            );
        }

        $headers = [
            'From: ' . $this->config['from_name'] . ' <' . $this->config['from_email'] . '>',
            'Reply-To: ' . $this->config['from_email'],
            'X-Mailer: PHP/' . phpversion(),
        ];

        if ($htmlMessage) {
            $headers[] = 'MIME-Version: 1.0';
            $headers[] = 'Content-type: text/html; charset=UTF-8';
            $message = $htmlMessage;
        } else {
            $headers[] = 'Content-type: text/plain; charset=UTF-8';
        }

        $lastError = null;

        for ($attempt = 1; $attempt <= self::MAX_RETRIES; $attempt++) {
            $result = $this->attemptSend($to, $subject, $message, $headers);

            if ($result['success']) {
                return new NotificationResult(
                    true,
                    'Email sent successfully',
                    $result['response']
                );
            }

            $lastError = $result['error'];

            if ($attempt < self::MAX_RETRIES) {
                sleep(self::RETRY_DELAY * $attempt);
            }
        }

        return new NotificationResult(
            false,
            'Failed to send email after ' . self::MAX_RETRIES . ' attempts: ' . $lastError,
            null,
            500
        );
    }

    private function attemptSend(string $to, string $subject, string $message, array $headers): array
    {
        try {
            if (!empty($this->config['smtp_host'])) {
                return $this->sendViaSmtp($to, $subject, $message);
            }

            $result = @mail($to, $subject, $message, implode("\r\n", $headers));

            return [
                'success' => $result,
                'response' => $result ? 'Sent via mail()' : null,
                'error' => $result ? null : error_get_last()['message'] ?? 'Unknown error',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'response' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function sendViaSmtp(string $to, string $subject, string $message): array
    {
        // Простая реализация SMTP через socket
        // В production лучше использовать библиотеку типа PHPMailer или SwiftMailer
        
        $smtpHost = $this->config['smtp_host'];
        $smtpPort = $this->config['smtp_port'] ?? 587;
        
        try {
            $socket = @fsockopen($smtpHost, $smtpPort, $errno, $errstr, 10);
            
            if (!$socket) {
                return [
                    'success' => false,
                    'response' => null,
                    'error' => "Failed to connect to SMTP server: $errstr ($errno)",
                ];
            }

            $response = fgets($socket, 515);
            
            // Базовое SMTP handshake (упрощённая версия)
            fputs($socket, "EHLO localhost\r\n");
            fgets($socket, 515);
            
            if ($smtpPort === 587) {
                fputs($socket, "STARTTLS\r\n");
                fgets($socket, 515);
                stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                fputs($socket, "EHLO localhost\r\n");
                fgets($socket, 515);
            }
            
            if (!empty($this->config['smtp_user'])) {
                fputs($socket, "AUTH LOGIN\r\n");
                fgets($socket, 515);
                fputs($socket, base64_encode($this->config['smtp_user']) . "\r\n");
                fgets($socket, 515);
                fputs($socket, base64_encode($this->config['smtp_pass']) . "\r\n");
                fgets($socket, 515);
            }
            
            fputs($socket, "MAIL FROM: <{$this->config['from_email']}>\r\n");
            fgets($socket, 515);
            fputs($socket, "RCPT TO: <$to>\r\n");
            fgets($socket, 515);
            fputs($socket, "DATA\r\n");
            fgets($socket, 515);
            fputs($socket, "Subject: $subject\r\n");
            fputs($socket, "To: <$to>\r\n");
            fputs($socket, "From: {$this->config['from_name']} <{$this->config['from_email']}>\r\n");
            fputs($socket, "\r\n$message\r\n");
            fputs($socket, ".\r\n");
            $response = fgets($socket, 515);
            fputs($socket, "QUIT\r\n");
            fclose($socket);
            
            if (strpos($response, '250') === 0) {
                return [
                    'success' => true,
                    'response' => 'Sent via SMTP',
                    'error' => null,
                ];
            }
            
            return [
                'success' => false,
                'response' => null,
                'error' => 'SMTP error: ' . trim($response),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'response' => null,
                'error' => $e->getMessage(),
            ];
        }
    }
}

