<?php

namespace App\Widget\Menu;

use App\Component\Setting;
use App\Component\Widget;
use Viloveul\Container\ContainerAwareTrait;
use Viloveul\Container\Contracts\ContainerAware;

class Builder extends Widget implements ContainerAware
{
    use ContainerAwareTrait;

    /**
     * @var array
     */
    protected $options = [
        'type' => 'navmenu',
    ];

    /**
     * @return mixed
     */
    public function results(): array
    {
        return $this->getContainer()->get(Setting::class)->get('menu-' . $this->options['type']) ?: [];
    }
}
