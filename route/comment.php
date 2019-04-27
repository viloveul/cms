<?php

use Viloveul\Router\Route;

/**
 * get comment
 */
$router->add(
    new Route('GET /comment/index', [
        App\Controller\CommentController::class, 'index',
    ])
)->setName('comment.index');

/**
 * get comment
 */
$router->add(
    new Route('GET /comment/detail/{:id}', [
        App\Controller\CommentController::class, 'detail',
    ])
)->setName('comment.detail');

/**
 * Update comment
 */
$router->add(
    new Route('POST /comment/update/{:id}', [
        App\Controller\CommentController::class, 'update',
    ])
)->setName('comment.update');

/**
 * Create comment
 */
$router->add(
    new Route('POST /comment/create', [
        App\Controller\CommentController::class, 'create',
    ])
);

/**
 * approve comment
 */
$router->add(
    new Route('POST /comment/approve/{:id}', [
        App\Controller\CommentController::class, 'approve',
    ])
)->setName('comment.approve');

/**
 * Delete comment
 */
$router->add(
    new Route('DELETE /comment/delete/{:id}', [
        App\Controller\CommentController::class, 'delete',
    ])
)->setName('comment.delete');
