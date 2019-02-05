<?php

namespace App\Controller;

use App\Component\AttrAssignment;
use App\Entity\Role;
use App\Entity\RoleChild;
use App\Validation\Role as RoleValidation;
use Viloveul\Http\Contracts\Response;
use Viloveul\Http\Contracts\ServerRequest;
use Viloveul\Pagination\Builder as Pagination;
use Viloveul\Pagination\Parameter;

class RoleController
{
    /**
     * @var mixed
     */
    protected $request;

    /**
     * @var mixed
     */
    protected $response;

    /**
     * @param ServerRequest $request
     * @param Response      $response
     */
    public function __construct(ServerRequest $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
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
        $role->where('deleted', 0);
        return $this->response->withPayload([
            'data' => $role->get(),
        ]);
    }

    /**
     * @param $id
     */
    public function assign($id)
    {
        if ($role = Role::where('id', $id)->where('deleted', 0)->where('status', 1)->first()) {
            $ids = (array) $this->request->getPost('child') ?: [];
            $childs = [];
            foreach ($ids as $child_id) {
                $child = RoleChild::firstOrCreate(
                    ['role_id' => $role->id, 'child_id' => $child_id],
                    ['created_at' => date('Y-m-d H:i:s')]
                );
                if ($child) {
                    $childs[] = $child_id;
                }
            }
            return $this->response->withStatus(201)->withPayload(['data' => $childs]);
        }
        return $this->response->withErrors(404, ['Role not found']);
    }

    /**
     * @return mixed
     */
    public function create()
    {
        $attr = $this->request->loadPostTo(new AttrAssignment);
        $attr->set('name', preg_replace('/[^a-z0-9\-\.\_]+/', '-', strtolower($attr->get('name'))), true);
        $validator = new RoleValidation($attr->getAttributes());
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
                        'attributes' => $role,
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
        if ($role = Role::where('id', $id)->with('childs')->first()) {
            return $this->response->withPayload([
                'data' => [
                    'id' => $role->id,
                    'type' => 'role',
                    'attributes' => $role,
                ],
            ]);
        } else {
            return $this->response->withErrors(404, ['Role not found']);
        }
    }

    public function index()
    {
        $parameter = new Parameter('search', $_GET);
        $parameter->setBaseUrl('/api/v1/role/index');
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
     * @param $id
     */
    public function unassign($id)
    {
        if ($role = Role::where('id', $id)->where('deleted', 0)->where('status', 1)->first()) {
            $ids = (array) $this->request->getPost('child') ?: [];
            $childs = [];
            foreach ($ids as $child_id) {
                if (RoleChild::where('role_id', $id)->where('child_id', $child_id)->delete()) {
                    $childs[] = $child_id;
                }
            }
            return $this->response->withStatus(201)->withPayload(['data' => $childs]);
        }
        return $this->response->withErrors(404, ['Role not found']);
    }

    /**
     * @param  int     $id
     * @return mixed
     */
    public function update(int $id)
    {
        if ($role = Role::where('id', $id)->first()) {
            $attr = $this->request->loadPostTo(new AttrAssignment);
            $attr->set('name', preg_replace('/[^a-z0-9\-\.\_]+/', '-', strtolower($attr->get('name') ?: $role->name)), true);
            $validator = new RoleValidation($attr->getAttributes(), compact('id'));
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
                            'attributes' => $role,
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
