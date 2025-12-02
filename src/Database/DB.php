<?php

declare(strict_types=1);

namespace NotificationService\Database;

use PDO;
use PDOException;

class DB
{
    private static ?PDO $connection = null;

    public static function getConnection(): PDO
    {
        if (self::$connection !== null) {
            return self::$connection;
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            \NotificationService\Core\Env::get('DB_HOST', 'localhost'),
            \NotificationService\Core\Env::get('DB_PORT', '3306'),
            \NotificationService\Core\Env::get('DB_DATABASE', 'notification_service')
        );

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        ];

        try {
            self::$connection = new PDO(
                $dsn,
                \NotificationService\Core\Env::get('DB_USERNAME', 'root'),
                \NotificationService\Core\Env::get('DB_PASSWORD', ''),
                $options
            );
        } catch (PDOException $e) {
            throw new \RuntimeException('Database connection failed: ' . $e->getMessage());
        }

        return self::$connection;
    }

    public static function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function fetch(string $sql, array $params = []): ?array
    {
        $stmt = self::query($sql, $params);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public static function fetchAll(string $sql, array $params = []): array
    {
        $stmt = self::query($sql, $params);
        return $stmt->fetchAll();
    }

    public static function insert(string $sql, array $params = []): int
    {
        self::query($sql, $params);
        return (int)self::getConnection()->lastInsertId();
    }

    public static function execute(string $sql, array $params = []): bool
    {
        $stmt = self::query($sql, $params);
        return $stmt->rowCount() > 0;
    }

    public static function beginTransaction(): bool
    {
        return self::getConnection()->beginTransaction();
    }

    public static function commit(): bool
    {
        return self::getConnection()->commit();
    }

    public static function rollback(): bool
    {
        return self::getConnection()->rollBack();
    }

    public static function migrate(): void
    {
        $migrationFile = __DIR__ . '/migrations.sql';
        
        if (!file_exists($migrationFile)) {
            throw new \RuntimeException("Migration file not found: $migrationFile");
        }

        $sql = file_get_contents($migrationFile);
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            fn($stmt) => !empty($stmt) && strpos($stmt, '--') !== 0
        );

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
            self::rollback();
            throw new \RuntimeException('Migration failed: ' . $e->getMessage());
        }
    }
}

