<?php

namespace App\Entity;

use Viloveul\Framework\Model;

class User extends Model
{
    /**
     * @var array
     */
    protected $fillable = ['username', 'password', 'email', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * @var array
     */
    protected $hidden = ['password'];

    /**
     * @var string
     */
    protected $table = 'user';
}
