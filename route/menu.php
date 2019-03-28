<?php

use Viloveul\Router\Route;

/**
 * Create new user
 */
$router->add(
    'menu.create',
    new Route('POST /menu/create', [
        App\Controller\MenuController::class, 'create',
    ])
);

/**
 * menus by type
 */
$router->add(
    'menu.index',
    new Route('GET /menu/index', [
        App\Controller\MenuController::class, 'index',
    ])
);

/**
 * get user
 */
$router->add(
    'menu.detail',
    new Route('GET /menu/detail/{:id}', [
        App\Controller\MenuController::class, 'detail',
    ])
);

/**
 * Update user
 */
$router->add(
    'menu.update',
    new Route('POST /menu/update/{:id}', [
        App\Controller\MenuController::class, 'update',
    ])
);

/**
 * Delete user
 */
$router->add(
    'menu.delete',
    new Route('DELETE /menu/delete/{:id}', [
        App\Controller\MenuController::class, 'delete',
    ])
);
