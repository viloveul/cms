<?php

namespace App\Entity;

use App\Entity\User;
use Viloveul\Database\Model;

class Link extends Model
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
        ];
    }

    public function table(): string
    {
        return '{{ link }}';
    }
}
