<?php

namespace App\Entity;

use App\Entity\User;
use App\Entity\AuditDetail;
use Viloveul\Database\Model;

class Audit extends Model
{
    public function relations(): array
    {
        return [
            'details' => [
                'type' => static::HAS_MANY,
                'class' => AuditDetail::class,
                'keys' => [
                    'id' => 'audit_id',
                ],
            ],
            'author' => [
                'type' => static::HAS_ONE,
                'class' => User::class,
                'keys' => [
                    'author_id' => 'id',
                ],
            ],
        ];
    }

    public function table(): string
    {
        return '{{ audit }}';
    }
}
