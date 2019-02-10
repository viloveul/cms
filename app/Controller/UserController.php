<?php

namespace App\Controller;

use App\Component\AttrAssignment;
use App\Component\Privilege;
use App\Component\Setting;
use App\Entity\User;
use App\Validation\User as UserValidation;
use Viloveul\Auth\Contracts\Authentication;
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
     * @return mixed
     */
    public function create()
    {
        $attr = $this->request->loadPostTo(new AttrAssignment);
        $validator = new UserValidation($attr->getAttributes());
        if ($validator->validate('insert')) {
            $user = new User();
            $data = array_only($attr->getAttributes(), ['name', 'picture', 'username', 'email', 'status']);
            foreach ($data as $key => $value) {
                $user->{$key} = $value;
            }
            $user->created_at = date('Y-m-d H:i:s');
            $user->password = password_hash($attr->get('password'), PASSWORD_DEFAULT);
            if ($user->save()) {
                $relations = $attr->get('relations') ?: [];
                $user->roles()->sync($relations);
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
            $user->status = 3;
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
            if (!$user->picture) {
                $uri = $this->request->getUri();
                $user->picture = sprintf(
                    '%s://%s:%s/images/no-image.jpg',
                    $uri->getScheme(),
                    $uri->getHost(),
                    $uri->getPort()
                );
            }
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
     * @param  Authentication $auth
     * @param  Privilege      $privilege
     * @param  Setting        $setting
     * @return mixed
     */
    public function me(Authentication $auth, Privilege $privilege, Setting $setting)
    {
        if ($id = $auth->getUser()->get('sub')) {
            if ($user = User::where('id', $id)->where('status', 1)->first()) {
                if (!$user->picrure) {
                    $uri = $this->request->getUri();
                    $user->picture = sprintf(
                        '%s://%s:%s/images/no-image.jpg',
                        $uri->getScheme(),
                        $uri->getHost(),
                        $uri->getPort()
                    );
                }
                return $this->response->withPayload([
                    'data' => [
                        'id' => $user->id,
                        'type' => 'user_profile',
                        'attributes' => $user,
                    ],
                    'meta' => [
                        'token' => $auth->getToken(),
                        'privileges' => $privilege->mine()
                    ],
                ]);
            } else {
                return $this->response->withErrors(400, ['User not actived.']);
            }
        } else {
            return $this->response->withErrors(400, ['Invalid Credentials']);
        }
    }

    /**
     * @param  int     $id
     * @return mixed
     */
    public function publish(int $id)
    {
        if ($user = User::where('id', $id)->first()) {
            $user->status = 1;
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
    public function update(int $id)
    {
        if ($user = User::where('id', $id)->first()) {
            $attr = $this->request->loadPostTo(new AttrAssignment);
            $attr->get('password') or $attr->forget('password');
            $validator = new UserValidation($attr->getAttributes(), ['id' => $id]);
            if ($validator->validate('update')) {
                $data = array_only($attr->getAttributes(), ['name', 'picture', 'email', 'username', 'status']);
                foreach ($data as $key => $value) {
                    $user->{$key} = $value;
                }
                $user->updated_at = date('Y-m-d H:i:s');
                if ($password = $attr->get('password')) {
                    $user->password = password_hash($password, PASSWORD_DEFAULT);
                }
                if ($user->save()) {
                    $relations = $attr->get('relations') ?: [];
                    $user->roles()->sync($relations);
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
