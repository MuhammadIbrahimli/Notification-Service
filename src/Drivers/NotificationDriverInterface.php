<?php

declare(strict_types=1);

namespace NotificationService\Drivers;

interface NotificationDriverInterface
{
    public function send(array $payload): NotificationResult;
}

