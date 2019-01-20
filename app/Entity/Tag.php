<?php

namespace App\Entity;

use App\Entity\Post;
use Viloveul\Kernel\Model;

class Tag extends Model
{
    /**
     * @var array
     */
    protected $fillable = [
        'name',
        'type',
        'description',
        'deleted',
        'status',
    ];

    /**
     * @var string
     */
    protected $table = 'tag';

    /**
     * @return mixed
     */
    public function posts()
    {
        return $this->belongsToMany(Post::class, 'post_tag')->where('deleted', 0)->where('status', 1);
    }
}
