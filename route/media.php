<?php

use Viloveul\Router\Route;

/**
 * Upload new file
 */
$router->add(
    new Route('POST /media/upload', [
        App\Controller\MediaController::class, 'upload',
    ])
)->setName('media.upload');

/**
 * get files
 */
$router->add(
    new Route('GET /media/index', [
        App\Controller\MediaController::class, 'index',
    ])
)->setName('media.index');

/**
 * get file
 */
$router->add(
    new Route('GET /media/detail/{:id}', [
        App\Controller\MediaController::class, 'detail',
    ])
)->setName('media.detail');

/**
 * Delete file
 */
$router->add(
    new Route('DELETE /media/delete/{:id}', [
        App\Controller\MediaController::class, 'delete',
    ])
)->setName('media.delete');
