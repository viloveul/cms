<?php

namespace App\Command;

use App\Component\ContentDummy;
use App\Entity\User;
use Viloveul\Console\Command;
use Viloveul\Container\ContainerAwareTrait;
use Viloveul\Container\Contracts\ContainerAware;

class DummyCommand extends Command implements ContainerAware
{
    use ContainerAwareTrait;

    /**
     * @var string
     */
    protected static $defaultName = 'cms:dummy';

    /**
     * @return mixed
     */
    public function handle()
    {
        $this->writeNormal('--------------------------------------------------------------');
        $this->writeInfo('Create content dummy');
        $dummy = new ContentDummy(User::first());
        $dummy->run();
        $this->writeNormal('--------------------------------------------------------------');
        $this->writeInfo('Dump complete.');
    }
}
