<?php

namespace App\Entity;

use App\Entity\User;
use Viloveul\Kernel\Model;

class Media extends Model
{
    /**
     * @var array
     */
    protected $fillable = [
        'author_id',
        'name',
        'filename',
        'type',
        'size',
        'year',
        'month',
        'day',
        'status',
        'deleted',
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
}
