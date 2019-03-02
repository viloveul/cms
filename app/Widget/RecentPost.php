<?php

namespace App\Widget;

use App\Component\Widget;
use App\Entity\Post;

class RecentPost extends Widget
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
        return Post::select(['id', 'author_id', 'created_at', 'title', 'description', 'slug', 'type'])
            ->where('status', 1)
            ->where('type', $this->options['type'])
            ->with(['author'])
            ->orderBy('id', 'desc')
            ->take($this->options['size'])
            ->get()
            ->map(function ($post) {
                return [
                    'id' => $post->id,
                    'type' => $post->type,
                    'attributes' => $post->getAttributes(),
                    'relationships' => [
                        'author' => [
                            'data' => $post->author,
                        ],
                    ],
                ];
            })
            ->toArray();
    }
}
