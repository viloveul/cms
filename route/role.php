<?php

use Viloveul\Router\Route;

/**
 * Create new user
 */
$router->add(
    'role.create',
    new Route('POST /role/create', [
        App\Controller\RoleController::class, 'create',
    ])
);

/**
 * get users
 */
$router->add(
    'role.index',
    new Route('GET /role/index', [
        App\Controller\RoleController::class, 'index',
    ])
);

/**
 * all users
 */
$router->add(
    'role.all',
    new Route('GET /role/all', [
        App\Controller\RoleController::class, 'all',
    ])
);

/**
 * get user
 */
$router->add(
    'role.detail',
    new Route('GET /role/detail/{:id}', [
        App\Controller\RoleController::class, 'detail',
    ])
);

/**
 * assign role child
 */
$router->add(
    'role.assign',
    new Route('POST /role/assign/{:id}', [
        App\Controller\RoleController::class, 'assign',
    ])
);

/**
 * unassign role child
 */
$router->add(
    'role.unassign',
    new Route('POST /role/unassign/{:id}', [
        App\Controller\RoleController::class, 'unassign',
    ])
);

/**
 * Update user
 */
$router->add(
    'role.update',
    new Route('POST /role/update/{:id}', [
        App\Controller\RoleController::class, 'update',
    ])
);

/**
 * Delete user
 */
$router->add(
    'role.delete',
    new Route('DELETE /role/delete/{:id}', [
        App\Controller\RoleController::class, 'delete',
    ])
);