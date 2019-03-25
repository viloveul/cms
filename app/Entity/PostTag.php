<?php

namespace App\Entity;

use App\Model;

class PostTag extends Model
{
    /**
     * @var array
     */
    protected $fillable = [
        'post_id',
        'tag_id',
    ];

    /**
     * @var string
     */
    protected $table = 'post_tag';
}
