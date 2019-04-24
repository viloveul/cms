<?php

namespace App\Widget;

use App\Entity\MenuItem;
use App\Component\Helper;
use App\Component\Widget;
use Viloveul\Database\Contracts\Query;
use Viloveul\Container\ContainerAwareTrait;
use Viloveul\Container\Contracts\ContainerAware;

class Menu extends Widget implements ContainerAware
{
    use ContainerAwareTrait;

    /**
     * @var array
     */
    protected $options = [
        'id' => '0',
    ];

    /**
     * @return mixed
     */
    public function results(): array
    {
        $results = MenuItem::select(['id', 'parent_id', 'label', 'icon', 'url'])
            ->where(['status' => 1, 'menu_id' => $this->options['id']])
            ->orderBy('order', Query::SORT_ASC)
            ->getResults();
        $items = [];
        foreach ($results->toArray() ?: [] as $item) {
            $items[$item['parent_id']][] = $item;
        }
        return $this->getContainer()->get(Helper::class)->parseRecursiveMenuItem($items, 0) ?: [];
    }
}
