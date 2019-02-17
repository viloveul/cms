<?php

use Viloveul\Router\Route;

/**
 * profile
 */
$router->add(
    'profile.detail',
    new Route('GET /profile/{:id}', [
        App\Controller\ProfileController::class, 'detail',
    ])
);

$router->add(
    'profile.update',
    new Route('POST /profile/{:id}', [
        App\Controller\ProfileController::class, 'update',
    ])
);
