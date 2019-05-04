<?php

namespace App\Middleware;

use Exception;
use Viloveul\Log\Contracts\Logger;
use Viloveul\Http\Contracts\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Viloveul\Auth\InvalidTokenException;
use Viloveul\Router\Contracts\Dispatcher;
use Viloveul\Auth\Contracts\Authentication;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Viloveul\Config\Contracts\Configuration;

class Auth implements MiddlewareInterface
{
    /**
     * @var mixed
     */
    protected $auth;

    /**
     * @var mixed
     */
    protected $config;

    /**
     * @var mixed
     */
    protected $log;

    /**
     * @var mixed
     */
    protected $response;

    /**
     * @var mixed
     */
    protected $route;

    /**
     * @param Configuration  $config
     * @param Authentication $auth
     * @param Response       $response
     * @param Logger         $log
     * @param Dispatcher     $router
     */
    public function __construct(
        Configuration $config,
        Authentication $auth,
        Response $response,
        Logger $log,
        Dispatcher $router
    ) {
        $this->config = $config;
        $this->auth = $auth;
        $this->response = $response;
        $this->log = $log;
        $this->route = $router->routed();
    }

    /**
     * @param ServerRequestInterface  $request
     * @param RequestHandlerInterface $next
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $next): ResponseInterface
    {
        [$name, $token] = sscanf($request->getServer('HTTP_AUTHORIZATION'), "%s %s");
        if ($this->config->get('auth.name') === $name && !empty($token) && !in_array($token, ['null', 'undefined'])) {
            $this->auth->setToken($token);
        }

        try {
            $this->auth->authenticate();
        } catch (Exception $e) {
            $named = $this->route->getName();
            if (strlen($named) !== 0) {
                $this->log->handleException($e);
                if ($e instanceof InvalidTokenException) {
                    return $this->response->withErrors(401, [
                        'Token Invalid',
                    ]);
                }
                return $this->response->withErrors(401, [
                    $e->getMessage(),
                ]);
            }
        }

        return $next->handle($request);
    }
}
