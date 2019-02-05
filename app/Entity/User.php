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
        'deleted',
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
        return $this->belongsToMany(Role::class, 'user_role')->where('deleted', 0)->where('status', 1);
    }

    /**
     * @param $value
     */
    public function setDeletedAttribute($value)
    {
        $this->attributes['deleted'] = abs($value);
    }

    /**
     * @param $value
     */
    public function setStatusAttribute($value)
    {
        $this->attributes['status'] = abs($value);
    }
}
