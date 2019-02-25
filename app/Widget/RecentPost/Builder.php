<?php

namespace App\Widget\RecentPost;

use App\Component\Widget;
use App\Entity\Post;

class Builder extends Widget
{
    /**
     * @var array
     */
    protected $options = [
        'type' => 'post',
        'size' => 10,
    ];

    /**
     * @return mixed
     */
    public function results(): array
    {
        $posts = Post::select(['id', 'title', 'type', 'author_id', 'slug'])->where('status', 1)->where('type', $this->options['type'])
            ->with(['author'])
            ->orderBy('id', 'desc')
            ->take($this->options['size'])
            ->get();
        return $posts->toArray();
    }
}
