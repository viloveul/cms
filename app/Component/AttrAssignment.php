<?php

namespace App\Component;

use Viloveul\Http\Contracts\ServerRequestAssignment as IServerRequestAssignment;
use Viloveul\Support\AttrAwareTrait;

class AttrAssignment implements IServerRequestAssignment
{
    use AttrAwareTrait;
}
