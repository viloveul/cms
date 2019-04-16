<?php

use Viloveul\Router\Route;

/**
 * Create new user
 */
$router->add(
    new Route('POST /link/create', [
        App\Controller\LinkController::class, 'create',
    ])
)->setName('link.create');

/**
 * links by type
 */
$router->add(
    new Route('GET /link/index', [
        App\Controller\LinkController::class, 'index',
    ])
)->setName('link.index');

/**
 * get user
 */
$router->add(
    new Route('GET /link/detail/{:id}', [
        App\Controller\LinkController::class, 'detail',
    ])
)->setName('link.detail');

/**
 * Update user
 */
$router->add(
    new Route('POST /link/update/{:id}', [
        App\Controller\LinkController::class, 'update',
    ])
)->setName('link.update');

/**
 * Delete user
 */
$router->add(
    new Route('DELETE /link/delete/{:id}', [
        App\Controller\LinkController::class, 'delete',
    ])
)->setName('link.delete');
