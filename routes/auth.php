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
