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
     * @param Cache          $cache
     * @param Authentication $auth
     */
    public function __construct(Cache $cache, Authentication $auth)
    {
        $this->cache = $cache;
        $this->user = $auth->getUser();
        if (!$this->cache->has('privilege.roles') || !$this->cache->has('privilege.users')) {
            $this->load();
        } else {
            // retrieve from cache
            $this->roles = $this->cache->get('privilege.roles') ?: [];
            $this->users = $this->cache->get('privilege.users') ?: [];
        }
    }

    /**
     * @param string $name
     * @param string $type
     * @param string $author_id
     */
    public function check(string $name, string $type = 'access', string $author_id = '0'): bool
    {
        if (strlen($author_id) > 0 && $author_id == $this->user->get('sub')) {
            return true;
        }
        $me = $this->mine();
        if (in_array($name . '#' . $type, $me)) {
            return true;
        } else {
            return in_array($name . '#' . $type, $me);
        }
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

    /**
     * @param $name
     * @param $type
     */
    public function getRoleUsers($name, $type = 'access')
    {
        $roles = array_filter($this->roles, function ($roles) use ($name, $type) {
            return in_array("{$name}#{$type}", $roles);
        });
        $ids = array_keys($roles);
        $users = array_filter($this->users, function ($users) use ($ids) {
            foreach ($ids as $id) {
                if (in_array($id, $users)) {
                    return true;
                }
            }
            return false;
        });
        return array_keys($users);
    }

    /**
     * @param  string  $id
     * @return mixed
     */
    public function getUserRoles(string $id)
    {
        if (array_key_exists($id, $this->users)) {
            $roles = [];
            foreach ($this->users[$id] as $roleId) {
                if (array_key_exists($roleId, $this->roles)) {
                    foreach ($this->roles[$roleId] as $role) {
                        if (!in_array($role, $roles)) {
                            $roles[] = $role;
                        }
                    }
                }
            }
            return $roles;
        }
        return [];
    }

    public function load(): void
    {
        $users = UserRole::all()->toArray();
        $childs = RoleChild::all()->toArray();
        $roles = Role::all()->toArray();

        foreach ($roles as $role) {
            $this->roles[$role['id']][] = $role['name'] . '#' . $role['type'];
        }
        // create key -> value | parent -> childs
        $relations = [];
        foreach ($childs as $child) {
            $relations[$child['role_id']][] = $child['child_id'];
        }
        // make appended child name to parent
        array_walk($this->roles, function ($childs, $id) use ($relations) {
            $this->recursive($id, $relations);
        });

        foreach ($users as $user) {
            if (array_key_exists($user['role_id'], $this->roles)) {
                $this->users[$user['user_id']][] = $user['role_id'];
            }
        }
        $this->cache->set('privilege.roles', $this->roles);
        $this->cache->set('privilege.users', $this->users);
    }

    /**
     * @return mixed
     */
    public function mine()
    {
        if ($id = $this->user->get('sub')) {
            return $this->getUserRoles($id);
        }
        return [];
    }

    /**
     * @param string $id
     * @param array  $relations
     */
    public function recursive(string $id, array $relations): void
    {
        if (array_key_exists($id, $relations)) {
            foreach ($relations[$id] as $childId) {
                $this->recursive($childId, $relations);
                if (array_key_exists($childId, $this->roles)) {
                    foreach ($this->roles[$childId] as $own) {
                        if (!in_array($own, $this->roles[$id])) {
                            $this->roles[$id][] = $own;
                        }
                    }
                }
            }
        }
    }
}
