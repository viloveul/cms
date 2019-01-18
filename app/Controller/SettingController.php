<?php

namespace App\Controller;

use App\Component\Setting as SettingComponent;
use App\Entity\Setting;
use Viloveul\Http\Contracts\Response;
use Viloveul\Http\Contracts\ServerRequest;

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
     * @param Response         $response
     * @param SettingComponent $options
     */
    public function __construct(Response $response, SettingComponent $options)
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
            $name => $this->options->get($name),
        ]);
    }

    /**
     * @param  ServerRequest $request
     * @param  $name
     * @return mixed
     */
    public function set(ServerRequest $request, string $name)
    {
        $value = $request->getBody()->getContents();
        $option = is_scalar($value) ? $value : json_encode($value);
        Setting::updateOrCreate(compact('name'), compact('option'));
        $this->options->clear();
        return $this->response->withPayload([
            $name => $value,
        ]);
    }
}
