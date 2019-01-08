<?php

namespace App\Component;

use Viloveul\Http\Contracts\ServerRequestAssignment as IServerRequestAssignment;

class RequestAssignment implements IServerRequestAssignment
{
    /**
     * @var array
     */
    private $attributes = [];

    /**
     * @return mixed
     */
    public function all()
    {
        return $this->attributes;
    }

    /**
     * @param string     $key
     * @param $default
     */
    public function getAttribute(string $key, $default = null)
    {
        return array_key_exists($key, $this->attributes) ? $this->attributes[$key] : $default;
    }

    /**
     * @param string   $key
     * @param $value
     */
    public function setAttribute(string $key, $value = null)
    {
        $this->attributes[$key] = $value;
    }
}
