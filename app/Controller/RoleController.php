<?php

namespace App\Controller;

use App\Entity\Role;
use App\Component\Helper;
use App\Component\Privilege;
use App\Component\AttrAssignment;
use Viloveul\Pagination\Parameter;
use Viloveul\Http\Contracts\Response;
use App\Validation\Role as Validation;
use Viloveul\Router\Contracts\Dispatcher;
use Viloveul\Http\Contracts\ServerRequest;
use Viloveul\Config\Contracts\Configuration;
use Viloveul\Pagination\Builder as Pagination;

class RoleController
{
    /**
     * @var mixed
     */
    protected $config;

    /**
     * @var mixed
     */
    protected $helper;

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
     * @param Helper        $helper
     * @param Dispatcher    $router
     */
    public function __construct(
        ServerRequest $request,
        Response $response,
        Privilege $privilege,
        Configuration $config,
        Helper $helper,
        Dispatcher $router
    ) {
        $this->request = $request;
        $this->response = $response;
        $this->privilege = $privilege;
        $this->config = $config;
        $this->helper = $helper;
        $this->route = $router->routed();
    }

    /**
     * @return mixed
     */
    public function all()
    {
        $role = Role::select(['id', 'name', 'type']);
        foreach ($_GET as $key => $value) {
            $role->where($key, $value);
        }
        $role->where('status', 1);
        return $this->response->withPayload([
            'data' => $role->get()->map(function ($role) {
                return $role->getAttributes();
            }),
        ]);
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
        if ($role = Role::where('id', $id)->where('status', 1)->first()) {
            $ids = (array) $this->request->getPost('childs') ?: [];
            $role->childs()->attach($ids);
            $role->load('childs');
            $this->privilege->clear();
            return $this->response->withStatus(201)->withPayload([
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
            $role->id = $this->helper->uuid();
            if ($role->save()) {
                return $this->response->withPayload([
                    'data' => $role->getAttributes(),
                ]);
            } else {
                return $this->response->withErrors(500, ['Something Wrong !!!']);
            }
        } else {
            return $this->response->withErrors(400, $validator->errors());
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
        if ($role = Role::where('id', $id)->with('childs')->first()) {
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
        $pagination->prepare(function () {
            $model = Role::query();
            $parameter = $this->getParameter();
            foreach ($parameter->getConditions() as $key => $value) {
                $model->where($key, 'like', "%{$value}%");
            }
            $this->total = $model->count();
            $this->data = $model->orderBy($parameter->getOrderBy(), $parameter->getSortOrder())
                ->skip(($parameter->getCurrentPage() * $parameter->getPageSize()) - $parameter->getPageSize())
                ->take($parameter->getPageSize())
                ->get()
                ->toArray();
        });
        return $this->response->withPayload($pagination->getResults());
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
        if ($role = Role::where('id', $id)->where('status', 1)->first()) {
            $ids = (array) $this->request->getPost('childs') ?: [];
            $role->childs()->detach($ids);
            $role->load('childs');
            $this->privilege->clear();
            return $this->response->withStatus(201)->withPayload([
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
        if ($role = Role::where('id', $id)->first()) {
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
                if ($role->save()) {
                    return $this->response->withPayload([
                        'data' => $role->getAttributes(),
                    ]);
                } else {
                    return $this->response->withErrors(500, ['Something Wrong !!!']);
                }
            } else {
                return $this->response->withErrors(400, $validator->errors());
            }
        } else {
            return $this->response->withErrors(404, ['Role not found']);
        }
    }
}
