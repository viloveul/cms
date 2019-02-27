<?php

namespace App\Entity;

use App\Entity\User;
use App\Model;

class Menu extends Model
{
    /**
     * @var array
     */
    protected $fillable = [
        'author_id',
        'label',
        'icon',
        'type',
        'description',
        'url',
        'status',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * @var string
     */
    protected $table = 'menu';

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
