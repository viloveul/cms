<?php

namespace App\Widget;

use App\Entity\Comment;
use App\Component\Widget;
use Viloveul\Database\Contracts\Query;

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
        $comments = Comment::where(['status' => 1])
            ->with('post')
            ->with('author')
            ->orderBy('created_at', Query::SORT_DESC)
            ->limit($this->options['size'])
            ->findAll();
        return $comments->toArray();
    }
}
