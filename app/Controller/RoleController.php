<?php

namespace App\Controller;

use App\Component\AttrAssignment;
use App\Component\Privilege;
use App\Entity\Role;
use App\Validation\Role as Validation;
use Viloveul\Config\Contracts\Configuration;
use Viloveul\Http\Contracts\Response;
use Viloveul\Http\Contracts\ServerRequest;
use Viloveul\Pagination\Builder as Pagination;
use Viloveul\Pagination\Parameter;
use Viloveul\Router\Contracts\Dispatcher;

class RoleController
{
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
     * @param Dispatcher    $router
     */
    public function __construct(
        ServerRequest $request,
        Response $response,
        Privilege $privilege,
        Configuration $config,
        Dispatcher $router
    ) {
        $this->request = $request;
        $this->response = $response;
        $this->privilege = $privilege;
        $this->config = $config;
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
                return [
                    'id' => $role->id,
                    'attributes' => $role->getAttributes(),
                ];
            }),
        ]);
    }

    /**
     * @param $id
     */
    public function assign($id)
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
                'data' => [
                    'id' => $role->id,
                    'type' => 'role',
                    'attributes' => $role->getAttributes(),
                ],
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
        $attr = $this->request->loadPostTo(new AttrAssignment);
        $attr->set('name', preg_replace('/[^a-z0-9\-\.\_]+/', '-', strtolower($attr->get('name'))), true);
        $validator = new Validation($attr->getAttributes());
        if ($validator->validate('insert')) {
            $role = new Role();
            $data = array_only($attr->getAttributes(), ['name', 'type', 'description']);
            foreach ($data as $key => $value) {
                $role->{$key} = $value;
            }
            $role->created_at = date('Y-m-d H:i:s');
            if ($role->save()) {
                return $this->response->withPayload([
                    'data' => [
                        'id' => $role->id,
                        'type' => 'role',
                        'attributes' => $role->getAttributes(),
                    ],
                ]);
            } else {
                return $this->response->withErrors(500, ['Something Wrong !!!']);
            }
        } else {
            return $this->response->withErrors(400, $validator->errors());
        }
    }

    /**
     * @param  int     $id
     * @return mixed
     */
    public function detail(int $id)
    {
        if ($this->privilege->check($this->route->getName(), 'access') !== true) {
            return $this->response->withErrors(403, [
                "No direct access for route: {$this->route->getName()}",
            ]);
        }
        if ($role = Role::where('id', $id)->with('childs')->first()) {
            return $this->response->withPayload([
                'data' => [
                    'id' => $role->id,
                    'type' => 'role',
                    'attributes' => $role->getAttributes(),
                    'relationships' => [
                        'childs' => [
                            'data' => $role->childs,
                        ],
                    ],
                ],
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
                ->map(function ($role) {
                    return [
                        'id' => $role->id,
                        'type' => 'role',
                        'attributes' => $role->getAttributes(),
                    ];
                })->toArray();
        });
        return $this->response->withPayload($pagination->getResults());
    }

    /**
     * @param $id
     */
    public function unassign($id)
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
                'data' => [
                    'id' => $role->id,
                    'type' => 'role',
                    'attributes' => $role->getAttributes(),
                ],
            ]);
        }
        return $this->response->withErrors(404, ['Role not found']);
    }

    /**
     * @param  int     $id
     * @return mixed
     */
    public function update(int $id)
    {
        if ($this->privilege->check($this->route->getName(), 'access') !== true) {
            return $this->response->withErrors(403, [
                "No direct access for route: {$this->route->getName()}",
            ]);
        }
        if ($role = Role::where('id', $id)->first()) {
            $attr = $this->request->loadPostTo(new AttrAssignment);
            $attr->set('name', preg_replace('/[^a-z0-9\-\.\_]+/', '-', strtolower($attr->get('name') ?: $role->name)), true);
            $validator = new Validation($attr->getAttributes(), compact('id'));
            if ($validator->validate('update')) {
                $data = array_only($attr->getAttributes(), ['name', 'type', 'description']);
                foreach ($data as $key => $value) {
                    $role->{$key} = $value;
                }
                $role->updated_at = date('Y-m-d H:i:s');
                if ($role->save()) {
                    return $this->response->withPayload([
                        'data' => [
                            'id' => $id,
                            'type' => 'role',
                            'attributes' => $role->getAttributes(),
                        ],
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
