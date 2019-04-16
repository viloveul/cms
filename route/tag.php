<?php

use Viloveul\Router\Route;

/**
 * Create new user
 */
$router->add(
    new Route('POST /tag/create', [
        App\Controller\TagController::class, 'create',
    ])
)->setName('tag.create');

/**
 * get users
 */
$router->add(
    new Route('GET /tag/index', [
        App\Controller\TagController::class, 'index',
    ])
)->setName('tag.index');

/**
 * get user
 */
$router->add(
    new Route('GET /tag/detail/{:id}', [
        App\Controller\TagController::class, 'detail',
    ])
)->setName('tag.detail');

/**
 * Update user
 */
$router->add(
    new Route('POST /tag/update/{:id}', [
        App\Controller\TagController::class, 'update',
    ])
)->setName('tag.update');

/**
 * Delete user
 */
$router->add(
    new Route('DELETE /tag/delete/{:id}', [
        App\Controller\TagController::class, 'delete',
    ])
)->setName('tag.delete');
