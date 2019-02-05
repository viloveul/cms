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
        'comment_enabled',
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
     * @param $value
     */
    public function setCommentEnabledAttribute($value)
    {
        $this->attributes['comment_enabled'] = abs($value);
    }

    /**
     * @param $value
     */
    public function setDeletedAttribute($value)
    {
        $this->attributes['deleted'] = abs($value);
    }

    /**
     * @param $value
     */
    public function setStatusAttribute($value)
    {
        $this->attributes['status'] = abs($value);
    }

    /**
     * @return mixed
     */
    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'post_tag')->where('deleted', 0)->where('status', 1);
    }
}
