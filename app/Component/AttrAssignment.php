<?php

namespace App\Component;

use Viloveul\Support\AttrAwareTrait;
use Viloveul\Http\Contracts\ServerRequestAssignment as IServerRequestAssignment;

class AttrAssignment implements IServerRequestAssignment
{
    use AttrAwareTrait;

    /**
     * @var array
     */
    private $attributes = [];

    /**
     * @param array $attrs
     */
    public function __construct(array $attrs = [])
    {
        foreach ($attrs as $key => $value) {
            $this->set($key, $value);
        }
    }

    public function attrkey(): string
    {
        return 'attributes';
    }
}
