<?php

namespace App\Entity;

use App\Entity\Menu;
use App\Entity\Role;
use App\Entity\User;
use Viloveul\Database\Model;

class MenuItem extends Model
{
    public function relations(): array
    {
        return [
            'author' => [
                'type' => static::HAS_ONE,
                'class' => User::class,
                'keys' => [
                    'author_id' => 'id',
                ],
            ],
            'menu' => [
                'type' => static::HAS_ONE,
                'class' => Menu::class,
                'keys' => [
                    'menu_id' => 'id',
                ],
            ],
            'role' => [
                'type' => static::HAS_ONE,
                'class' => Role::class,
                'keys' => [
                    'role_id' => 'id',
                ],
            ],
            'childs' => [
                'type' => static::HAS_MANY,
                'class' => __CLASS__,
                'keys' => [
                    'id' => 'parent_id',
                ],
            ],
        ];
    }

    public function table(): string
    {
        return '{{ menu_item }}';
    }
}
