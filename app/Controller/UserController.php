<?php

namespace App\Controller;

use App\Entity\User;
use App\Component\Setting;
use App\Component\Privilege;
use App\Entity\Notification;
use App\Component\AuditTrail;
use App\Component\AttrAssignment;
use Viloveul\Pagination\Parameter;
use Viloveul\Pagination\ResultSet;
use Viloveul\Http\Contracts\Response;
use App\Validation\User as Validation;
use Viloveul\Database\Contracts\Query;
use Viloveul\Router\Contracts\Dispatcher;
use Viloveul\Http\Contracts\ServerRequest;
use Viloveul\Auth\Contracts\Authentication;
use Viloveul\Config\Contracts\Configuration;
use Viloveul\Pagination\Builder as Pagination;

class UserController
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
     * @param AuditTrail     $audit
     * @param Authentication $auth
     * @param Dispatcher     $router
     */
    public function __construct(
        ServerRequest $request,
        Response $response,
        Privilege $privilege,
        Configuration $config,
        Setting $setting,
        AuditTrail $audit,
        Authentication $auth,
        Dispatcher $router
    ) {
        $this->request = $request;
        $this->response = $response;
        $this->privilege = $privilege;
        $this->config = $config;
        $this->setting = $setting;
        $this->audit = $audit;
        $this->route = $router->routed();
        $this->user = $auth->getUser();
    }

    /**
     * @param  string  $id
     * @return mixed
     */
    public function approve(string $id)
    {
        if ($this->privilege->check($this->route->getName(), 'access') !== true) {
            return $this->response->withErrors(403, [
                "No direct access for route: {$this->route->getName()}",
            ]);
        }
        if ($user = User::where(['id' => $id])->find()) {
            $previous = $user->getAttributes();
            $user->status = 1;
            $user->updated_at = date('Y-m-d H:i:s');
            $user->save();
            $this->audit->update($user->id, 'user', $user->getAttributes(), $previous);
            return $this->response->withStatus(204);
        } else {
            return $this->response->withErrors(404, ['User not found']);
        }
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
            $user->id = str_uuid();
            $user->save();
            $relations = $attr->get('relations') ?: [];
            $user->sync('roleRelations', $relations);
            $this->audit->create($user->id, 'user');
            return $this->response->withStatus(201)->withPayload([
                'data' => $user,
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
        if ($user = User::where(['id' => $id])->find()) {
            $user->status = 3;
            $user->deleted_at = date('Y-m-d H:i:s');
            $user->save();
            $this->audit->delete($user->id, 'user');
            return $this->response->withStatus(204);
        } else {
            return $this->response->withErrors(404, ['User not found']);
        }
    }

    /**
     * @param  string  $id
     * @return mixed
     */
    public function detail(string $id)
    {
        if ($this->privilege->check($this->route->getName(), 'access', $id) !== true) {
            return $this->response->withErrors(403, [
                "No direct access for route: {$this->route->getName()}",
            ]);
        }
        if ($user = User::where(['id' => $id])->with('roles')->find()) {
            if (!$user->picture) {
                $user->picture = sprintf(
                    '%s/images/no-image-available.jpg',
                    $this->request->getBaseUrl()
                );
            }
            return $this->response->withPayload([
                'data' => $user,
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
        $pagination = new Pagination($parameter);
        $pagination->with(function ($conditions, $size, $page, $order, $sort) {
            $model = new User();
            foreach ($conditions as $key => $value) {
                $model->where([$key => "%{$value}%"], Query::OPERATOR_LIKE);
            }
            $total = $model->count();
            $result = $model->order($order, $sort === 'ASC' ? Query::SORT_ASC : Query::SORT_DESC)
                ->limit($size, ($page * $size) - $size)
                ->findAll();
            return new ResultSet($total, $result->toArray());
        });

        return $this->response->withPayload([
            'meta' => $pagination->getMeta(),
            'data' => $pagination->getData(),
        ]);
    }

    /**
     * @return mixed
     */
    public function me()
    {
        if ($id = $this->user->get('sub')) {
            if ($user = User::where(['id' => $id, 'status' => 1])->find()) {
                if (!$user->picture) {
                    $user->picture = sprintf(
                        '%s/images/no-image-available.jpg',
                        $this->request->getBaseUrl()
                    );
                }
                return $this->response->withPayload([
                    'data' => $user,
                    'meta' => [
                        'privileges' => $this->privilege->mine(),
                        'notification' => [
                            'total' => Notification::where(['receiver_id' => $id])->count(),
                            'unread' => Notification::where(['receiver_id' => $id, 'status' => 0])->count(),
                            'read' => Notification::where(['receiver_id' => $id, 'status' => 1])->count(),
                        ],
                    ],
                ]);
            } else {
                return $this->response->withErrors(401, ['User not actived.']);
            }
        } else {
            return $this->response->withErrors(401, ['Invalid Credentials']);
        }
    }

    /**
     * @param  string  $id
     * @return mixed
     */
    public function relations(string $id)
    {
        if ($this->privilege->check($this->route->getName(), 'access') !== true) {
            return $this->response->withErrors(403, [
                "No direct access for route: {$this->route->getName()}",
            ]);
        }
        if ($user = User::where(['id' => $id])->find()) {
            $body = $this->request->getBody()->getContents() ?: '[]';
            $relations = json_decode($body, true) ?: [];
            is_array($relations) and $user->sync('roleRelations', $relations);
            $this->privilege->load();
            return $this->response->withPayload([
                'data' => $user,
            ]);
        } else {
            return $this->response->withErrors(404, ['User not found']);
        }
    }

    /**
     * @param  string  $id
     * @return mixed
     */
    public function update(string $id)
    {
        if ($this->privilege->check($this->route->getName(), 'access', $id) !== true) {
            return $this->response->withErrors(403, [
                "No direct access for route: {$this->route->getName()}",
            ]);
        }
        if ($user = User::where(['id' => $id])->find()) {
            $attr = $this->request->loadPostTo(new AttrAssignment());
            $attr->get('password') or $attr->delete('password');
            $previous = $user->getAttributes();
            $params = array_merge(array_only($previous, ['name', 'picture', 'email', 'username', 'status']), $attr->getAttributes());
            $validator = new Validation($params, ['id' => $id]);
            if ($validator->validate('update')) {
                $data = array_only($params, [
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
                $user->save();
                $this->audit->update($user->id, 'user', $user->getAttributes(), $previous);
                return $this->response->withPayload([
                    'data' => $user,
                ]);
            } else {
                return $this->response->withErrors(400, $validator->errors());
            }
        } else {
            return $this->response->withErrors(404, ['User not found']);
        }
    }
}
