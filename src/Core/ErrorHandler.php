<?php

declare(strict_types=1);

namespace NotificationService\Core;

use Error;
use Exception;
use Throwable;

class ErrorHandler {
    private static bool $registered = false;

    public static function register(): void {
        if (self::$registered) {
            return;
        }

        error_reporting(E_ALL);
        ini_set('display_errors', '0');
        ini_set('log_errors', '1');

        set_exception_handler([self::class, 'handleException']);
        set_error_handler([self::class, 'handleError']);
        register_shutdown_function([self::class, 'handleShutdown']);

        self::$registered = true;
    }

    public static function handleException(Throwable $exception): void {
        self::logError($exception);

        // Загружаем необходимые классы напрямую для надежности
        self::loadRequiredClasses();

        // Безопасное получение значения APP_DEBUG
        $isDebug = false;
        if (class_exists(\NotificationService\Core\Env::class)) {
            try {
                $isDebug = \NotificationService\Core\Env::get('APP_DEBUG', 'false') === 'true';
            } catch (\Throwable $e) {
                // Игнорируем ошибки при получении настроек
            }
        }

        // Пытаемся использовать класс Response
        if (class_exists(\NotificationService\Core\Response::class)) {
            try {
                $response = new \NotificationService\Core\Response();
                $response->status(500)->json([
                    'error' => 'Internal Server Error',
                    'message' => $isDebug
                        ? $exception->getMessage()
                        : 'An error occurred while processing your request.',
                    'file' => $isDebug
                        ? $exception->getFile()
                        : null,
                    'line' => $isDebug
                        ? $exception->getLine()
                        : null,
                ]);
                return;
            } catch (\Throwable $e) {
                // Если Response не работает, используем fallback
            }
        }

        // Fallback: если Response недоступен, выводим напрямую
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
        }

        $data = [
            'error' => 'Internal Server Error',
            'message' => $isDebug
                ? $exception->getMessage()
                : 'An error occurred while processing your request.',
        ];

        if ($isDebug) {
            $data['file'] = $exception->getFile();
            $data['line'] = $exception->getLine();
        }

        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    private static function loadRequiredClasses(): void {
        // Проверяем, загружен ли класс Response
        if (class_exists(\NotificationService\Core\Response::class)) {
            return;
        }

        $baseDir = __DIR__;

        // Пробуем загрузить автозагрузку сначала
        $autoloadFile = dirname(__DIR__, 2) . '/vendor/autoload.php';
        if (file_exists($autoloadFile)) {
            @require_once $autoloadFile;
        }

        // Если класс все еще не загружен, загружаем напрямую
        if (!class_exists(\NotificationService\Core\Response::class)) {
            $responseFile = $baseDir . '/Response.php';
            if (file_exists($responseFile)) {
                // Подавляем все ошибки при загрузке, чтобы не вызвать рекурсию
                @require_once $responseFile;
            }
        }

        // Загружаем Env.php, если нужно
        if (!class_exists(\NotificationService\Core\Env::class)) {
            $envFile = $baseDir . '/Env.php';
            if (file_exists($envFile)) {
                @require_once $envFile;
            }
        }
    }


    public static function handleError(
        int $severity,
        string $message,
        string $file,
        int $line
    ): bool {
        // Игнорируем deprecation warnings, чтобы не вызывать рекурсию
        if ($severity === E_DEPRECATED || $severity === E_USER_DEPRECATED) {
            return false;
        }

        if (!(error_reporting() & $severity)) {
            return false;
        }

        // Создаём исключение, информация о файле и строке будет в сообщении
        $exception = new Error("$message in $file on line $line", 0);
        self::handleException($exception);

        return true;
    }

    public static function handleShutdown(): void {
        $error = error_get_last();

        if ($error !== null && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
            // Создаём исключение, информация о файле и строке будет в сообщении
            $exception = new Error(
                "{$error['message']} in {$error['file']} on line {$error['line']}",
                0
            );
            self::handleException($exception);
        }
    }

    private static function logError(Throwable $exception): void {
        $logDir = dirname(__DIR__, 2) . '/storage/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $logFile = $logDir . '/error-' . date('Y-m-d') . '.log';
        $message = sprintf(
            "[%s] %s: %s in %s:%d\nStack trace:\n%s\n",
            date('Y-m-d H:i:s'),
            get_class($exception),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString()
        );

        file_put_contents($logFile, $message, FILE_APPEND);
    }
}
