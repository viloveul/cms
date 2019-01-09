<?php

use Viloveul\Auth\Contracts\Authentication;
use Viloveul\Auth\Contracts\UserData as IUserData;
use Viloveul\Auth\UserData;
use Viloveul\Http\Contracts\ServerRequest;
use Viloveul\Router\Contracts\Route;

$middleware->add(function (ServerRequest $request, $next) {
    $container = $this->getContainer();
    $route = $container->get(Route::class);
    if (!in_array($route->getName(), ['auth.login', 'auth.register'])) {
        $auth = $container->get(Authentication::class);
        $user = $auth->authenticate(new Viloveul\Auth\UserData);
        $this->getContainer()->set(IUserData::class, function () use ($user) {
            return $user;
        });
    }
    return $next($request);
});
