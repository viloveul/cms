<?php

namespace App\Entity;

use App\Entity\Comment;
use App\Entity\Tag;
use App\Entity\User;
use Viloveul\Kernel\Model;

class Post extends Model
{
    /**
     * @var array
     */
    protected $fillable = [
        'parent_id',
        'author_id',
        'title',
        'slug',
        'type',
        'description',
        'content',
        'status',
        'deleted',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * @var string
     */
    protected $table = 'post';

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
    public function comments()
    {
        return $this->hasMany(Comment::class)->where('deleted', 0)->where('status', 1);
    }

    /**
     * @return mixed
     */
    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'post_tag')->where('deleted', 0)->where('status', 1);
    }
}
