<?php

namespace App\Controller;

use App\Component\Setting;
use App\Component\Privilege;
use Viloveul\Http\Contracts\Response;
use App\Entity\Setting as SettingModel;
use Viloveul\Router\Contracts\Dispatcher;
use Viloveul\Http\Contracts\ServerRequest;

class SettingController
{
    /**
     * @var mixed
     */
    protected $privilege;

    /**
     * @var mixed
     */
    protected $request;

    /**
     * @var mixed
     */
    protected $response;

    /**
     * @var mixed
     */
    protected $route;

    /**
     * @var mixed
     */
    protected $setting;

    /**
     * @param ServerRequest $request
     * @param Response      $response
     * @param Privilege     $privilege
     * @param Setting       $setting
     * @param Dispatcher    $router
     */
    public function __construct(
        ServerRequest $request,
        Response $response,
        Privilege $privilege,
        Setting $setting,
        Dispatcher $router
    ) {
        $this->request = $request;
        $this->response = $response;
        $this->privilege = $privilege;
        $this->setting = $setting;
        $this->route = $router->routed();
    }

    /**
     * @return mixed
     */
    public function all()
    {
        return $this->response->withPayload([
            'data' => $this->setting->all(),
        ]);
    }

    /**
     * @param  $name
     * @return mixed
     */
    public function get(string $name)
    {
        return $this->response->withPayload([
            'data' => [
                'name' => $name,
                'option' => $this->setting->get($name),
            ],
        ]);
    }

    /**
     * @param  string  $name
     * @return mixed
     */
    public function set(string $name)
    {
        if (!$this->privilege->check($this->route->getName())) {
            return $this->response->withErrors(401, [
                "No direct access for route: {$this->route->getName()}",
            ]);
        }
        $value = $this->request->getBody()->getContents();
        $model = SettingModel::getResultOrInstance(compact('name'), [
            'id' => str_uuid(),
        ]);
        $model->option = is_scalar($value) ? $value : json_encode($value);
        $model->save();
        $this->setting->clear();
        return $this->response->withPayload([
            'data' => [
                'name' => $name,
                'option' => $value,
            ],
        ]);
    }
}
