<?php

use Viloveul\Router\Route;

/**
 * get notification
 */
$router->add(
    'notification.index',
    new Route('GET /notification/index', [
        App\Controller\NotificationController::class, 'index',
    ])
);

/**
 * get notification
 */
$router->add(
    'notification.detail',
    new Route('GET /notification/detail/{:id}', [
        App\Controller\NotificationController::class, 'detail',
    ])
);

/**
 * count notification
 */
$router->add(
    'notification.count',
    new Route('GET /notification/count', [
        App\Controller\NotificationController::class, 'count',
    ])
);
