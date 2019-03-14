<?php

namespace App\Message;

use App\Entity\Notification;
use Viloveul\Transport\Passenger;

class CommentPassenger extends Passenger
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
            'total' => Notification::where('receiver_id', $this->uid)->where('status', 0)->count(),
        ]);
    }

    public function point(): string
    {
        return 'system notification';
    }

    public function task(): string
    {
        return 'comment.notification';
    }
}
