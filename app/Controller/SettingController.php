<?php

namespace App\Controller;

use App\Component\Setting;
use App\Component\Privilege;
use App\Component\AuditTrail;
use Viloveul\Http\Contracts\Response;
use App\Entity\Setting as SettingModel;
use Viloveul\Router\Contracts\Dispatcher;
use Viloveul\Http\Contracts\ServerRequest;

class SettingController
{
    /**
     * @var mixed
     */
    protected $audit;

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
     * @param AuditTrail    $audit
     * @param Dispatcher    $router
     */
    public function __construct(
        ServerRequest $request,
        Response $response,
        Privilege $privilege,
        Setting $setting,
        AuditTrail $audit,
        Dispatcher $router
    ) {
        $this->request = $request;
        $this->response = $response;
        $this->privilege = $privilege;
        $this->setting = $setting;
        $this->audit = $audit;
        $this->route = $router->routed();
    }

    /**
     * @return mixed
     */
    public function get()
    {
        return $this->response->withPayload([
            'data' => $this->setting->all(),
        ]);
    }

    /**
     * @return mixed
     */
    public function set()
    {
        if (!$this->privilege->check($this->route->getName())) {
            return $this->response->withErrors(401, [
                "No direct access for route: {$this->route->getName()}",
            ]);
        }
        $body = $this->request->getBody()->getContents();
        $contents = json_decode($body, true) ?: [];
        if (json_last_error() === JSON_ERROR_NONE) {
            foreach ($contents as $name => $value) {
                if ($value != $this->setting->get($name)) {
                    $model = SettingModel::where(['name' => $name])->findOrNew([
                        'id' => str_uuid(),
                        'name' => $name,
                        'created_at' => date('Y-m-d H:i:s'),
                    ]);
                    $previous = $model->getAttributes();
                    $newRecord = $model->isNewRecord();
                    $model->updated_at = date('Y-m-d H:i:s');
                    $model->option = is_scalar($value) ? $value : json_encode($value);
                    $model->save();
                    if ($newRecord) {
                        $this->audit->create($model->id, 'setting');
                    } else {
                        $this->audit->update($model->id, 'setting', $model->getAttributes(), $previous);
                    }
                }
            }
        }
        $this->setting->clear();
        return $this->response->withStatus(204);
    }
}
