<?php

namespace App\Controller;

use App\Component\AttrAssignment;
use App\Entity\User;
use App\Entity\UserRole;
use App\Validation\User as UserValidation;
use Viloveul\Event\Contracts\Dispatcher as Event;
use Viloveul\Http\Contracts\Response;
use Viloveul\Http\Contracts\ServerRequest;
use Viloveul\Pagination\Builder as Pagination;
use Viloveul\Pagination\Parameter;

class UserController
{
    /**
     * @var mixed
     */
    protected $event;

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
    public function __construct(ServerRequest $request, Response $response, Event $event)
    {
        $this->request = $request;
        $this->response = $response;
        $this->event = $event;
    }

    /**
     * @param  $id
     * @return mixed
     */
    public function assign($id)
    {
        if ($user = User::where('id', $id)->where('deleted', 0)->where('status', 1)->first()) {
            $ids = (array) $this->request->getPost('role') ?: [];
            $roles = [];
            foreach ($ids as $role_id) {
                $role = UserRole::firstOrCreate(
                    ['user_id' => $user->id, 'role_id' => $role_id],
                    ['created_at' => date('Y-m-d H:i:s')]
                );
                if ($role) {
                    $roles[] = $role;
                }
            }
            return $this->response->withStatus(201);
        }
        return $this->response->withErrors(404, ['User not found']);
    }

    /**
     * @return mixed
     */
    public function create()
    {
        $data = $this->request->loadPostTo(new AttrAssignment);
        $validator = new UserValidation($data->getAttributes());
        if ($validator->validate('insert')) {
            $user = new User();
            $data = array_only($data->getAttributes(), ['username', 'email', 'password']);
            foreach ($data as $key => $value) {
                $user->{$key} = $value;
            }
            $user->created_at = date('Y-m-d H:i:s');
            $user->password = password_hash(array_get($data, 'password'), PASSWORD_DEFAULT);
            if ($user->save()) {
                return $this->response->withPayload([
                    'data' => [
                        'id' => $user->id,
                        'type' => 'user',
                        'attributes' => $user,
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
     * @param $id
     */
    public function delete(int $id)
    {
        if ($user = User::where('id', $id)->first()) {
            $user->deleted = 1;
            $user->deleted_at = date('Y-m-d H:i:s');
            if ($user->save()) {
                return $this->response->withStatus(201);
            } else {
                return $this->response->withErrors(500, ['Something Wrong !!!']);
            }
        } else {
            return $this->response->withErrors(404, ['User not found']);
        }
    }

    /**
     * @param $id
     */
    public function detail(int $id)
    {
        if ($user = User::where('id', $id)->with('roles')->first()) {
            return $this->response->withPayload([
                'data' => [
                    'id' => $user->id,
                    'type' => 'user',
                    'attributes' => $user,
                ],
            ]);
        } else {
            return $this->response->withErrors(404, ['User not found']);
        }
    }

    /**
     * @return mixed
     */
    public function index(ServerRequest $request)
    {
        $parameter = new Parameter('search', $_GET);
        $parameter->setBaseUrl('/api/v1/user/index');
        $pagination = new Pagination($parameter);
        $pagination->prepare(function () {
            $model = User::query();
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
     * @param  $id
     * @return mixed
     */
    public function unassign($id)
    {
        if ($user = User::where('id', $id)->where('deleted', 0)->where('status', 1)->first()) {
            $ids = (array) $this->request->getPost('role') ?: [];
            foreach ($ids as $role_id) {
                UserRole::where('user_id', $id)->where('role_id', $role_id)->delete();
            }
            return $this->response->withStatus(201);
        }
        return $this->response->withErrors(404, ['User not found']);
    }

    /**
     * @param $id
     */
    public function update(int $id)
    {
        if ($user = User::where('id', $id)->first()) {
            $post = $this->request->loadPostTo(new AttrAssignment);
            $validator = new UserValidation($post->getAttributes(), [$id]);
            if ($validator->validate('update')) {
                $data = array_only($post->getAttributes(), ['username', 'email', 'status', 'deleted']);
                foreach ($data as $key => $value) {
                    $user->{$key} = $value;
                }
                $user->updated_at = date('Y-m-d H:i:s');
                if ($password = array_get($data, 'password')) {
                    $user->password = password_hash($password, PASSWORD_DEFAULT);
                }
                if ($user->save()) {
                    return $this->response->withPayload([
                        'data' => [
                            'id' => $id,
                            'type' => 'user',
                            'attributes' => $user,
                        ],
                    ]);
                } else {
                    return $this->response->withErrors(500, ['Something Wrong !!!']);
                }
            } else {
                return $this->response->withErrors(400, $validator->errors());
            }
        } else {
            return $this->response->withErrors(404, ['User not found']);
        }
    }
}
