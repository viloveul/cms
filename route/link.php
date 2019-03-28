<?php

use Viloveul\Router\Route;

/**
 * Create new user
 */
$router->add(
    'link.create',
    new Route('POST /link/create', [
        App\Controller\LinkController::class, 'create',
    ])
);

/**
 * links by type
 */
$router->add(
    'link.index',
    new Route('GET /link/index', [
        App\Controller\LinkController::class, 'index',
    ])
);

/**
 * get user
 */
$router->add(
    'link.detail',
    new Route('GET /link/detail/{:id}', [
        App\Controller\LinkController::class, 'detail',
    ])
);

/**
 * Update user
 */
$router->add(
    'link.update',
    new Route('POST /link/update/{:id}', [
        App\Controller\LinkController::class, 'update',
    ])
);

/**
 * Delete user
 */
$router->add(
    'link.delete',
    new Route('DELETE /link/delete/{:id}', [
        App\Controller\LinkController::class, 'delete',
    ])
);
