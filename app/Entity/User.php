<?php

namespace App\Entity;

use App\Entity\Role;
use Viloveul\Kernel\Model;

class User extends Model
{
    /**
     * @var array
     */
    protected $fillable = [
        'email',
        'photo',
        'password',
        'name',
        'nickname',
        'status',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * @var array
     */
    protected $hidden = ['password'];

    /**
     * @var string
     */
    protected $table = 'user';

    /**
     * @return mixed
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_role')->where('status', 1);
    }

    /**
     * @param $value
     */
    public function setStatusAttribute($value)
    {
        $this->attributes['status'] = abs($value);
    }
}
