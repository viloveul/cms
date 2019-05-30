<?php

namespace App\Controller;

use App\Entity\Role;
use App\Component\Privilege;
use App\Component\AuditTrail;
use App\Component\AttrAssignment;
use Viloveul\Pagination\Parameter;
use Viloveul\Pagination\ResultSet;
use Viloveul\Http\Contracts\Response;
use App\Validation\Role as Validation;
use Viloveul\Database\Contracts\Query;
use Viloveul\Router\Contracts\Dispatcher;
use Viloveul\Http\Contracts\ServerRequest;
use Viloveul\Config\Contracts\Configuration;
use Viloveul\Pagination\Builder as Pagination;

class RoleController
{
    /**
     * @var mixed
     */
    protected $audit;

    /**
     * @var mixed
     */
    protected $config;

    /**
     * @var mixed
     */
    protected $privilege;

    /**
     * @var mixed
     */
    protected $request;

    /**
     * @var mixed
     */
    protected $response;

    /**
     * @var mixed
     */
    protected $route;

    /**
     * @param ServerRequest $request
     * @param Response      $response
     * @param Privilege     $privilege
     * @param Configuration $config
     * @param AuditTrail    $audit
     * @param Dispatcher    $router
     */
    public function __construct(
        ServerRequest $request,
        Response $response,
        Privilege $privilege,
        Configuration $config,
        AuditTrail $audit,
        Dispatcher $router
    ) {
        $this->request = $request;
        $this->response = $response;
        $this->privilege = $privilege;
        $this->config = $config;
        $this->audit = $audit;
        $this->route = $router->routed();
    }

    /**
     * @param  string  $id
     * @return mixed
     */
    public function assign(string $id)
    {
        if ($this->privilege->check($this->route->getName(), 'access') !== true) {
            return $this->response->withErrors(403, [
                "No direct access for route: {$this->route->getName()}",
            ]);
        }
        if ($role = Role::where(['id' => $id, 'status' => 1])->find()) {
            $ids = (array) $this->request->getPost('childs') ?: [];
            $role->sync('childRelations', $ids, Query::SYNC_ATTACH);
            $role->load('childs');
            $this->privilege->clear();
            return $this->response->withPayload([
                'data' => $role->getAttributes(),
            ]);
        }
        return $this->response->withErrors(404, ['Role not found']);
    }

    /**
     * @return mixed
     */
    public function create()
    {
        if ($this->privilege->check($this->route->getName(), 'access') !== true) {
            return $this->response->withErrors(403, [
                "No direct access for route: {$this->route->getName()}",
            ]);
        }
        $attr = $this->request->loadPostTo(new AttrAssignment());
        $with = $attr->get('type') === 'group' ? ':' : '.';
        $attr->set('name', preg_replace('/[^a-z0-9\-\_]+/', $with, strtolower($attr->get('name'))), true);
        $validator = new Validation($attr->getAttributes());
        if ($validator->validate('insert')) {
            $role = new Role();
            $data = array_only($attr->getAttributes(), ['name', 'type', 'description']);
            foreach ($data as $key => $value) {
                $role->{$key} = $value;
            }
            $role->created_at = date('Y-m-d H:i:s');
            $role->id = str_uuid();
            $role->save();
            $this->audit->create($post->id, 'post');
            return $this->response->withStatus(201)->withPayload([
                'data' => $role->getAttributes(),
            ]);
        } else {
            return $this->response->withErrors(400, $validator->errors());
        }
    }

    /**
     * @param  string  $id
     * @return mixed
     */
    public function delete(string $id)
    {
        if ($this->privilege->check($this->route->getName(), 'access') !== true) {
            return $this->response->withErrors(403, [
                "No direct access for route: {$this->route->getName()}",
            ]);
        }
        if ($role = Role::where(['id' => $id])->find()) {
            $role->deleted_at = date('Y-m-d H:i:s');
            $role->status = 3;
            $role->save();
            $this->audit->delete($role->id, 'role');
            return $this->response->withStatus(204);
        } else {
            return $this->response->withErrors(404, ['Role not found']);
        }
    }

