<?php

namespace App\Command;

use App\Entity\User;
use App\Component\Dummy;
use Viloveul\Console\Command;

class DummyCommand extends Command
{
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
        $dummy = new Dummy(User::where(['status' => 1])->getResult());
        $dummy->run();
        $this->writeNormal('--------------------------------------------------------------');
        $this->writeInfo('Dump complete.');
    }
}
