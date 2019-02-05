<?php

namespace App\Entity;

use App\Entity\User;
use Viloveul\Kernel\Model;

class Role extends Model
{
    /**
     * @var array
     */
    protected $fillable = [
        'name',
        'type',
        'status',
        'deleted',
        'created_at',
    ];

    /**
     * @var string
     */
    protected $table = 'role';

    /**
     * @return mixed
     */
    public function childs()
    {
        return $this->belongsToMany(__CLASS__, 'role_child', 'role_id', 'child_id')->where('deleted', 0)->where('status', 1);
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

    /**
     * @return mixed
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_role')->where('deleted', 0)->where('status', 1);
    }
}
