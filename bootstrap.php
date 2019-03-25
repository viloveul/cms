<?php

error_reporting(-1);
// sleep(100);
ini_set('display_errors', 'On');

define('VILOVEUL_WORKDIR', __DIR__);

// require composer autoloader
require_once __DIR__ . '/vendor/autoload.php';

// load dot env variable
$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();

// initialize container with several components
$container = Viloveul\Container\ContainerFactory::instance([
    App\Component\Slug::class => App\Component\Slug::class,
    App\Component\Privilege::class => App\Component\Privilege::class,
    App\Component\Setting::class => App\Component\Setting::class,
    App\Component\Helper::class => App\Component\Helper::class,
    App\Component\AuditTrail::class => App\Component\AuditTrail::class,
]);

$config = Viloveul\Config\ConfigFactory::load(__DIR__ . '/config/main.php');

// initialize application object
$app = new App\Kernel($container, $config);

/**
 * Load all middlewares
 */
$app->middleware($container->make(App\Middleware\Auth::class));

/**
 * Load all routes
 */
$app->uses(function (Viloveul\Router\Contracts\Collection $router) {
    foreach (glob(__DIR__ . '/route/*.php') as $file) {
        require $file;
    }
});

/**
 * Load all routes
 */
$app->uses(function (Viloveul\Event\Contracts\Dispatcher $event) {
    foreach (glob(__DIR__ . '/hook/*.php') as $file) {
        require $file;
    }
});

return $app;
