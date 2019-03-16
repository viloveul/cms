<?php

use Viloveul\Router\Route;

/**
 * Create new post
 */
$router->add(
    'post.create',
    new Route('POST /post/create', [
        App\Controller\PostController::class, 'create',
    ])
);

/**
 * get post
 */
$router->add(
    'post.index',
    new Route('GET /post/index', [
        App\Controller\PostController::class, 'index',
    ])
);

/**
 * get post
 */
$router->add(
    'post.detail',
    new Route('GET /post/detail/{:id}', [
        App\Controller\PostController::class, 'detail',
    ])
);

/**
 * Update post
 */
$router->add(
    'post.update',
    new Route('POST /post/update/{:id}', [
        App\Controller\PostController::class, 'update',
    ])
);

/**
 * Delete post
 */
$router->add(
    'post.delete',
    new Route('DELETE /post/delete/{:id}', [
        App\Controller\PostController::class, 'delete',
    ])
);
