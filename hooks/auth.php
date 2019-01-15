<?php

use Viloveul\Auth\Contracts\Authentication;
use Viloveul\Auth\Contracts\UserData as IUserData;
use Viloveul\Auth\UserData;
use Viloveul\Config\Contracts\Configuration;
use Viloveul\Http\Contracts\ServerRequest;
use Viloveul\Router\Contracts\Route;

$middleware->add(function (ServerRequest $request, $next) {
    $container = $this->getContainer();
    $configs = $container->get(Configuration::class);
    $route = $container->get(Route::class);
    if (!in_array($route->getName(), ['auth.login', 'auth.register'])) {
        $auth = $container->get(Authentication::class);
        [$name, $token] = sscanf($request->getServer('HTTP_AUTHORIZATION'), "%s %s");
        if (array_get($configs, 'auth.name') === $name && !empty($token)) {
            $auth = $auth->withToken($token);
        }
        $user = $auth->authenticate(new UserData);
        $this->getContainer()->set(IUserData::class, function () use ($user) {
            return $user;
        });
    }
    return $next($request);
});
