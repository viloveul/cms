<?php

namespace App\Entity;

use Viloveul\Kernel\Model;

class Role extends Model
{
    /**
     * @var array
     */
    protected $fillable = ['name', 'type', 'created_at'];

    /**
     * @var string
     */
    protected $table = 'role';
}
