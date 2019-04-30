<?php

namespace App\Message;

use Viloveul\Transport\Passenger;

class Mailer extends Passenger
{
    /**
     * @var array
     */
    protected $configs = [
        'to' => null,
        'subject' => null,
        'body' => null,
    ];

    /**
     * @param array $configs
     */
    public function __construct(array $configs)
    {
        foreach ($this->configs as $key => $value) {
            if (array_key_exists($key, $configs)) {
                $this->configs[$key] = $configs[$key];
            }
        }
    }

    public function handle(): void
    {
        foreach ($this->configs as $key => $value) {
            $this->setAttribute($key, $value);
        }
    }

    public function point(): string
    {
        return 'viloveul.system.worker';
    }

    public function task(): string
    {
        return 'system.email';
    }
}
