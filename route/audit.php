<?php

use Viloveul\Router\Route;

/**
 * get files
 */
$router->add(
    new Route('GET /audit/index', [
        App\Controller\AuditController::class, 'index',
    ])
)->setName('audit.index');

/**
 * get file
 */
$router->add(
    new Route('GET /audit/detail/{:id}', [
        App\Controller\AuditController::class, 'detail',
    ])
)->setName('audit.detail');
