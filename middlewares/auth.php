<?php

use Viloveul\Auth\Contracts\Authentication;
use Viloveul\Auth\Contracts\UserData as IUserData;
use Viloveul\Auth\UserData;
use Viloveul\Config\Contracts\Configuration;
use Viloveul\Http\Contracts\ServerRequest;
use Viloveul\Router\Contracts\Route;

$middleware->add(function (ServerRequest $request, $next) {
    $container = $this->getContainer();
    $config = $container->get(Configuration::class);
    $route = $container->get(Route::class);
    $auth = $container->get(Authentication::class);
    $user = new UserData();

    [$name, $token] = sscanf($request->getServer('HTTP_AUTHORIZATION'), "%s %s");

    if (array_get($config->all(), 'auth.name') === $name && !empty($token)) {
        $auth->setToken($token);
    }

    if (!in_array($route->getName(), ['auth.login', 'auth.register', 'setting.get']) && 0 !== stripos($route->getName(), 'blog.')) {
        $user = $auth->authenticate($user);
    }

    $this->getContainer()->set(IUserData::class, function () use ($user) {
        return $user;
    });

    return $next($request);
});
