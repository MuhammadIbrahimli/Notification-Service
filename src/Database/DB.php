<?php

declare(strict_types=1);

namespace NotificationService\Database;

use PDO;
use PDOException;

class DB {
    private static ?PDO $connection = null;

    public static function getConnection(): PDO {
        if (self::$connection !== null) {
            return self::$connection;
        }

        // Загружаем переменные окружения
        \NotificationService\Core\Env::load();

        $host = \NotificationService\Core\Env::get('DB_HOST', 'localhost');
        $port = \NotificationService\Core\Env::get('DB_PORT', '3306');
        $database = \NotificationService\Core\Env::get('DB_DATABASE', 'notification_service');
        $username = \NotificationService\Core\Env::get('DB_USERNAME', 'root');
        $password = \NotificationService\Core\Env::get('DB_PASSWORD', '');

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $host,
            $port,
            $database
        );

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        ];

        try {
            self::$connection = new PDO($dsn, $username, $password, $options);
        } catch (PDOException $e) {
            $envFile = dirname(__DIR__, 2) . '/.env';
            $envExists = file_exists($envFile) ? 'существует' : 'не найден';

            $errorMsg = "Database connection failed!\n";
            $errorMsg .= "Error: " . $e->getMessage() . "\n\n";
            $errorMsg .= "Configuration:\n";
            $errorMsg .= "  Host: $host\n";
            $errorMsg .= "  Port: $port\n";
            $errorMsg .= "  Database: $database\n";
            $errorMsg .= "  Username: $username\n";
            $errorMsg .= "  .env file: $envExists\n\n";
            $errorMsg .= "Troubleshooting:\n";
            $errorMsg .= "  1. Check if MySQL server is running in OSPanel\n";
            $errorMsg .= "  2. Verify .env file exists and has correct settings\n";
            $errorMsg .= "  3. Check DB_HOST, DB_PORT, DB_USERNAME, DB_PASSWORD in .env\n";
            $errorMsg .= "  4. Make sure database '$database' exists\n";

            throw new \RuntimeException($errorMsg);
        }

        return self::$connection;
    }

    public static function query(string $sql, array $params = []): \PDOStatement {
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function fetch(string $sql, array $params = []): ?array {
        $stmt = self::query($sql, $params);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public static function fetchAll(string $sql, array $params = []): array {
        $stmt = self::query($sql, $params);
        return $stmt->fetchAll();
    }

    public static function insert(string $sql, array $params = []): int {
        self::query($sql, $params);
        return (int)self::getConnection()->lastInsertId();
    }

    public static function execute(string $sql, array $params = []): bool {
        $stmt = self::query($sql, $params);
        return $stmt->rowCount() > 0;
    }

    public static function beginTransaction(): bool {
        return self::getConnection()->beginTransaction();
    }

    public static function commit(): bool {
        return self::getConnection()->commit();
    }

    public static function rollback(): bool {
        return self::getConnection()->rollBack();
    }

    public static function migrate(): void {
        // Загружаем переменные окружения перед миграцией
        \NotificationService\Core\Env::load();

        $migrationFile = __DIR__ . '/migrations.sql';

        if (!file_exists($migrationFile)) {
            throw new \RuntimeException("Migration file not found: $migrationFile");
        }

        $sql = file_get_contents($migrationFile);

        // Улучшенный парсинг SQL: обрабатываем многострочные запросы
        $lines = explode("\n", $sql);
        $statements = [];
        $currentStatement = '';

        foreach ($lines as $line) {
            $line = trim($line);

            // Пропускаем пустые строки и комментарии
            if (empty($line) || strpos($line, '--') === 0) {
                continue;
            }

            $currentStatement .= $line . "\n";

            // Если строка заканчивается на ;, то это конец запроса
            if (substr(rtrim($line), -1) === ';') {
                $statement = trim($currentStatement);
                if (!empty($statement)) {
                    $statements[] = $statement;
                }
                $currentStatement = '';
            }
        }

        try {
            self::beginTransaction();

            foreach ($statements as $statement) {
                if (!empty(trim($statement))) {
                    self::getConnection()->exec($statement);
                }
            }

            self::commit();
            echo "Migration completed successfully!\n";
        } catch (PDOException $e) {
            if (self::$connection !== null) {
                try {
                    self::rollback();
                } catch (\Exception $rollbackError) {
                    // Ignore rollback errors
                }
            }
            throw new \RuntimeException('Migration failed: ' . $e->getMessage());
        }
    }
}
