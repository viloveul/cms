<?php

use Viloveul\Router\Route;

/**
 * get widget
 */
$router->add(
    new Route('GET /widget/load/{:type}', [
        App\Controller\WidgetController::class, 'load',
    ])
);

/**
 * list availables
 */
$router->add(
    new Route('GET /widget/availables', [
        App\Controller\WidgetController::class, 'availables',
    ])
);
