<?php

namespace App\Command;

use App\Message\Hello;
use Viloveul\Console\Command;
use Viloveul\Container\ContainerAwareTrait;
use Viloveul\Transport\Contracts\Bus as IBus;
use Viloveul\Container\Contracts\ContainerAware;

class HelloCommand extends Command implements ContainerAware
{
    use ContainerAwareTrait;

    /**
     * @var string
     */
    protected static $defaultName = 'hello:test';

    public function handle()
    {
        $this->getContainer()->get(IBus::class)->process(new Hello());
    }
}
