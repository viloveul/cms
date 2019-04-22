<?php

namespace App\Entity;

use Viloveul\Database\Model;

class RoleChild extends Model
{
    public function primary()
    {
        return ['role_id', 'child_id'];
    }

    public function relations(): array
    {
        return [];
    }

    public function table(): string
    {
        return '{{ role_child }}';
    }
}
