<?php

namespace App\Entity;

use App\Entity\User;
use App\Model;

class UserProfile extends Model
{
    /**
     * @var array
     */
    protected $fillable = ['user_id', 'name', 'value', 'last_modified'];

    /**
     * @var string
     */
    protected $table = 'user_profile';

    /**
     * @return mixed
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
