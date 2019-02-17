<?php

use Viloveul\Router\Route;

/**
 * Upload new file
 */
$router->add(
    'media.upload',
    new Route('POST /media/upload', [
        App\Controller\MediaController::class, 'upload',
    ])
);

/**
 * get files
 */
$router->add(
    'media.index',
    new Route('GET /media/index', [
        App\Controller\MediaController::class, 'index',
    ])
);

/**
 * get file
 */
$router->add(
    'media.detail',
    new Route('GET /media/detail/{:id}', [
        App\Controller\MediaController::class, 'detail',
    ])
);

/**
 * Delete file
 */
$router->add(
    'media.delete',
    new Route('DELETE /media/delete/{:id}', [
        App\Controller\MediaController::class, 'delete',
    ])
);
