<?php

namespace App\Controller;

use App\Component\Privilege;
use App\Component\Setting;
use App\Entity\Setting as SettingModel;
use Viloveul\Http\Contracts\Response;
use Viloveul\Http\Contracts\ServerRequest;
use Viloveul\Router\Contracts\Dispatcher;

class SettingController
{
    /**
     * @var mixed
     */
    protected $options;

    /**
     * @var mixed
     */
    protected $response;

    /**
     * @param Response $response
     * @param Setting  $options
     */
    public function __construct(Response $response, Setting $options)
    {
        $this->response = $response;
        $this->options = $options;
    }

    /**
     * @param  $name
     * @return mixed
     */
    public function get(string $name)
    {
        return $this->response->withPayload([
            'data' => $this->options->get($name),
        ]);
    }

    /**
     * @param  string        $name
     * @param  ServerRequest $request
     * @param  Privilege     $privilege
     * @param  Dispatcher    $router
     * @return mixed
     */
    public function set(string $name, ServerRequest $request, Privilege $privilege, Dispatcher $router)
    {
        $route = $router->routed();
        if (!$privilege->check($route->getName())) {
            return $this->response->withErrors(401, ["No direct access for route: {$route->getName()}"]);
        }
        $value = $request->getBody()->getContents();
        $option = is_scalar($value) ? $value : json_encode($value);
        SettingModel::updateOrCreate(compact('name'), compact('option'));
        $this->options->clear();
        return $this->response->withPayload([
            'data' => $value,
        ]);
    }
}
