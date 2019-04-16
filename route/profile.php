<?php

use Viloveul\Router\Route;

/**
 * profile
 */
$router->add(
    new Route('GET /profile/detail/{:id}', [
        App\Controller\ProfileController::class, 'detail',
    ])
)->setName('profile.detail');

$router->add(
    new Route('POST /profile/update/{:id}', [
        App\Controller\ProfileController::class, 'update',
    ])
)->setName('profile.update');
