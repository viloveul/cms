<?php

/**
 * Format posts
 */
$event->listen('setting.posts', function ($payload) {
    $posts = [
        [
            'name' => 'post',
            'format' => 'post',
            'label' => 'Post',
        ],
        [
            'name' => 'page',
            'format' => 'page',
            'label' => 'Page',
        ],
    ];
    foreach ($payload as $post) {
        if (isset($post->format, $post->name, $post->label)) {
            $posts[] = (array) $post;
        }
    }
    return array_values(array_map('unserialize', array_unique(array_map('serialize', $posts))));
});

/**
 * Format tags
 */
$event->listen('setting.tags', function ($payload) {
    $tags = [
        [
            'name' => 'tag',
            'format' => 'tag',
            'label' => 'Tag',
        ],
        [
            'name' => 'category',
            'format' => 'category',
            'label' => 'Category',
        ],
    ];
    foreach ($payload as $tag) {
        if (isset($tag->format, $tag->name, $tag->label)) {
            $tags[] = (array) $tag;
        }
    }
    return array_values(array_map('unserialize', array_unique(array_map('serialize', $tags))));
});
