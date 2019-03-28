<?php

namespace App\Entity;

use App\Model;
use App\Entity\Audit;

class AuditDetail extends Model
{
    /**
     * @var array
     */
    protected $fillable = [
        'id',
        'audit_id',
        'resource',
        'previous',
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
