<?php

namespace App\Entity;

use Viloveul\Kernel\Model;

class PostTag extends Model
{
    /**
     * @var array
     */
    protected $fillable = [
        'post_id',
        'tag_id',
        'created_at',
    ];

    /**
     * @var string
     */
    protected $table = 'post_tag';
}
