<?php

declare(strict_types=1);

namespace NotificationService\Services;

use NotificationService\Drivers\NotificationDriverInterface;
use NotificationService\Drivers\EmailDriver;
use NotificationService\Drivers\SmsDriver;
use NotificationService\Drivers\TelegramDriver;
use NotificationService\Drivers\WebhookDriver;

class DriverManager
{
    private array $drivers = [];
    private array $config = [];

    public function __construct()
    {
        $configPath = dirname(__DIR__, 2) . '/config/drivers.php';
        
        if (file_exists($configPath)) {
            $this->config = require $configPath;
        }
    }

    public function getDriver(string $channel): NotificationDriverInterface
    {
        if (isset($this->drivers[$channel])) {
            return $this->drivers[$channel];
        }

        if (!isset($this->config[$channel])) {
            throw new \RuntimeException("Driver for channel '$channel' not found");
        }

        $driverConfig = $this->config[$channel];
        $driverClass = $driverConfig['driver'] ?? null;
        $config = $driverConfig['config'] ?? [];

        if (!$driverClass || !class_exists($driverClass)) {
            throw new \RuntimeException("Driver class '$driverClass' not found for channel '$channel'");
        }

        if (!is_subclass_of($driverClass, NotificationDriverInterface::class)) {
            throw new \RuntimeException("Driver class '$driverClass' must implement NotificationDriverInterface");
        }

        $this->drivers[$channel] = new $driverClass($config);

        return $this->drivers[$channel];
    }

    public function hasDriver(string $channel): bool
    {
        return isset($this->config[$channel]);
    }

    public function getAvailableChannels(): array
    {
        return array_keys($this->config);
    }
}

