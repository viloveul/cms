<?php

namespace App\Controller;

use App\Component\AttrAssignment;
use App\Component\Privilege;
use App\Component\Setting;
use App\Entity\Notification;
use App\Entity\User;
use App\Validation\User as Validation;
use Viloveul\Auth\Contracts\Authentication;
use Viloveul\Config\Contracts\Configuration;
use Viloveul\Http\Contracts\Response;
use Viloveul\Http\Contracts\ServerRequest;
use Viloveul\Pagination\Builder as Pagination;
use Viloveul\Pagination\Parameter;
use Viloveul\Router\Contracts\Dispatcher;

class UserController
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
     * @var mixed
     */
    protected $setting;

    /**
     * @var mixed
     */
    protected $user;

    /**
     * @param ServerRequest  $request
     * @param Response       $response
     * @param Privilege      $privilege
     * @param Configuration  $config
     * @param Setting        $setting
     * @param Authentication $auth
     * @param Dispatcher     $router
     */
    public function __construct(
        ServerRequest $request,
        Response $response,
        Privilege $privilege,
        Configuration $config,
        Setting $setting,
        Authentication $auth,
        Dispatcher $router
    ) {
        $this->request = $request;
        $this->response = $response;
        $this->privilege = $privilege;
        $this->config = $config;
        $this->setting = $setting;
        $this->route = $router->routed();
        $this->user = $auth->getUser();
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
        $validator = new Validation($attr->getAttributes());
        if ($validator->validate('insert')) {
            $user = new User();
            $data = array_only($attr->getAttributes(), [
                'name',
                'picture',
                'username',
                'email',
                'status',
            ]);
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
        if ($this->privilege->check($this->route->getName(), 'access') !== true) {
            return $this->response->withErrors(403, [
                "No direct access for route: {$this->route->getName()}",
            ]);
        }
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
        if ($this->privilege->check($this->route->getName(), 'access', $id) !== true) {
            return $this->response->withErrors(403, [
                "No direct access for route: {$this->route->getName()}",
            ]);
        }
        if ($user = User::where('id', $id)->first()) {
            if (!$user->picture) {
                $user->picture = sprintf(
                    '%s/images/no-image.jpg',
                    $this->request->getBaseUrl()
                );
            }
            return $this->response->withPayload([
                'data' => [
                    'id' => $user->id,
                    'type' => 'user',
                    'attributes' => $user->toArray(),
                    'relationships' => [
                        'roles' => [
                            'data' => $user->roles,
                        ],
                    ],
                ],
            ]);
        } else {
            return $this->response->withErrors(404, ['User not found']);
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
        $parameter->setBaseUrl("{$this->config->basepath}/user/index");
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
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'type' => 'user',
                        'attributes' => $user,
                    ];
                })->toArray();
        });

        return $this->response->withPayload($pagination->getResults());
    }

    /**
     * @return mixed
     */
    public function me()
    {
        if ($id = $this->user->get('sub')) {
            if ($user = User::where('id', $id)->where('status', 1)->first()) {
                if (!$user->picture) {
                    $user->picture = sprintf(
                        '%s/images/no-image.jpg',
                        $this->request->getBaseUrl()
                    );
                }
                return $this->response->withPayload([
                    'data' => [
                        'id' => $user->id,
                        'type' => 'user',
                        'attributes' => $user,
                    ],
                    'meta' => [
                        'notification' => [
                            'total' => Notification::where('receiver_id', $id)->count(),
                            'unread' => Notification::where('receiver_id', $id)->where('status', 0)->count(),
                            'read' => Notification::where('receiver_id', $id)->where('status', 1)->count(),
                        ],
                        'privileges' => $this->privilege->mine(),
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
    public function relations(int $id)
    {
        if ($this->privilege->check($this->route->getName(), 'access') !== true) {
            return $this->response->withErrors(403, [
                "No direct access for route: {$this->route->getName()}",
            ]);
        }
        if ($user = User::where('id', $id)->first()) {
            $body = $this->request->getBody()->getContents() ?: '[]';
            $relations = json_decode($body, true) ?: [];
            is_array($relations) and $user->roles()->sync($relations);
            $this->privilege->load();
            return $this->response->withPayload([
                'data' => [
                    'id' => $id,
                    'type' => 'user',
                    'attributes' => $user,
                ],
            ]);
        } else {
            return $this->response->withErrors(404, ['User not found']);
        }
    }

    /**
     * @param $id
     */
    public function update(int $id)
    {
        if ($this->privilege->check($this->route->getName(), 'access', $id) !== true) {
            return $this->response->withErrors(403, [
                "No direct access for route: {$this->route->getName()}",
            ]);
        }
        if ($user = User::where('id', $id)->first()) {
            $attr = $this->request->loadPostTo(new AttrAssignment);
            $attr->get('password') or $attr->forget('password');
            $validator = new Validation($attr->getAttributes(), ['id' => $id]);
            if ($validator->validate('update')) {
                $data = array_only($attr->getAttributes(), [
                    'name',
                    'picture',
                    'email',
                    'username',
                    'status',
                ]);
                foreach ($data as $key => $value) {
                    $user->{$key} = $value;
                }
                $user->updated_at = date('Y-m-d H:i:s');
                if ($password = $attr->get('password')) {
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
