<?php

namespace App\Entity;

use Viloveul\Kernel\Model;

class UserRole extends Model
{
    /**
     * @var array
     */
    protected $fillable = ['user_id', 'role_id', 'created_at'];

    /**
     * @var string
     */
    protected $table = 'user_role';
}
