<?php

use Viloveul\Router\Route;

/**
 * get notification
 */
$router->add(
    new Route('GET /notification/index', [
        App\Controller\NotificationController::class, 'index',
    ])
)->setName('notification.index');

/**
 * get notification
 */
$router->add(
    new Route('GET /notification/detail/{:id}', [
        App\Controller\NotificationController::class, 'detail',
    ])
)->setName('notification.detail');

/**
 * count notification
 */
$router->add(
    new Route('GET /notification/count', [
        App\Controller\NotificationController::class, 'count',
    ])
)->setName('notification.count');
