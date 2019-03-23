<?php

use Viloveul\Router\Route;

/**
 * create access token
 */
$router->add(
    'auth.login',
    new Route('POST /auth/login', [
        App\Controller\AuthController::class, 'login',
    ])
);

/**
 * register credentials
 */
$router->add(
    'auth.register',
    new Route('POST /auth/register', [
        App\Controller\AuthController::class, 'register',
    ])
);

/**
 * forgot password
 */
$router->add(
    'auth.forgot',
    new Route('POST /auth/forgot', [
        App\Controller\AuthController::class, 'forgot',
    ])
);
