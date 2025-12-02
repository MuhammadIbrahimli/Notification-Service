# Notification Center

Модульный сервис для отправки уведомлений через различные каналы связи на чистом PHP 8.2+ без фреймворков.

## 📋 Описание

Notification Center — это production-ready сервис, который принимает HTTP-запросы и отправляет уведомления через разные каналы:
- 📧 **Email** — через SMTP или функцию mail()
- 📱 **SMS** — через REST API провайдеров
- 💬 **Telegram** — через Bot API
- 🔗 **Webhook** — отправка на произвольные HTTP endpoints

## ✨ Особенности

- ✅ Модульная архитектура с разделением ответственности (SRP)
- ✅ Очередь задач для асинхронной обработки
- ✅ Retry-механизм для всех драйверов (3 попытки)
- ✅ Логирование всех операций
- ✅ Расширяемая система драйверов
- ✅ Полная типизация с `declare(strict_types=1)`
- ✅ Docker-окружение для разработки и продакшена
- ✅ RESTful API

## 🏗️ Архитектура

```
Request → Router → Controller → NotificationService → DriverManager → Drivers
```

Каждый драйвер реализует интерфейс `NotificationDriverInterface` и может быть легко расширен.

## 📁 Структура проекта

```
project/
├── public/              # Точка входа
│   └── index.php
├── src/
│   ├── Controllers/     # Контроллеры
│   ├── Services/        # Бизнес-логика
│   ├── Drivers/         # Драйверы уведомлений
│   ├── Core/            # Ядро (Router, Request, Response)
│   ├── Models/          # Модели данных
│   ├── Queue/           # Система очередей
│   └── Database/        # Работа с БД
├── config/              # Конфигурационные файлы
├── storage/             # Логи и файлы
└── docker/              # Docker конфигурация
```

## 🚀 Быстрый старт

### Требования

- PHP 8.2+
- Composer
- MySQL 5.7+ или 8.0+
- Docker и Docker Compose (опционально)

### Установка

1. **Клонирование репозитория:**

```bash
git clone <repository-url>
cd notification-service
```

2. **Установка зависимостей:**

```bash
composer install
```

3. **Настройка окружения:**

Создайте файл `.env` на основе `.env.example`:

```bash
cp .env.example .env
```

Отредактируйте `.env` и укажите параметры подключения к БД и настройки драйверов.

4. **Создание базы данных:**

```bash
# Создайте БД вручную или используйте миграции
mysql -u root -p -e "CREATE DATABASE notification_service CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

5. **Выполнение миграций:**

```bash
composer migrate
```

Или вручную:

```bash
php -r "require 'vendor/autoload.php'; \NotificationService\Database\DB::migrate();"
```

6. **Запуск встроенного сервера PHP:**

```bash
php -S localhost:8000 -t public
```

7. **Запуск воркера очереди (в отдельном терминале):**

```bash
php src/Queue/Worker.php
```

Или через composer:

```bash
composer worker
```

## 🐳 Docker

### Запуск через Docker Compose

1. **Запуск всех сервисов:**

```bash
docker-compose up -d
```

2. **Выполнение миграций:**

```bash
docker-compose exec app composer migrate
```

3. **Проверка статуса:**

```bash
docker-compose ps
```

4. **Просмотр логов:**

```bash
docker-compose logs -f worker
docker-compose logs -f app
```

5. **Остановка:**

```bash
docker-compose down
```

Сервис будет доступен по адресу: `http://localhost:8080`

## 📡 API Endpoints

### 1. Отправка уведомления

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

### 2. Получение статуса уведомления

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

### 3. Получение логов уведомления

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

## 🔌 Поддерживаемые каналы

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

## ⚙️ Конфигурация

Настройки драйверов находятся в файле `config/drivers.php`. Переменные окружения настраиваются в `.env`:

- `EMAIL_SMTP_HOST` — SMTP сервер
- `EMAIL_SMTP_PORT` — SMTP порт
- `EMAIL_SMTP_USER` — SMTP пользователь
- `EMAIL_SMTP_PASS` — SMTP пароль
- `SMS_API_URL` — URL API для SMS
- `SMS_API_KEY` — API ключ для SMS
- `TELEGRAM_BOT_TOKEN` — токен Telegram бота
- `WEBHOOK_TIMEOUT` — таймаут для webhook запросов

## 🔧 Разработка

### Добавление нового драйвера

1. Создайте класс драйвера, реализующий `NotificationDriverInterface`:

```php
<?php

declare(strict_types=1);

namespace NotificationService\Drivers;

class CustomDriver implements NotificationDriverInterface
{
    public function send(array $payload): NotificationResult
    {
        // Ваша логика отправки
        return new NotificationResult(true, 'Success');
    }
}
```

2. Добавьте конфигурацию в `config/drivers.php`:

```php
'custom' => [
    'driver' => \NotificationService\Drivers\CustomDriver::class,
    'config' => [
        // Ваши настройки
    ],
],
```

3. Готово! Теперь можно использовать канал `custom`.

### Структура очереди

Задачи в очереди имеют следующую структуру:

- `id` — уникальный идентификатор задачи
- `payload` — данные задачи (JSON)
- `status` — статус (pending, processing, completed, failed)
- `attempts` — количество попыток
- `created_at` — время создания

## 📝 Логирование

Логи сохраняются в директории `storage/logs/`:

- `error-YYYY-MM-DD.log` — ошибки приложения
- Логи БД можно посмотреть через Docker: `docker-compose logs db`

## 🧪 Тестирование

Примеры тестовых запросов через cURL:

```bash
# Отправка уведомления
curl -X POST http://localhost:8000/send \
  -H "Content-Type: application/json" \
  -d '{
    "channel": "telegram",
    "to": "123456789",
    "message": "Test notification"
  }'

# Проверка статуса
curl http://localhost:8000/status/1

# Получение логов
curl http://localhost:8000/logs/1

# Health check
curl http://localhost:8000/health
```

## 🔒 Безопасность

- Все SQL-запросы используют подготовленные запросы (prepared statements)
- Валидация всех входных данных
- Обработка исключений на всех уровнях
- Логирование ошибок без раскрытия чувствительной информации

## 📄 Лицензия

Этот проект создан в учебных целях.

## 👥 Автор

Muhammad Ibrahimli

## 🤝 Вклад

Приветствуются предложения и pull requests!

---

**Версия:** 1.0.0  
**PHP:** 8.2+  
**Статус:** Production Ready

