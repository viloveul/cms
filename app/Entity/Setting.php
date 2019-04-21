<?php

namespace App\Entity;

use Viloveul\Database\Model;

class Setting extends Model
{
    public function relations(): array
    {
        return [];
    }

    public function table(): string
    {
        return '{{ setting }}';
    }
}
