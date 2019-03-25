<?php

namespace App\Entity;

use App\Entity\Menu;
use App\Entity\User;
use App\Model;

class MenuItem extends Model
{
    /**
     * @var array
     */
    protected $fillable = [
        'menu_id',
        'author_id',
        'label',
        'url',
        'status',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * @var string
     */
    protected $table = 'menu_item';

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
    public function menu()
    {
        return $this->belongsTo(Menu::class);
    }

    /**
     * @param $value
     */
    public function setStatusAttribute($value)
    {
        $this->attributes['status'] = abs($value);
    }
}
