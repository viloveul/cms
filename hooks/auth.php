<?php

use Viloveul\Auth\Contracts\Authentication;
use Viloveul\Http\Contracts\ServerRequest;
use Viloveul\Http\Contracts\Response;
use Viloveul\Router\Contracts\Route;

$middleware->add(function (ServerRequest $request, $next) {
    // $route = $this->getContainer()->get(Route::class);
    // if (!in_array($route->getName(), ['auth.login', 'auth.register'])) {
    //     $auth = $this->getContainer()->get(Authentication::class);
    //     $auth->authenticate();
    // }
    return $next($request);
});
