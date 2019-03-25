<?php

namespace App\Entity;

use App\Entity\User;
use App\Model;

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
