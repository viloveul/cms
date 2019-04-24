<?php

namespace App\Widget;

use App\Entity\Link;
use App\Component\Helper;
use App\Component\Widget;
use App\Entity\Menu as Model;
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
        $items = [];
        $content = Model::select('content')->where(['id' => $this->options['id']])->getValue('content');
        $links = Link::select(['id', 'label', 'icon', 'url'])->where(['status' => 1])->getResults();
        foreach ($links->toArray() ?: [] as $link) {
            $items[$link['id']] = $link;
        }
        $decoded = json_decode($content, true) ?: [];
        return $this->getContainer()->get(Helper::class)->parseRecursiveMenu(is_array($decoded) ? $decoded : [], $items) ?: [];
    }
}
