# 🚀 Quick Start

## Local Installation

### 1. Install Dependencies

```bash
composer install
```

### 2. Environment Setup

Copy `ENV_EXAMPLE.txt` to `.env` and fill in the necessary parameters:

```bash
# Windows
copy ENV_EXAMPLE.txt .env

# Linux/Mac
cp ENV_EXAMPLE.txt .env
```

Minimum settings for start:

```env
DB_HOST=localhost
DB_DATABASE=notification_service
DB_USERNAME=root
DB_PASSWORD=your_password
```

### 3. Create Database

```sql
CREATE DATABASE notification_service CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 4. Run Migrations

```bash
composer migrate
```

### 5. Start Server

```bash
php -S localhost:8000 -t public
```

### 6. Start Worker (in a separate terminal)

```bash
php src/Queue/Worker.php
```

## Docker Installation

### 1. Start All Services

```bash
docker-compose up -d
```

### 2. Configure .env for Docker

```env
DB_HOST=db
DB_USERNAME=notification_user
DB_PASSWORD=notification_password
```

### 3. Run Migrations

```bash
docker-compose exec apache composer migrate
```

The service will be available at: **http://localhost:8080**

## Testing

### Health Check

```bash
curl http://localhost:8000/health
```

### Send Test Notification

```bash
curl -X POST http://localhost:8000/send \
  -H "Content-Type: application/json" \
  -d '{
    "channel": "telegram",
    "to": "123456789",
    "message": "Test notification"
  }'
```

### Check Status

```bash
curl http://localhost:8000/status/1
```

## Telegram Bot Setup

1. Create a bot via [@BotFather](https://t.me/BotFather)
2. Get the token
3. Add to `.env`:

```env
TELEGRAM_BOT_TOKEN=your_bot_token_here
```

4. Get your chat_id (send a message to the bot and check via API)

## Email Setup

To send email via SMTP, add to `.env`:

```env
EMAIL_SMTP_HOST=smtp.gmail.com
EMAIL_SMTP_PORT=587
EMAIL_SMTP_USER=your_email@gmail.com
EMAIL_SMTP_PASS=your_password
EMAIL_FROM=your_email@gmail.com
EMAIL_FROM_NAME=Notification Center
```

## SMS Setup

To send SMS, add to `.env`:

```env
SMS_API_URL=https://api.sms-provider.com/send
SMS_API_KEY=your_api_key
SMS_SENDER=YourCompany
```

## Request Structure

```json
{
  "channel": "email|sms|telegram|webhook",
  "to": "recipient",
  "message": "Your message",
  "subject": "Optional subject",
  "payload": {
    "additional": "data"
  }
}
```

## Next Steps

- Read the full documentation in [README.md](README.md)
- Configure all necessary drivers
- Add your custom driver (see README.md)
- Configure monitoring and logging

