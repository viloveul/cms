<?php

namespace App\Widget;

use App\Entity\Comment;
use App\Component\Widget;

class RecentComment extends Widget
{
    /**
     * @var array
     */
    protected $options = [
        'size' => 10,
    ];

    /**
     * @return mixed
     */
    public function results(): array
    {
        $comments = Comment::where('status', 1)
            ->with(['post', 'author'])
            ->orderBy('created_at', 'desc')
            ->take($this->options['size'])
            ->get();
        return $comments->toArray();
    }
}
