<?php

namespace App\Controller;

use App\Entity\Role;
use App\Entity\RoleChild;
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
                    $childs[] = $child;
                }
            }
            return $this->response->withStatus(201);
        }
        return $this->response->withErrors(404, ['Role not found']);
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
            $model->with('childs');
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
            foreach ($ids as $child_id) {
                RoleChild::where('role_id', $id)->where('child_id', $child_id)->delete();
            }
            return $this->response->withStatus(201);
        }
        return $this->response->withErrors(404, ['Role not found']);
    }
}
