<?php

namespace App\Entity;

use App\Entity\Tag;
use App\Entity\User;
use App\Entity\Comment;
use App\Entity\PostTag;
use Viloveul\Database\Model;

class Post extends Model
{
    public function relations(): array
    {
        return [
            'tagRelations' => [
                'type' => static::HAS_MANY,
                'class' => PostTag::class,
                'keys' => [
                    'id' => 'post_id',
                ],
            ],
            'tags' => [
                'type' => static::HAS_MANY,
                'class' => Tag::class,
                'through' => 'tagRelations',
                'keys' => [
                    'tag_id' => 'id',
                ],
            ],
            'comments' => [
                'type' => static::HAS_MANY,
                'class' => Comment::class,
                'keys' => [
                    'id' => 'post_id',
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
        return '{{ post }}';
    }
}
