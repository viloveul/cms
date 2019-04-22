<?php

namespace App\Entity;

use App\Entity\Post;
use App\Entity\PostTag;
use Viloveul\Database\Model;

class Tag extends Model
{
    public function relations(): array
    {
        return [
            'postRelations' => [
                'type' => static::HAS_MANY,
                'class' => PostTag::class,
                'keys' => [
                    'id' => 'tag_id',
                ],
            ],
            'posts' => [
                'type' => static::HAS_MANY,
                'class' => Post::class,
                'through' => 'postRelations',
                'keys' => [
                    'post_id' => 'id',
                ],
            ],
        ];
    }

    public function table(): string
    {
        return '{{ tag }}';
    }
}
