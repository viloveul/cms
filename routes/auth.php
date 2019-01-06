<?php

use Viloveul\Router\Route;

/**
 * create access token
 */
$router->add('auth.login', new Route('POST /login', [App\Controller\AuthController::class, 'login']));

/**
 * register credentials
 */
$router->add('auth.register', new Route('POST /register', [App\Controller\AuthController::class, 'register']));
