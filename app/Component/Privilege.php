<?php

namespace App\Component;

use App\Entity\Role;
use App\Entity\RoleChild;
use App\Entity\UserRole;
use Viloveul\Auth\Contracts\Authentication;
use Viloveul\Cache\Contracts\Cache;

class Privilege
{
    /**
     * @var mixed
     */
    protected $auth;

    /**
     * @var mixed
     */
    protected $cache;

    /**
     * @var array
     */
    protected $permissions = [];

    /**
     * @var mixed
     */
    protected $users = [];

    /**
     * @param Cache   $cache
     * @param $name
     */
    public function __construct(Cache $cache, Authentication $auth)
    {
        $this->cache = $cache;
        $this->auth = $auth;
        if (!$this->cache->has('privilege.permissions') || !$this->cache->has('privilege.users')) {
            $this->load();
        } else {
            // retrieve from cache
            $this->permissions = $this->cache->get('privilege.permissions') ?: [];
            $this->users = $this->cache->get('privilege.users') ?: [];
        }
    }

    /**
     * @param $name
     * @param $object_id
     */
    public function check($name, $object_id = 0)
    {
        if ($sub = array_get($this->auth->authenticate(), 'sub')) {
            $id = $sub->getValue();
            if (array_key_exists($id, $this->users)) {
                if (in_array($name . '-' . $object_id, $this->users[$id])) {
                    return true;
                } else {
                    return in_array($name . '-0', $this->users[$id]);
                }
            }
        }
        return false;
    }

    public function clear()
    {
        if ($this->cache->has('privilege.permissions')) {
            $this->cache->delete('privilege.permissions');
            $this->permissions = [];
        }
        if ($this->cache->has('privilege.users')) {
            $this->cache->delete('privilege.users');
            $this->users = [];
        }
    }

    public function load()
    {
        foreach (Role::all() ?: [] as $role) {
            $this->permissions[$role->id][] = $role->name . '-' . abs($role->object_id);
        }
        $relations = [];
        foreach (RoleChild::all() ?: [] as $child) {
            $relations[$child->role_id][] = $child->child_id;
        }
        foreach (array_keys($this->permissions) as $key) {
            $this->recursive($relations, $key);
        }
        foreach (UserRole::all() ?: [] as $user) {
            if (!array_key_exists($user->user_id, $this->users)) {
                $this->users[$user->user_id] = [];
            }
            $this->users[$user->user_id] = array_unique(array_merge(
                $this->users[$user->user_id],
                $this->permissions[$user->role_id]
            ));
        }
        $this->cache->set('privilege.permissions', $this->permissions);
        $this->cache->set('privilege.users', $this->users);
    }

    /**
     * @param array  $relations
     * @param $key
     */
    protected function recursive(array $relations, $key)
    {
        if (array_key_exists($key, $relations)) {
            foreach ($relations[$key] as $child) {
                if (array_key_exists($child, $relations)) {
                    $this->recursive($relations, $child);
                }
                if (array_key_exists($child, $this->permissions)) {
                    $this->permissions[$key] = array_unique(array_merge(
                        $this->permissions[$key],
                        $this->permissions[$child]
                    ));
                }
            }
        }
    }
}
