<?php

namespace App\Widget;

use App\Entity\Tag;
use App\Component\Widget;

class Archive extends Widget
{
    /**
     * @var array
     */
    protected $options = [
        'type' => 'tag',
        'level' => 1,
    ];

    /**
     * @return mixed
     */
    public function results(): array
    {
        $tags = Tag::where(['type' => $this->options['type'], 'status' => 1])->getResults();
        return $tags->toArray();
    }
}
