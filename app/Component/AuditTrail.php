<?php

namespace App\Component;

use App\Entity\Audit;
use App\Entity\AuditDetail;
use Viloveul\Auth\Contracts\Authentication;

class AuditTrail
{
    /**
     * @var mixed
     */
    protected $agent;

    /**
     * @var mixed
     */
    protected $ip;

    /**
     * @var mixed
     */
    protected $user;

    /**
     * @param Authentication $auth
     */
    public function __construct(Authentication $auth)
    {
        $this->user = $auth->getUser();
        $this->agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Console';
        $this->ip = $this->ipOrHost();
    }

    /**
     * @param int    $id
     * @param string $entity
     */
    public function create(string $id, string $entity): void
    {
        $this->audit($id, $entity, 'create');
    }

    /**
     * @param int    $id
     * @param string $entity
     */
    public function delete(string $id, string $entity): void
    {
        $this->audit($id, $entity, 'delete');
    }

    /**
     * @param int    $id
     * @param string $entity
     * @param string $type
     */
    public function record(string $id, string $entity, string $type = 'RECORD'): void
    {
        $this->audit($id, $entity, $type);
    }

    /**
     * @param int    $id
     * @param string $entity
     * @param array  $current
     * @param array  $previous
     */
    public function update(string $id, string $entity, array $current, array $previous = []): void
    {
        $audit = $this->audit($id, $entity, 'update');
        foreach ($current as $field => $value) {
            $old = array_key_exists($field, $previous) ? $previous[$field] : null;
            if ($value != $old && !in_array($field, ['created_at', 'updated_at', 'deleted_at'])) {
                $detail = new AuditDetail();
                $detail->setAttributes([
                    'id' => str_uuid(),
                    'audit_id' => $audit->id,
                    'resource' => $field,
                    'previous' => $old,
                ]);
                $detail->save();
            }
        }
    }

    /**
     * @param int    $id
     * @param string $entity
     * @param string $type
     */
    protected function audit(string $id, string $entity, string $type = 'CREATE'): Audit
    {
        $audit = new Audit();
        $audit->setAttributes([
            'id' => str_uuid(),
            'object_id' => $id,
            'author_id' => $this->user->get('sub') ?: 0,
            'entity' => $entity,
            'ip' => $this->ip,
            'agent' => $this->agent,
            'type' => strtoupper($type),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $audit->save();
        return $audit;
    }

    /**
     * @return mixed
     */
    protected function ipOrHost()
    {
        $ips = [];
        foreach (['HTTP_X_REAL_IP', 'HTTP_X_FORWARDER_FOR', 'REMOTE_ADDR'] as $ip) {
            if (array_key_exists($ip, $_SERVER) && !in_array($_SERVER[$ip], $ips)) {
                $ips[] = $_SERVER[$ip];
            }
        }
        return $ips ? implode(':', $ips) : '127.0.0.1';
    }
}
