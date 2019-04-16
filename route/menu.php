<?php

use Viloveul\Router\Route;

/**
 * Create new user
 */
$router->add(
    new Route('POST /menu/create', [
        App\Controller\MenuController::class, 'create',
    ])
)->setName('menu.create');

/**
 * menus by type
 */
$router->add(
    new Route('GET /menu/index', [
        App\Controller\MenuController::class, 'index',
    ])
)->setName('menu.index');

/**
 * get user
 */
$router->add(
    new Route('GET /menu/detail/{:id}', [
        App\Controller\MenuController::class, 'detail',
    ])
)->setName('menu.detail');

/**
 * Update user
 */
$router->add(
    new Route('POST /menu/update/{:id}', [
        App\Controller\MenuController::class, 'update',
    ])
)->setName('menu.update');

/**
 * Delete user
 */
$router->add(
    new Route('DELETE /menu/delete/{:id}', [
        App\Controller\MenuController::class, 'delete',
    ])
)->setName('menu.delete');
