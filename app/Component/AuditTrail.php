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
        $this->ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';
        if (isset($_SERVER['HTTP_X_FORWARDER_FOR'])) {
            $this->ip .= ':' . $_SERVER['HTTP_X_FORWARDER_FOR'];
        }
    }

    /**
     * @param int    $id
     * @param string $entity
     */
    public function create(int $id, string $entity): void
    {
        $this->audit($id, $entity, 'create');
    }

    /**
     * @param int    $id
     * @param string $entity
     */
    public function delete(int $id, string $entity): void
    {
        $this->audit($id, $entity, 'delete');
    }

    /**
     * @param int    $id
     * @param string $entity
     * @param string $type
     */
    public function record(int $id, string $entity, string $type = 'RECORD'): void
    {
        $this->audit($id, $entity, $type);
    }

    /**
     * @param int    $id
     * @param string $entity
     * @param array  $current
     * @param array  $previous
     */
    public function update(int $id, string $entity, array $current, array $previous = []): void
    {
        $audit = $this->audit($id, $entity, 'update');
        foreach ($current as $key => $value) {
            $old = array_key_exists($key, $previous) ? $previous[$key] : null;
            if ($value != $old) {
                AuditDetail::create([
                    'audit_id' => $audit->id,
                    'resource' => $key,
                    'previous' => $old,
                    'current' => $value,
                ]);
            }
        }
    }

    /**
     * @param int    $id
     * @param string $entity
     * @param string $type
     */
    protected function audit(int $id, string $entity, string $type = 'CREATE'): Audit
    {
        return Audit::create([
            'object_id' => $id,
            'author_id' => $this->user->get('sub') ?: 0,
            'entity' => $entity,
            'ip' => $this->ip,
            'agent' => $this->agent,
            'type' => strtoupper($type),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
