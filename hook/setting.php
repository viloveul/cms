<?php

/**
 * Format contents
 */
$mutator->addFilter('setting.get', function (Viloveul\Mutator\Contracts\Context $context) {
    if ($context->name === 'contents') {
        $contents = $context->value;
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
        foreach (['posts', 'tags'] as $type) {
            if (array_key_exists($type, $contents ?: [])) {
                foreach ($contents[$type] ?: [] as $data) {
                    if (isset($data->format, $data->name, $data->label)) {
                        $c[$type][] = (array) $data;
                    }
                }
            }
        }
        $context->value = array_map('unserialize', array_unique(array_map('serialize', $c)));
    }
    return $context;
});
