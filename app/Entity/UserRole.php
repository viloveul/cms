<?php

namespace App\Entity;

use Viloveul\Database\Model;

class UserRole extends Model
{
    public function primary()
    {
        return ['user_id', 'role_id'];
    }

    public function relations(): array
    {
        return [];
    }

    public function table(): string
    {
        return '{{ user_role }}';
    }
}
