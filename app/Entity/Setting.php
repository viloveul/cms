<?php

namespace App\Entity;

use App\Model;

class Setting extends Model
{
    /**
     * @var array
     */
    protected $fillable = [
        'name',
        'option',
    ];

    /**
     * @var string
     */
    protected $table = 'setting';
}
