<?php

namespace App\Entity;

use App\Model;
use App\Entity\User;

class UserPassword extends Model
{
    /**
     * @var array
     */
    protected $fillable = [
        'id',
        'user_id',
        'password',
        'expired',
        'status',
    ];

    /**
     * @var string
     */
    protected $table = 'user_password';

    /**
     * @return mixed
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
