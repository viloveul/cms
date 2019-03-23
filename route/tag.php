<?php

use Viloveul\Router\Route;

/**
 * Create new user
 */
$router->add(
    'tag.create',
    new Route('POST /tag/create', [
        App\Controller\TagController::class, 'create',
    ])
);

/**
 * get users
 */
$router->add(
    'tag.index',
    new Route('GET /tag/index', [
        App\Controller\TagController::class, 'index',
    ])
);

/**
 * get all tags
 */
$router->add(
    'tag.all',
    new Route('GET /tag/all', [
        App\Controller\TagController::class, 'all',
    ])
);

/**
 * get user
 */
$router->add(
    'tag.detail',
    new Route('GET /tag/detail/{:id}', [
        App\Controller\TagController::class, 'detail',
    ])
);

/**
 * Update user
 */
$router->add(
    'tag.update',
    new Route('POST /tag/update/{:id}', [
        App\Controller\TagController::class, 'update',
    ])
);

/**
 * Delete user
 */
$router->add(
    'tag.delete',
    new Route('DELETE /tag/delete/{:id}', [
        App\Controller\TagController::class, 'delete',
    ])
);
