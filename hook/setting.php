<?php

/**
 * Format contents
 */
$event->listen('setting.contents', function ($payload) {
    $c['posts'] = [
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
    $c['tags'] = [
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
    $c['menus'] = [
        [
            'name' => 'navmenu',
            'format' => 'standar',
            'label' => 'Nav Menu',
        ],
    ];
    foreach (['posts', 'tags', 'menus'] as $type) {
        if (array_key_exists($type, $payload ?: [])) {
            foreach ($payload[$type] ?: [] as $data) {
                if (isset($data->format, $data->name, $data->label)) {
                    $c[$type][] = (array) $data;
                }
            }
        }
    }
    return array_map('unserialize', array_unique(array_map('serialize', $c)));
});
