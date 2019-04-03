<?php

namespace App\Middleware;

use Exception;
use Viloveul\Http\Contracts\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Viloveul\Auth\InvalidTokenException;
use Viloveul\Router\Contracts\Dispatcher;
use Viloveul\Auth\Contracts\Authentication;
use Viloveul\Container\ContainerAwareTrait;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Viloveul\Config\Contracts\Configuration;
use Viloveul\Container\Contracts\ContainerAware;

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

        if ($config->get('auth.name') === $name && !empty($token) && !in_array($token, ['null', 'undefined'])) {
            $auth->setToken($token);
        }

        $ignores = ['auth.login', 'auth.register', 'auth.forgot'];

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
