<?php

namespace App\Entity;

use Viloveul\Database\Model;

class PostTag extends Model
{
    public function primary()
    {
        return ['post_id', 'tag_id'];
    }

    public function relations(): array
    {
        return [];
    }

    public function table(): string
    {
        return '{{ post_tag }}';
    }
}
