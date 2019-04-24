<?php

/**
 * Format contents
 */
$event->listen('setting.get', function ($payload) {
    if ($payload['name'] === 'contents') {
        $contents = $payload['value'];
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
            if (array_key_exists($type, $contents ?: [])) {
                foreach ($contents[$type] ?: [] as $data) {
                    if (isset($data->format, $data->name, $data->label)) {
                        $c[$type][] = (array) $data;
                    }
                }
            }
        }
        $payload['value'] = array_map('unserialize', array_unique(array_map('serialize', $c)));
    }
    return $payload;
});

$event->listen('setting.get', function ($payload) {
    if (strpos($payload['name'], 'menu-') !== false && isset($payload['value']['id'])) {
        $container = Viloveul\Container\ContainerFactory::instance();
        $menu = $payload['value'];
        $items = [];
        $results = App\Entity\MenuItem::select(['id', 'parent_id', 'label', 'icon', 'url'])
            ->where(['status' => 1, 'menu_id' => $menu['id']])
            ->orderBy('order', Viloveul\Database\Contracts\Query::SORT_ASC)
            ->getResults();
        foreach ($results->toArray() ?: [] as $item) {
            $items[$item['parent_id']][] = $item;
        }
        $mapped = $container->get(App\Component\Helper::class)->parseRecursiveMenuItem($items ?: [], 0, $admin) ?: [];
        $payload['value']['items'] = $mapped;
    }
    return $payload;
});
