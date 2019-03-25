<?php

namespace App\Entity;

use App\Model;

class RoleChild extends Model
{
    /**
     * @var array
     */
    protected $fillable = [
        'role_id',
        'child_id',
    ];

    /**
     * @var string
     */
    protected $table = 'role_child';
}
