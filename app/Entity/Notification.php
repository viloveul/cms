<?php

namespace App\Entity;

use App\Model;
use App\Entity\User;

class Notification extends Model
{
    /**
     * @var array
     */
    protected $fillable = [
        'id',
        'receiver_id',
        'author_id',
        'subject',
        'content',
        'status',
        'created_at',
        'updated_at',
    ];

    /**
     * @var string
     */
    protected $table = 'notification';

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
    public function receiver()
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
