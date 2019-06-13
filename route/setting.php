<?php

use Viloveul\Router\Route;

/**
 * get setting
 */
$router->add(
    new Route('GET /setting', [
        App\Controller\SettingController::class, 'get',
    ])
);

/**
 * set new setting
 */
$router->add(
    new Route('POST /setting', [
        App\Controller\SettingController::class, 'set',
    ])
)->setName('setting.set');
