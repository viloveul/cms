<?php

namespace App\Message;

use Viloveul\Transport\Passenger;

class Hello extends Passenger
{
    public function handle(): void
    {
        $this->setAttribute('data', ['hello']);
    }

    public function point(): string
    {
        return 'viloveul.system.worker';
    }

    public function task(): string
    {
        return 'system.hello';
    }
}
