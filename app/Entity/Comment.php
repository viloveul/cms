<?php

namespace App\Entity;

use App\Entity\Post;
use App\Entity\User;
use Viloveul\Database\Model;

class Comment extends Model
{
    public function relations(): array
    {
        return [
            'post' => [
                'type' => static::HAS_ONE,
                'class' => Post::class,
                'keys' => [
                    'post_id' => 'id',
                ],
            ],
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
        return '{{ comment }}';
    }
}
