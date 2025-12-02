<?php

declare(strict_types=1);

return [
    'email' => [
        'driver' => \NotificationService\Drivers\EmailDriver::class,
        'config' => [
            'smtp_host' => $_ENV['EMAIL_SMTP_HOST'] ?? 'localhost',
            'smtp_port' => (int)($_ENV['EMAIL_SMTP_PORT'] ?? 587),
            'smtp_user' => $_ENV['EMAIL_SMTP_USER'] ?? '',
            'smtp_pass' => $_ENV['EMAIL_SMTP_PASS'] ?? '',
            'from_email' => $_ENV['EMAIL_FROM'] ?? 'noreply@example.com',
            'from_name' => $_ENV['EMAIL_FROM_NAME'] ?? 'Notification Center',
        ],
    ],
    'sms' => [
        'driver' => \NotificationService\Drivers\SmsDriver::class,
        'config' => [
            'api_url' => $_ENV['SMS_API_URL'] ?? '',
            'api_key' => $_ENV['SMS_API_KEY'] ?? '',
            'sender' => $_ENV['SMS_SENDER'] ?? 'Notification',
        ],
    ],
    'telegram' => [
        'driver' => \NotificationService\Drivers\TelegramDriver::class,
        'config' => [
            'bot_token' => $_ENV['TELEGRAM_BOT_TOKEN'] ?? '',
            'api_url' => $_ENV['TELEGRAM_API_URL'] ?? 'https://api.telegram.org/bot',
        ],
    ],
    'webhook' => [
        'driver' => \NotificationService\Drivers\WebhookDriver::class,
        'config' => [
            'timeout' => (int)($_ENV['WEBHOOK_TIMEOUT'] ?? 10),
            'retry_count' => (int)($_ENV['WEBHOOK_RETRY_COUNT'] ?? 3),
        ],
    ],
];
