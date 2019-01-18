<?php

namespace App\Entity;

use Viloveul\Kernel\Model;

class Post extends Model
{
    /**
     * @var array
     */
    protected $fillable = ['parent_id', 'author_id', 'title', 'slug', 'description', 'content', 'status', 'deleted', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * @var string
     */
    protected $table = 'post';
}
