<?php

use Viloveul\Router\Route;

/**
 * get setting
 */
$router->add(
    'setting.get',
    new Route('GET /setting/{:name}', [
        App\Controller\SettingController::class, 'get',
    ])
);

/**
 * set new setting
 */
$router->add(
    'setting.set',
    new Route('POST /setting/{:name}', [
        App\Controller\SettingController::class, 'set',
    ])
);
