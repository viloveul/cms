<?php

use Viloveul\Router\Route;

/**
 * Create new user
 */
$router->add(
    'user.create',
    new Route('POST /user/create', [
        App\Controller\UserController::class, 'create',
    ])
);

/**
 * get user
 */
$router->add(
    'user.index',
    new Route('GET /user/index', [
        App\Controller\UserController::class, 'index',
    ])
);

/**
 * get user
 */
$router->add(
    'user.detail',
    new Route('GET /user/detail/{:id}', [
        App\Controller\UserController::class, 'detail',
    ])
);

/**
 * assign user role
 */
$router->add(
    'user.assign',
    new Route('POST /user/assign/{:id}', [
        App\Controller\UserController::class, 'assign',
    ])
);

/**
 * unassign user role
 */
$router->add(
    'user.unassign',
    new Route('POST /user/unassign/{:id}', [
        App\Controller\UserController::class, 'unassign',
    ])
);

/**
 * Update user
 */
$router->add(
    'user.update',
    new Route('POST /user/update/{:id}', [
        App\Controller\UserController::class, 'update',
    ])
);

/**
 * Delete user
 */
$router->add(
    'user.delete',
    new Route('DELETE /user/delete/{:id}', [
        App\Controller\UserController::class, 'delete',
    ])
);
