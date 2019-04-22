<?php

namespace App\Entity;

use App\Entity\User;
use Viloveul\Database\Model;

class UserPassword extends Model
{
    public function relations(): array
    {
        return [
            'uset' => [
                'type' => static::HAS_ONE,
                'class' => User::class,
                'keys' => [
                    'uset_id' => 'id',
                ],
            ],
        ];
    }

    public function table(): string
    {
        return '{{ user_password }}';
    }
}
