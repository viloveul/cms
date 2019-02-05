<?php

use Viloveul\Router\Route;

$router->add(
    'blog.detail',
    new Route('GET /blog/detail/{:slug}', [
        App\Controller\BlogController::class, 'detail',
    ])
);

$router->add(
    'blog.index',
    new Route('GET /blog/index', [
        App\Controller\BlogController::class, 'index',
    ])
);

$router->add(
    'blog.author',
    new Route('GET /blog/author/{:name}', [
        App\Controller\BlogController::class, 'author',
    ])
);

$router->add(
    'blog.archive',
    new Route('GET /blog/archive/{:slug}', [
        App\Controller\BlogController::class, 'archive',
    ])
);

$router->add(
    'blog.comments',
    new Route('GET /blog/comments/{:post_id}', [
        App\Controller\BlogController::class, 'comments',
    ])
);

$router->add(
    'blog.comment',
    new Route('POST /blog/comment/{:post_id}', [
        App\Controller\BlogController::class, 'comment',
    ])
);
