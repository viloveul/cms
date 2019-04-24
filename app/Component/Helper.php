<?php

namespace App\Component;

use Ramsey\Uuid\Uuid;
use App\Component\Setting;
use App\Component\Privilege;
use Viloveul\Transport\Contracts\Bus;
use Viloveul\Auth\Contracts\Authentication;
use App\Entity\Notification as NotificationModel;

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

    public function parseRecursiveMenuItem(array $items, $parentId, $isAdmin = false): array
    {
        $results = [];
        if (array_key_exists($parentId, $items)) {
            foreach ($items[$parentId] as $item) {
                if ($isAdmin || !array_key_exists('role', $item) || empty($item['role']) || $this->privilege->check($item['role']['name'], $item['role']['type'])) {
                    $object = $item;
                    if ($child = $this->parseRecursiveMenuItem($items, $item['id'], $isAdmin)) {
                        $object['children'] = $child;
                    }
                    $results[] = $object;
                }
            }
        }
        return $results;
    }

    public function normalizeMenuItem(array $items, $parentId = 0, $order = 1)
    {
        $results = [];
        foreach ($items as $item) {
            $new = [
                'id' => $item['id'],
                'parent_id' => $parentId,
                'order' => $order
            ];
            $results[] = $new;
            if (array_key_exists('children', $item)) {
                if ($child = $this->normalizeMenuItem($item['children'], $item['id'], 1)) {
                    $results = array_merge($results, $child);
                }
            }
            $order++;
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
                    $notif = new NotificationModel();
                    $notif->setAttributes([
                        'id' => str_uuid(),
                        'author_id' => $me,
                        'receiver_id' => $id,
                        'subject' => $subject,
                        'content' => $content,
                    ]);
                    $notif->save();
                }
            }
        }
    }
}
