<?php

namespace App\Entity;

use App\Model;
use App\Entity\User;

class Link extends Model
{
    /**
     * @var array
     */
    protected $fillable = [
        'author_id',
        'label',
        'url',
        'icon',
        'description',
        'status',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * @var string
     */
    protected $table = 'link';

    /**
     * @return mixed
     */
    public function author()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @param $value
     */
    public function setStatusAttribute($value)
    {
        $this->attributes['status'] = abs($value);
    }
}
