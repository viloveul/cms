<?php

namespace App\Entity;

use App\Entity\Audit;
use App\Model;

class AuditDetail extends Model
{
    /**
     * @var array
     */
    protected $fillable = [
        'audit_id',
        'resource',
        'previous',
        'current',
    ];

    /**
     * @var string
     */
    protected $table = 'audit_detail';

    /**
     * @return mixed
     */
    public function audit()
    {
        return $this->belongsTo(Audit::class);
    }
}
