<?php

namespace App\Widget;

use App\Component\Widget;

class SearchForm extends Widget
{
    /**
     * @var array
     */
    protected $options = [
        'placeholder' => 'Search...',
    ];

    public function results(): array
    {
        return [];
    }
}
