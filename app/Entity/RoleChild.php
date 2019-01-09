<?php

namespace App\Entity;

use Viloveul\Kernel\Model;

class RoleChild extends Model
{
    /**
     * @var array
     */
    protected $fillable = ['role_id', 'child_id', 'created_at'];

    /**
     * @var string
     */
    protected $table = 'role_child';
}
