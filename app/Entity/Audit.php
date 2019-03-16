<?php

namespace App\Entity;

use App\Entity\AuditDetail;
use App\Entity\User;
use App\Model;

class Audit extends Model
{
    /**
     * @var array
     */
    protected $fillable = [
        'author_id',
        'object_id',
        'entity',
        'ip',
        'agent',
        'type',
        'created_at',
    ];

    /**
     * @var string
     */
    protected $table = 'audit';

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
    public function details()
    {
        return $this->hasMany(AuditDetail::class);
    }
}
