# 🚀 Быстрый старт

## Локальная установка

### 1. Установка зависимостей

```bash
composer install
```

### 2. Настройка окружения

Скопируйте `ENV_EXAMPLE.txt` в `.env` и заполните необходимые параметры:

```bash
# Windows
copy ENV_EXAMPLE.txt .env

# Linux/Mac
cp ENV_EXAMPLE.txt .env
```

Минимальные настройки для старта:

```env
DB_HOST=localhost
DB_DATABASE=notification_service
DB_USERNAME=root
DB_PASSWORD=your_password
```

### 3. Создание базы данных

```sql
CREATE DATABASE notification_service CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 4. Запуск миграций

```bash
composer migrate
```

### 5. Запуск сервера

```bash
php -S localhost:8000 -t public
```

### 6. Запуск воркера (в отдельном терминале)

```bash
php src/Queue/Worker.php
```

## Docker установка

### 1. Запуск всех сервисов

```bash
docker-compose up -d
```

### 2. Настройка .env для Docker

```env
DB_HOST=db
DB_USERNAME=notification_user
DB_PASSWORD=notification_password
```

### 3. Выполнение миграций

```bash
docker-compose exec app composer migrate
```

Сервис будет доступен по адресу: **http://localhost:8080**

## Тестирование

### Проверка health check

```bash
curl http://localhost:8000/health
```

### Отправка тестового уведомления

```bash
curl -X POST http://localhost:8000/send \
  -H "Content-Type: application/json" \
  -d '{
    "channel": "telegram",
    "to": "123456789",
    "message": "Test notification"
  }'
```

### Проверка статуса

```bash
curl http://localhost:8000/status/1
```

## Настройка Telegram бота

1. Создайте бота через [@BotFather](https://t.me/BotFather)
2. Получите токен
3. Добавьте в `.env`:

```env
TELEGRAM_BOT_TOKEN=your_bot_token_here
```

4. Получите свой chat_id (отправьте боту сообщение и проверьте через API)

## Настройка Email

Для отправки email через SMTP добавьте в `.env`:

```env
EMAIL_SMTP_HOST=smtp.gmail.com
EMAIL_SMTP_PORT=587
EMAIL_SMTP_USER=your_email@gmail.com
EMAIL_SMTP_PASS=your_password
EMAIL_FROM=your_email@gmail.com
EMAIL_FROM_NAME=Notification Center
```

## Настройка SMS

Для отправки SMS добавьте в `.env`:

```env
SMS_API_URL=https://api.sms-provider.com/send
SMS_API_KEY=your_api_key
SMS_SENDER=YourCompany
```

## Структура запроса

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

## Следующие шаги

- Прочитайте полную документацию в [README.md](README.md)
- Настройте все необходимые драйверы
- Добавьте свой кастомный драйвер (см. README.md)
- Настройте мониторинг и логирование

