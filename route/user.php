<?php

use Viloveul\Router\Route;

/**
 * Create new user
 */
$router->add(
    new Route('POST /user/create', [
        App\Controller\UserController::class, 'create',
    ])
)->setName('user.create');

/**
 * get user
 */
$router->add(
    new Route('GET /user/index', [
        App\Controller\UserController::class, 'index',
    ])
)->setName('user.index');

/**
 * get user
 */
$router->add(
    new Route('GET /user/detail/{:id}', [
        App\Controller\UserController::class, 'detail',
    ])
)->setName('user.detail');

/**
 * get me
 */
$router->add(
    new Route('GET /user/me', [
        App\Controller\UserController::class, 'me',
    ])
)->setName('user.me');

/**
 * approve user
 */
$router->add(
    new Route('POST /user/approve/{:id}', [
        App\Controller\UserController::class, 'approve',
    ])
)->setName('user.approve');

/**
 * Update user
 */
$router->add(
    new Route('POST /user/update/{:id}', [
        App\Controller\UserController::class, 'update',
    ])
)->setName('user.update');

/**
 * sync user roles
 */
$router->add(
    new Route('POST /user/relations/{:id}', [
        App\Controller\UserController::class, 'relations',
    ])
)->setName('user.relations');

/**
 * Delete user
 */
$router->add(
    new Route('DELETE /user/delete/{:id}', [
        App\Controller\UserController::class, 'delete',
    ])
)->setName('user.delete');
