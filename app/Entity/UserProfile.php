<?php

namespace App\Entity;

use App\Entity\User;
use Viloveul\Database\Model;

class UserProfile extends Model
{
    public function relations(): array
    {
        return [
            'user' => [
                'type' => static::HAS_ONE,
                'class' => User::class,
                'keys' => [
                    'user_id' => 'id',
                ],
            ],
        ];
    }

    public function table(): string
    {
        return '{{ user_profile }}';
    }
}
