<?php

namespace App\Middleware;

use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Viloveul\Auth\Contracts\Authentication;
use Viloveul\Auth\InvalidTokenException;
use Viloveul\Config\Contracts\Configuration;
use Viloveul\Container\ContainerAwareTrait;
use Viloveul\Container\Contracts\ContainerAware;
use Viloveul\Http\Contracts\Response;
use Viloveul\Router\Contracts\Dispatcher;

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
        $route = $container->get(Dispatcher::class)->routed();
        $auth = $container->get(Authentication::class);

        [$name, $token] = sscanf($request->getServer('HTTP_AUTHORIZATION'), "%s %s");

        if (array_get($config->all(), 'auth.name') === $name && !empty($token) && $token !== 'null') {
            $auth->setToken($token);
        }

        $ignores = ['auth.login', 'auth.register'];

        $file = $config->get('root') . '/config/allowed.php';

        if (is_file($file)) {
            $allowed = require $file;
            $ignores = array_merge($allowed, $ignores);
        }

        try {
            $auth->authenticate();
        } catch (Exception $e) {
            if (!in_array($route->getName(), $ignores)) {
                if ($e instanceof InvalidTokenException) {
                    return $container->get(Response::class)->withErrors(401, [
                        'Token Invalid',
                    ]);
                }
                return $container->get(Response::class)->withErrors(401, [
                    $e->getMessage(),
                ]);
            }
        }

        return $next->handle($request);
    }
}
