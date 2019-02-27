<?php

namespace App\Entity;

use App\Entity\Post;
use App\Entity\User;
use App\Model;

class Comment extends Model
{
    /**
     * @var array
     */
    protected $fillable = [
        'parent_id',
        'post_id',
        'author_id',
        'name',
        'email',
        'website',
        'content',
        'status',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * @var string
     */
    protected $table = 'comment';

    /**
     * @return mixed
     */
    public function author()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return mixed
     */
    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * @param $value
     */
    public function setStatusAttribute($value)
    {
        $this->attributes['status'] = abs($value);
    }
}
