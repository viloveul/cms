<?php

namespace App\Entity;

use App\Entity\User;
use App\Model;

class Media extends Model
{
    /**
     * @var array
     */
    protected $fillable = [
        'author_id',
        'name',
        'filename',
        'ref',
        'type',
        'size',
        'year',
        'month',
        'day',
        'status',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * @var string
     */
    protected $table = 'media';

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
