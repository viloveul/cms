<?php

namespace App\Widget;

use App\Entity\Post;
use App\Component\Widget;
use Viloveul\Database\Contracts\Query;

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
        return Post::select(['id', 'author_id', 'created_at', 'title', 'slug', 'type'])
            ->where(['status' => 1, 'type' => $this->options['type']])
            ->where(['created_at' => date('Y-m-d H:i:s')], Query::OPERATOR_LTE)
            ->with('author')
            ->order('created_at', Query::SORT_DESC)
            ->limit($this->options['size'])
            ->findAll()
            ->toArray();
    }
}
