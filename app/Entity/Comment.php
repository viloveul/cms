<?php

namespace App\Entity;

use Viloveul\Kernel\Model;

class Comment extends Model
{
    /**
     * @var array
     */
    protected $fillable = ['parent_id', 'post_id', 'author_id', 'content', 'status', 'deleted', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * @var string
     */
    protected $table = 'comment';
}
