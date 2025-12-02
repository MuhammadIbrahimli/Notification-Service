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

        $response = new Response();
        $response->status(500)->json([
            'error' => 'Internal Server Error',
            'message' => Env::get('APP_DEBUG', 'false') === 'true'
                ? $exception->getMessage()
                : 'An error occurred while processing your request.',
            'file' => Env::get('APP_DEBUG', 'false') === 'true'
                ? $exception->getFile()
                : null,
            'line' => Env::get('APP_DEBUG', 'false') === 'true'
                ? $exception->getLine()
                : null,
        ]);
    }

    public static function handleError(
        int $severity,
        string $message,
        string $file,
        int $line
    ): bool {
        if (!(error_reporting() & $severity)) {
            return false;
        }

        $exception = new Error($message, 0);
        $exception->file = $file;
        $exception->line = $line;

        self::handleException($exception);

        return true;
    }

    public static function handleShutdown(): void {
        $error = error_get_last();

        if ($error !== null && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
            $exception = new Error($error['message'], 0);
            $exception->file = $error['file'];
            $exception->line = $error['line'];

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
