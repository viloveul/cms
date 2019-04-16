<?php

use Viloveul\Router\Route;

/**
 * Create new post
 */
$router->add(
    new Route('POST /post/create', [
        App\Controller\PostController::class, 'create',
    ])
)->setName('post.create');

/**
 * get post
 */
$router->add(
    new Route('GET /post/index', [
        App\Controller\PostController::class, 'index',
    ])
)->setName('post.index');

/**
 * get post
 */
$router->add(
    new Route('GET /post/detail/{:id}', [
        App\Controller\PostController::class, 'detail',
    ])
)->setName('post.detail');

/**
 * Update post
 */
$router->add(
    new Route('POST /post/update/{:id}', [
        App\Controller\PostController::class, 'update',
    ])
)->setName('post.update');

/**
 * Delete post
 */
$router->add(
    new Route('DELETE /post/delete/{:id}', [
        App\Controller\PostController::class, 'delete',
    ])
)->setName('post.delete');
