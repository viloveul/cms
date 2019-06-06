<?php

use Viloveul\Router\Route;

/**
 * get index audit
 */
$router->add(
    new Route('GET /audit/index', [
        App\Controller\AuditController::class, 'index',
    ])
)->setName('audit.index');

/**
 * get detail audit
 */
$router->add(
    new Route('GET /audit/detail/{:id}', [
        App\Controller\AuditController::class, 'detail',
    ])
)->setName('audit.detail');
