<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Viloveul\Auth\Contracts\Authentication;
use Viloveul\Auth\Contracts\UserData as IUserData;
use Viloveul\Auth\UserData;
use Viloveul\Config\Contracts\Configuration;
use Viloveul\Container\ContainerAwareTrait;
use Viloveul\Container\Contracts\ContainerAware;
use Viloveul\Router\Contracts\Route;

class Auth implements MiddlewareInterface, ContainerAware
{
    use ContainerAwareTrait;

    /**
     * @param ServerRequestInterface  $request
     * @param RequestHandlerInterface $next
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $next): ResponseInterface
    {
        $container = $this->getContainer();
        $config = $container->get(Configuration::class);
        $route = $container->get(Route::class);
        $auth = $container->get(Authentication::class);
        $user = new UserData();

        [$name, $token] = sscanf($request->getServer('HTTP_AUTHORIZATION'), "%s %s");

        if (array_get($config->all(), 'auth.name') === $name && !empty($token)) {
            $auth->setToken($token);
        }

        $routeIgnores = ['auth.login', 'auth.register', 'setting.get'];

        if (!in_array($route->getName(), $routeIgnores) && 0 !== stripos($route->getName(), 'blog.')) {
            $user = $auth->authenticate($user);
        }

        $this->getContainer()->set(IUserData::class, function () use ($user) {
            return $user;
        });

        return $next->handle($request);

    }
}
