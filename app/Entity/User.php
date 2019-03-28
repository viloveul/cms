<?php

namespace App\Entity;

use App\Model;
use App\Entity\Role;
use App\Entity\UserProfile;
use App\Entity\UserPassword;

class User extends Model
{
    /**
     * @var array
     */
    protected $fillable = [
        'id',
        'name',
        'picture',
        'email',
        'username',
        'password',
        'status',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * @var array
     */
    protected $hidden = ['password', 'passwords'];

    /**
     * @var string
     */
    protected $table = 'user';

    /**
     * @return mixed
     */
    public function passwords()
    {
        return $this->hasMany(UserPassword::class);
    }

    /**
     * @return mixed
     */
    public function profile()
    {
        return $this->hasMany(UserProfile::class);
    }

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
