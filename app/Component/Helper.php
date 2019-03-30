<?php

namespace App\Component;

use Ramsey\Uuid\Uuid;
use App\Component\Setting;
use App\Component\Privilege;
use Viloveul\Transport\Contracts\Bus;
use Viloveul\Auth\Contracts\Authentication;
use App\Entity\Notification as NotificationModel;
use App\Message\Notification as NotificationPassenger;
use Ramsey\Uuid\Exception\UnsatisfiedDependencyException;

class Helper
{
    /**
     * @var mixed
     */
    protected $bus;

    /**
     * @var mixed
     */
    protected $privilege;

    /**
     * @var mixed
     */
    protected $setting;

    /**
     * @var mixed
     */
    protected $user;

    /**
     * @param Authentication $auth
     * @param Bus            $bus
     * @param Privilege      $privilege
     * @param Setting        $setting
     */
    public function __construct(
        Authentication $auth,
        Bus $bus,
        Privilege $privilege,
        Setting $setting
    ) {
        $this->user = $auth->getUser();
        $this->bus = $bus;
        $this->privilege = $privilege;
        $this->setting = $setting;
    }

    /**
     * @param  array   $items
     * @param  array   $ids
     * @return mixed
     */
    public function parseRecursive(array $items, array $ids = []): array
    {
        $results = [];
        foreach ($items as $item) {
            $object = (array) $item;
            if (array_key_exists($object['id'], $ids)) {
                $chids = isset($object['children']) ? $object['children'] : [];
                $object = array_merge($ids[$object['id']], [
                    'children' => $chids,
                ]);
                $object['children'] = $this->parseRecursive($object['children'] ?: [], $ids);
                $results[] = $object;
            }
        }
        return $results;
    }

    /**
     * @param $target
     * @param string    $subject
     * @param string    $content
     */
    public function sendNotification($target, string $subject, string $content): void
    {
        if (is_scalar($target)) {
            $this->sendNotification([$target], $subject, $content);
        } else {
            $me = $this->user->get('sub') ?: '0';
            foreach ($target as $id) {
                if ($id != $me) {
                    NotificationModel::create([
                        'id' => $this->uuid(),
                        'author_id' => $me,
                        'receiver_id' => $id,
                        'subject' => $subject,
                        'content' => $content,
                    ]);
                    $this->bus->process(new NotificationPassenger($id));
                }
            }
        }
    }

    public function uuid()
    {
        try {
            return Uuid::uuid4()->toString();
        } catch (UnsatisfiedDependencyException $e) {
            throw $e;
        }
    }
}
