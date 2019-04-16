<?php

use Viloveul\Router\Route;

$router->add(
    new Route('GET /blog/detail/{:slug}', [
        App\Controller\BlogController::class, 'detail',
    ])
);

$router->add(
    new Route('GET /blog/index', [
        App\Controller\BlogController::class, 'index',
    ])
);

$router->add(
    new Route('GET /blog/author/{:name}', [
        App\Controller\BlogController::class, 'author',
    ])
);

$router->add(
    new Route('GET /blog/archive/{:slug}', [
        App\Controller\BlogController::class, 'archive',
    ])
);

$router->add(
    new Route('GET /blog/comments/{:post_id}', [
        App\Controller\BlogController::class, 'comments',
    ])
);
