<?php

namespace App\Middleware;

use App\Component\Privilege;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Viloveul\Container\ContainerAwareTrait;
use Viloveul\Container\Contracts\ContainerAware;
use Viloveul\Router\Contracts\Dispatcher;

class Access implements MiddlewareInterface, ContainerAware
{
    use ContainerAwareTrait;

    /**
     * @param ServerRequestInterface  $request
     * @param RequestHandlerInterface $next
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $next): ResponseInterface
    {
        $container = $this->getContainer();
        $privilege = $container->get(Privilege::class);
        $route = $container->get(Dispatcher::class)->routed();

        $routeIgnores = ['auth.login', 'auth.register', 'auth.validate', 'setting.get'];
        if (!in_array($route->getName(), $routeIgnores) && 0 !== stripos($route->getName(), 'blog.')) {
            if (!$privilege->check($route->getName())) {
                return $container->get(Response::class)->withErrors(401, [
                    "No direct access for route: {$route->getName()}",
                ]);
            }
        }

        return $next->handle($request);

    }
}
