<?php

use Viloveul\Router\Route;

/**
 * create access token
 */
$router->add(
    new Route('POST /auth/login', [
        App\Controller\AuthController::class, 'login',
    ])
);

/**
 * register credentials
 */
$router->add(
    new Route('POST /auth/register', [
        App\Controller\AuthController::class, 'register',
    ])
);

/**
 * forgot password
 */
$router->add(
    new Route('POST /auth/forgot', [
        App\Controller\AuthController::class, 'forgot',
    ])
);
