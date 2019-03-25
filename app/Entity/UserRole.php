<?php

namespace App\Entity;

use App\Model;

class UserRole extends Model
{
    /**
     * @var array
     */
    protected $fillable = [
        'user_id',
        'role_id',
    ];

    /**
     * @var string
     */
    protected $table = 'user_role';
}
