<?php

namespace App\Entity;

use App\Entity\RoleChild;
use Viloveul\Database\Model;

class Role extends Model
{
    public function relations(): array
    {
        return [
            'childRelations' => [
                'type' => static::HAS_MANY,
                'class' => RoleChild::class,
                'keys' => [
                    'id' => 'role_id',
                ],
            ],
            'childs' => [
                'type' => static::HAS_MANY,
                'class' => __CLASS__,
                'through' => 'childRelations',
                'keys' => [
                    'child_id' => 'id',
                ],
            ],
        ];
    }

    public function table(): string
    {
        return '{{ role }}';
    }
}
