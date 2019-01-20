<?php

namespace App\Component;

use App\Entity\Role;
use App\Entity\RoleChild;
use App\Entity\UserRole;
use Exception;
use Viloveul\Auth\Contracts\UserData;
use Viloveul\Cache\Contracts\Cache;

class Privilege
{
    /**
     * @var mixed
     */
    protected $cache;

    /**
     * @var array
     */
    protected $roles = [];

    /**
     * @var mixed
     */
    protected $user;

    /**
     * @var mixed
     */
    protected $users = [];

    /**
     * @param Cache    $cache
     * @param UserData $user
     */
    public function __construct(Cache $cache, UserData $user)
    {
        $this->cache = $cache;
        $this->user = $user;
        if (!$this->cache->has('privilege.roles') || !$this->cache->has('privilege.users')) {
            $this->load();
        } else {
            // retrieve from cache
            $this->roles = $this->cache->get('privilege.roles') ?: [];
            $this->users = $this->cache->get('privilege.users') ?: [];
        }
    }

    /**
     * @param $name
     * @param $object_id
     */
    public function check(string $name, $object_id = 0): bool
    {
        try {
            if ($id = $this->user->get('sub')) {
                if (array_key_exists($id, $this->users)) {
                    if (in_array($name, $this->users[$id])) {
                        return true;
                    } else {
                        return in_array($name, $this->users[$id]);
                    }
                }
            }
        } catch (Exception $e) {

        }
        return false;
    }

    public function clear(): void
    {
        if ($this->cache->has('privilege.roles')) {
            $this->cache->delete('privilege.roles');
            $this->roles = [];
        }
        if ($this->cache->has('privilege.users')) {
            $this->cache->delete('privilege.users');
            $this->users = [];
        }
    }

    public function load(): void
    {
        foreach (Role::all() ?: [] as $role) {
            $this->roles[$role->id][] = $role->name . '/' . $role->type;
        }
        $relations = [];
        foreach (RoleChild::all() ?: [] as $child) {
            $relations[$child->role_id][] = $child->child_id;
        }
        foreach (array_keys($this->roles) as $key) {
            $this->recursive($relations, $key);
        }
        foreach (UserRole::all() ?: [] as $user) {
            if (!array_key_exists($user->user_id, $this->users)) {
                $this->users[$user->user_id] = [];
            }
            $this->users[$user->user_id] = array_unique(array_merge(
                $this->users[$user->user_id],
                $this->roles[$user->role_id]
            ));
        }
        $this->cache->set('privilege.roles', $this->roles);
        $this->cache->set('privilege.users', $this->users);
    }

    /**
     * @param array  $relations
     * @param $key
     */
    protected function recursive(array $relations, $key): void
    {
        if (array_key_exists($key, $relations)) {
            foreach ($relations[$key] as $child) {
                if (array_key_exists($child, $relations)) {
                    $this->recursive($relations, $child);
                }
                if (array_key_exists($child, $this->roles)) {
                    $this->roles[$key] = array_unique(array_merge(
                        $this->roles[$key],
                        $this->roles[$child]
                    ));
                }
            }
        }
    }
}
