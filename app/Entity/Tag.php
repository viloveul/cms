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
        'title',
        'type',
        'slug',
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
    public function childs()
    {
        return $this->hasMany(Tag::class, 'parent_id', 'id')->where('deleted', 0)->where('status', 1);
    }

    /**
     * @return mixed
     */
    public function posts()
    {
        return $this->belongsToMany(Post::class, 'post_tag')->where('deleted', 0)->where('status', 1);
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
}
