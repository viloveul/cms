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
     * @return mixed
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_role')->where('deleted', 0)->where('status', 1);
    }
}
