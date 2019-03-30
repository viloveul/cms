<?php

namespace App\Message;

use Viloveul\Transport\Passenger;
use App\Entity\Notification as Model;

class Notification extends Passenger
{
    /**
     * @var mixed
     */
    protected $uid;

    /**
     * @param $uid
     */
    public function __construct($uid)
    {
        $this->uid = $uid;
    }

    public function handle(): void
    {
        $this->setAttribute('user_id', $this->uid);
        $this->setAttribute('data', [
            'total' => Model::where('receiver_id', $this->uid)->count(),
            'unread' => Model::where('receiver_id', $this->uid)->where('status', 0)->count(),
            'read' => Model::where('receiver_id', $this->uid)->where('status', 1)->count(),
        ]);
    }

    public function point(): string
    {
        return 'viloveul.system.queue';
    }

    public function task(): string
    {
        return 'system.notification';
    }
}
