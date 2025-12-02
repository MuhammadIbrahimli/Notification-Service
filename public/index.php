<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

use NotificationService\Core\Env;
use NotificationService\Core\ErrorHandler;
use NotificationService\Core\Router;
use NotificationService\Core\Request;
use NotificationService\Controllers\NotificationController;

// Загрузка переменных окружения
Env::load();

// Регистрация обработчика ошибок
ErrorHandler::register();

// Создание роутера
$router = new Router();
$controller = new NotificationController();

// Определение маршрутов
$router->post('/send', [$controller, 'send']);
$router->get('/status/{id}', [$controller, 'status']);
$router->get('/logs/{id}', [$controller, 'logs']);
$router->get('/health', [$controller, 'health']);

// Обработка запроса
$request = new Request();
$router->dispatch($request);
