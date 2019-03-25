<?php

namespace App\Controller;

use App\Component\Helper;
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
    protected $helper;

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
     * @param Helper        $helper
     * @param Dispatcher    $router
     */
    public function __construct(
        ServerRequest $request,
        Response $response,
        Privilege $privilege,
        Setting $setting,
        Helper $helper,
        Dispatcher $router
    ) {
        $this->request = $request;
        $this->response = $response;
        $this->privilege = $privilege;
        $this->setting = $setting;
        $this->helper = $helper;
        $this->route = $router->routed();
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
        $model = SettingModel::firstOrNew(compact('name'), [
            'id' => $this->helper->uuid(),
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
