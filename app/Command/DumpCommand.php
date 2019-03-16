<?php

namespace App\Command;

use App\Component\ContentDummy;
use Viloveul\Console\Command;
use Viloveul\Container\ContainerAwareTrait;
use Viloveul\Container\Contracts\ContainerAware;

class DumpCommand extends Command implements ContainerAware
{
    use ContainerAwareTrait;

    /**
     * @var string
     */
    protected static $defaultName = 'cms:dump';

    /**
     * @return mixed
     */
    public function handle()
    {
        $this->writeNormal('--------------------------------------------------------------');
        $this->writeInfo('Create content dummy');
        $dummy = new ContentDummy($user);
        $dummy->run();
        $this->writeNormal('--------------------------------------------------------------');
        $this->writeInfo('Dump complete.');
    }
}
