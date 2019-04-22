<?php

namespace App\Entity;

use App\Entity\Role;
use App\Entity\UserRole;
use App\Entity\UserProfile;
use App\Entity\UserPassword;
use Viloveul\Database\Model;

class User extends Model
{
    /**
     * @var array
     */
    protected $protects = ['password', 'passwords'];

    public function relations(): array
    {
        return [
            'roleRelations' => [
                'type' => static::HAS_MANY,
                'class' => UserRole::class,
                'keys' => [
                    'id' => 'user_id',
                ],
            ],
            'roles' => [
                'type' => static::HAS_MANY,
                'class' => Role::class,
                'through' => 'roleRelations',
                'keys' => [
                    'role_id' => 'id',
                ],
            ],
            'profile' => [
                'type' => static::HAS_MANY,
                'class' => UserProfile::class,
                'keys' => [
                    'id' => 'user_id',
                ],
            ],
            'passwords' => [
                'type' => static::HAS_MANY,
                'class' => UserPassword::class,
                'keys' => [
                    'id' => 'user_id',
                ],
            ],
        ];
    }

    public function table(): string
    {
        return '{{ user }}';
    }
}