    /**
     * @param  string  $id
     * @return mixed
     */
    public function detail(string $id)
    {
        if ($this->privilege->check($this->route->getName(), 'access') !== true) {
            return $this->response->withErrors(403, [
                "No direct access for route: {$this->route->getName()}",
            ]);
        }
        if ($role = Role::where(['id' => $id])->with('childs')->find()) {
            return $this->response->withPayload([
                'data' => $role,
            ]);
        } else {
            return $this->response->withErrors(404, ['Role not found']);
        }
    }

    /**
     * @return mixed
     */
    public function index()
    {
        if ($this->privilege->check($this->route->getName(), 'access') !== true) {
            return $this->response->withErrors(403, [
                "No direct access for route: {$this->route->getName()}",
            ]);
        }
        $parameter = new Parameter('search', $_GET);
        $parameter->setBaseUrl("{$this->config->basepath}/role/index");
        $pagination = new Pagination($parameter);
        $pagination->with(function ($conditions, $size, $page, $order, $sort) {
            $model = new Role();
            foreach ($conditions as $key => $value) {
                $model->where([$key => "%{$value}%"], Query::OPERATOR_LIKE);
            }
            $total = $model->count();
            $result = $model->orderBy($order, $sort === 'ASC' ? Query::SORT_ASC : Query::SORT_DESC)
                ->limit($size, ($page * $size) - $size)
                ->findAll();
            return new ResultSet($total, $result->toArray());
        });
        return $this->response->withPayload([
            'meta' => $pagination->getMeta(),
            'data' => $pagination->getData(),
            'links' => $pagination->getLinks(),
        ]);
    }

    /**
     * @param  string  $id
     * @return mixed
     */
    public function unassign(string $id)
    {
        if ($this->privilege->check($this->route->getName(), 'access') !== true) {
            return $this->response->withErrors(403, [
                "No direct access for route: {$this->route->getName()}",
            ]);
        }
        if ($role = Role::where(['id' => $id, 'status' => 1])->find()) {
            $ids = (array) $this->request->getPost('childs') ?: [];
            $role->sync('childRelations', $ids, Query::SYNC_DETACH);
            $role->load('childs');
            $this->privilege->clear();
            return $this->response->withPayload([
                'data' => $role->getAttributes(),
            ]);
        }
        return $this->response->withErrors(404, ['Role not found']);
    }

    /**
     * @param  string  $id
     * @return mixed
     */
    public function update(string $id)
    {
        if ($this->privilege->check($this->route->getName(), 'access') !== true) {
            return $this->response->withErrors(403, [
                "No direct access for route: {$this->route->getName()}",
            ]);
        }
        if ($role = Role::where(['id' => $id])->find()) {
            $previous = $role->getAttributes();
            $attr = $this->request->loadPostTo(new AttrAssignment());
            $with = $attr->get('type') === 'group' ? ':' : '.';
            $attr->set('name', preg_replace('/[^a-z0-9\-\_]+/', $with, strtolower($attr->get('name') ?: $role->name)), true);
            $validator = new Validation($attr->getAttributes(), compact('id'));
            if ($validator->validate('update')) {
                $data = array_only($attr->getAttributes(), ['name', 'type', 'description']);
                foreach ($data as $key => $value) {
                    $role->{$key} = $value;
                }
                $role->updated_at = date('Y-m-d H:i:s');
                $role->status = 1;
                $role->save();
                $this->audit->update($id, 'role', $role->getAttributes(), $previous);
                return $this->response->withPayload([
                    'data' => $role->getAttributes(),
                ]);
            } else {
                return $this->response->withErrors(400, $validator->errors());
            }
        } else {
            return $this->response->withErrors(404, ['Role not found']);
        }
    }
}
