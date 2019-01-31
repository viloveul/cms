<?php

use Viloveul\Router\Route;

/**
 * Create new media
 */
$router->add(
    'media.upload',
    new Route('POST /media/upload', [
        App\Controller\MediaController::class, 'upload',
    ])
);

/**
 * get media
 */
$router->add(
    'media.index',
    new Route('GET /media/index', [
        App\Controller\MediaController::class, 'index',
    ])
);

/**
 * Delete media
 */
$router->add(
    'media.delete',
    new Route('DELETE /media/delete/{:id}', [
        App\Controller\MediaController::class, 'delete',
    ])
);
