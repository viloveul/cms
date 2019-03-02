<?php

namespace App\Widget;

use App\Component\Widget;
use App\Entity\Tag;

class Archive extends Widget
{
    /**
     * @var array
     */
    protected $options = [
        'type' => 'tag',
        'level' => 1,
    ];

    public function results(): array
    {
    	$tags = Tag::where('type', $this->options['type'])->where('status', 1)->get();
        return $tags->toArray();
    }
}
