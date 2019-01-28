<?php

use Viloveul\Router\Route;

/**
 * Create new comment
 */
$router->add(
    'comment.create',
    new Route('POST /comment/create', [
        App\Controller\CommentController::class, 'create',
    ])
);

/**
 * get comment
 */
$router->add(
    'comment.index',
    new Route('GET /comment/index', [
        App\Controller\CommentController::class, 'index',
    ])
);

/**
 * get comment
 */
$router->add(
    'comment.detail',
    new Route('GET /comment/detail/{:id}', [
        App\Controller\CommentController::class, 'detail',
    ])
);

/**
 * Update comment
 */
$router->add(
    'comment.update',
    new Route('POST /comment/update/{:id}', [
        App\Controller\CommentController::class, 'update',
    ])
);

/**
 * Delete comment
 */
$router->add(
    'comment.delete',
    new Route('DELETE /comment/delete/{:id}', [
        App\Controller\CommentController::class, 'update',
    ])
);
