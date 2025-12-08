# Notification Center

Modular service for sending notifications via various communication channels in pure PHP 8.2+ without frameworks.

## 📋 Description

Notification Center is a production-ready service that accepts HTTP requests and sends notifications through different channels:
- 📧 **Email** — via SMTP or mail() function
- 📱 **SMS** — via providers' REST API
- 💬 **Telegram** — via Bot API
- 🔗 **Webhook** — sending to arbitrary HTTP endpoints

## ✨ Features

- ✅ Modular architecture with separation of concerns (SRP)
- ✅ Task queue for asynchronous processing
- ✅ Retry mechanism for all drivers (3 attempts)
- ✅ Logging of all operations
- ✅ Extensible driver system
- ✅ Full typing with `declare(strict_types=1)`
- ✅ Docker environment for development and production
- ✅ RESTful API

## 🏗️ Architecture

```
Request → Router → Controller → NotificationService → DriverManager → Drivers
```

Each driver implements the `NotificationDriverInterface` and can be easily extended.

## 📁 Project Structure

```
project/
├── public/              # Entry point
│   └── index.php
├── src/
│   ├── Controllers/     # Controllers
│   ├── Services/        # Business logic
│   ├── Drivers/         # Notification drivers
│   ├── Core/            # Core (Router, Request, Response)
│   ├── Models/          # Data models
│   ├── Queue/           # Queue system
│   └── Database/        # Database operations
├── config/              # Configuration files
├── storage/             # Logs and files
└── docker/              # Docker configuration
```

## 🚀 Quick Start

### Requirements

- PHP 8.2+
- Composer
- MySQL 5.7+ or 8.0+
- Docker and Docker Compose (optional)

### Installation

1. **Clone the repository:**

```bash
git clone <repository-url>
cd notification-service
```

2. **Install dependencies:**

```bash
composer install
```

3. **Environment setup:**

Create a `.env` file based on `.env.example`:

```bash
cp .env.example .env
```

Edit `.env` and specify database connection parameters and driver settings.

4. **Create database:**

```bash
# Create DB manually or use migrations
mysql -u root -p -e "CREATE DATABASE notification_service CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

5. **Run migrations:**

```bash
composer migrate
```

Or manually:

```bash
php -r "require 'vendor/autoload.php'; \NotificationService\Database\DB::migrate();"
```

6. **Start built-in PHP server:**

```bash
php -S localhost:8000 -t public
```

7. **Start queue worker (in a separate terminal):**

```bash
php src/Queue/Worker.php
```

Or via composer:

```bash
composer worker
```

## 🐳 Docker

### Run via Docker Compose

1. **Start all services:**

```bash
docker-compose up -d
```

2. **Run migrations:**

```bash
docker-compose exec apache composer migrate
```

3. **Check status:**

```bash
docker-compose ps
```

4. **View logs:**

```bash
docker-compose logs -f worker
docker-compose logs -f apache
```

5. **Stop:**

```bash
docker-compose down
```

The service will be available at: `http://localhost:8080`

## 📡 API Endpoints

### 1. Send notification

**POST** `/send`

```json
{
  "channel": "telegram",
  "to": "123456789",
  "message": "Hello, World!",
  "subject": "Optional subject",
  "payload": {
    "parse_mode": "HTML"
  }
}
```

**Response:**

```json
{
  "status": "queued",
  "request_id": 12
}
```

### 2. Get notification status

**GET** `/status/{id}`

**Response:**

```json
{
  "id": 12,
  "channel": "telegram",
  "payload": {
    "to": "123456789",
    "message": "Hello, World!"
  },
  "status": "completed",
  "created_at": "2024-01-15 10:30:00",
  "updated_at": "2024-01-15 10:30:05"
}
```

### 3. Get notification logs

**GET** `/logs/{id}`

**Response:**

```json
{
  "request_id": 12,
  "logs": [
    {
      "id": 1,
      "driver": "telegram",
      "success": true,
      "response": {
        "ok": true,
        "result": {...}
      },
      "created_at": "2024-01-15 10:30:05"
    }
  ]
}
```

### 4. Health Check

**GET** `/health`

**Response:**

```json
{
  "status": "healthy",
  "timestamp": "2024-01-15T10:30:00+00:00",
  "version": "1.0.0"
}
```

## 🔌 Supported Channels

### Email

```json
{
  "channel": "email",
  "to": "user@example.com",
  "subject": "Test Email",
  "message": "This is a test message",
  "payload": {
    "html": "<p>HTML content</p>"
  }
}
```

### SMS

```json
{
  "channel": "sms",
  "to": "+1234567890",
  "message": "Your verification code is 1234"
}
```

### Telegram

```json
{
  "channel": "telegram",
  "to": "123456789",
  "message": "Hello from Notification Center!",
  "payload": {
    "parse_mode": "HTML"
  }
}
```

### Webhook

```json
{
  "channel": "webhook",
  "payload": {
    "url": "https://example.com/webhook",
    "method": "POST",
    "data": {
      "event": "notification",
      "message": "Test"
    },
    "headers": {
      "X-Custom-Header": "value"
    }
  }
}
```

## ⚙️ Configuration

Driver settings are located in `config/drivers.php`. Environment variables are configured in `.env`:

- `EMAIL_SMTP_HOST` — SMTP server
- `EMAIL_SMTP_PORT` — SMTP port
- `EMAIL_SMTP_USER` — SMTP user
- `EMAIL_SMTP_PASS` — SMTP password
- `SMS_API_URL` — API URL for SMS
- `SMS_API_KEY` — API key for SMS
- `TELEGRAM_BOT_TOKEN` — Telegram bot token
- `WEBHOOK_TIMEOUT` — Timeout for webhook requests

## 🔧 Development

### Adding a new driver

1. Create a driver class implementing `NotificationDriverInterface`:

```php
<?php

declare(strict_types=1);

namespace NotificationService\Drivers;

class CustomDriver implements NotificationDriverInterface
{
    public function send(array $payload): NotificationResult
    {
        // Your sending logic
        return new NotificationResult(true, 'Success');
    }
}
```

2. Add configuration to `config/drivers.php`:

```php
'custom' => [
    'driver' => \NotificationService\Drivers\CustomDriver::class,
    'config' => [
        // Your settings
    ],
],
```

3. Done! Now you can use the `custom` channel.

### Queue Structure

Tasks in the queue have the following structure:

- `id` — unique task identifier
- `payload` — task data (JSON)
- `status` — status (pending, processing, completed, failed)
- `attempts` — number of attempts
- `created_at` — creation time

## 📝 Logging

Logs are saved in the `storage/logs/` directory:

- `error-YYYY-MM-DD.log` — application errors
- DB logs can be viewed via Docker: `docker-compose logs db`

## 🧪 Testing

Examples of test requests via cURL:

```bash
# Send notification
curl -X POST http://localhost:8000/send \
  -H "Content-Type: application/json" \
  -d '{
    "channel": "telegram",
    "to": "123456789",
    "message": "Test notification"
      }'

# Check status
curl http://localhost:8000/status/1

# Get logs
curl http://localhost:8000/logs/1

# Health check
curl http://localhost:8000/health
```

## 🔒 Security

- All SQL queries use prepared statements
- Validation of all input data
- Exception handling at all levels
- Error logging without revealing sensitive information

## 📄 License

This project was created for educational purposes.

## 👥 Author

Muhammad Ibrahimli

## 🤝 Contribution

Suggestions and pull requests are welcome!

---

**Version:** 1.0.0  
**PHP:** 8.2+  
**Status:** Production Ready

