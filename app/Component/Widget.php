<?php

namespace App\Component;

use Viloveul\Http\Contracts\ServerRequest;

abstract class Widget
{
    /**
     * @var array
     */
    protected $options = [];

    /**
     * @var mixed
     */
    protected $request;

    /**
     * @param ServerRequest $request
     */
    public function __construct(ServerRequest $request)
    {
        $this->request = $request;
    }

    /**
     * @return mixed
     */
    public function getOptions()
    {
        return $this->options;
    }

    abstract public function results(): array;

    /**
     * @param array $options
     */
    public function setOptions(array $options = [])
    {
        foreach ($this->options as $key => $value) {
            if (array_key_exists($key, $options)) {
                $this->options[$key] = $options[$key];
            }
        }
    }
}
