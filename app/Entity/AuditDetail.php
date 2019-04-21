<?php

namespace App\Entity;

use App\Entity\Audit;
use Viloveul\Database\Model;

class AuditDetail extends Model
{
    public function relations(): array
    {
        return [
            'audit' => [
                'type' => static::HAS_ONE,
                'class' => Audit::class,
                'keys' => [
                    'audit_id' => 'id',
                ],
            ],
        ];
    }

    public function table(): string
    {
        return '{{ audit_trail }}';
    }
}
