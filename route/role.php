<?php

use Viloveul\Router\Route;

/**
 * Create new user
 */
$router->add(
    new Route('POST /role/create', [
        App\Controller\RoleController::class, 'create',
    ])
)->setName('role.create');

/**
 * get users
 */
$router->add(
    new Route('GET /role/index', [
        App\Controller\RoleController::class, 'index',
    ])
)->setName('role.index');

/**
 * get user
 */
$router->add(
    new Route('GET /role/detail/{:id}', [
        App\Controller\RoleController::class, 'detail',
    ])
)->setName('role.detail');

/**
 * assign role child
 */
$router->add(
    new Route('POST /role/assign/{:id}', [
        App\Controller\RoleController::class, 'assign',
    ])
)->setName('role.assign');

/**
 * unassign role child
 */
$router->add(
    new Route('POST /role/unassign/{:id}', [
        App\Controller\RoleController::class, 'unassign',
    ])
)->setName('role.unassign');

/**
 * Update user
 */
$router->add(
    new Route('POST /role/update/{:id}', [
        App\Controller\RoleController::class, 'update',
    ])
)->setName('role.update');

/**
 * Delete user
 */
$router->add(
    new Route('DELETE /role/delete/{:id}', [
        App\Controller\RoleController::class, 'delete',
    ])
)->setName('role.delete');
