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

    /**
     * @param  array   $items
     * @param  array   $ids
     * @return mixed
     */
    public function parseRecursiveMenu(array $items, array $ids = [], $isAdmin = false): array
    {
        $results = [];
        foreach ($items as $item) {
            $object = (array) $item;
            if (array_key_exists($object['id'], $ids)) {
                if ($isAdmin || !array_key_exists('role', $ids[$object['id']]) || $this->privilege->check($ids[$object['id']]['role'] === null || $ids[$object['id']]['role']['name'], $ids[$object['id']]['role'] === null || $ids[$object['id']]['role']['type'])) {
                    $chids = isset($object['children']) ? $object['children'] : [];
                    $object = array_merge($ids[$object['id']], [
                        'children' => $chids,
                    ]);
                    $object['children'] = $this->parseRecursiveMenu($object['children'] ?: [], $ids, $isAdmin);
                    $results[] = $object;
                }
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
