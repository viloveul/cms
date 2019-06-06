<?php

use Viloveul\Router\Route;

/**
 * get me
 */
$router->add(
    new Route('GET /user/me', [
        App\Controller\UserController::class, 'me',
    ])
)->setName('user.me');

/**
 * Create new user
 */
$router->add(
    new Route('POST /user/create', [
        App\Controller\UserController::class, 'create',
    ])
)->setName('user.create');

/**
 * get users
 */
$router->add(
    new Route('GET /user/index', [
        App\Controller\UserController::class, 'index',
    ])
)->setName('user.index');

/**
 * get user detail
 */
$router->add(
    new Route('GET /user/detail/{:id}', [
        App\Controller\UserController::class, 'detail',
    ])
)->setName('user.detail');

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

/**
 * get user profile
 */
$router->add(
    new Route('GET /user/profile/{:id}', [
        App\Controller\UserProfileController::class, 'detail',
    ])
)->setName('user.profile.get');


/**
 * set user profile
 */
$router->add(
    new Route('POST /user/profile/{:id}', [
        App\Controller\UserProfileController::class, 'update',
    ])
)->setName('user.profile.set');

