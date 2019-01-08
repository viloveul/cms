<?php

namespace App\Controller;

use App\Component\RequestAssignment;
use App\Entity\User;
use App\Validation\User as UserValidation;
use Viloveul\Event\Contracts\Dispatcher as Event;
use Viloveul\Http\Contracts\Response;
use Viloveul\Http\Contracts\ServerRequest as Request;
use Viloveul\Support\Pagination;

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
     * @param Request  $request
     * @param Response $response
     */
    public function __construct(Request $request, Response $response, Event $event)
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
        $post = $this->request->loadPostTo(new RequestAssignment);
        $validator = new UserValidation($post->all());
        if ($validator->validate('store')) {
            $user = new User();
            $data = array_only($post->all(), ['username', 'email', 'password']);
            foreach ($data as $key => $value) {
                $user->{$key} = $value;
            }
            $user->created_at = date('Y-m-d H:i:s');
            $user->password = password_hash(array_get($data, 'password'), PASSWORD_DEFAULT);
            if ($user->save()) {
                $response = $this->response->withPayload([
                    'data' => [
                        'id' => $user->id,
                        'type' => 'user',
                        'attributes' => $user,
                    ],
                ]);
            } else {
                $response = $this->response->withErrors(500, ['Something Wrong !!!']);
            }
        } else {
            $errors = [];
            foreach ($validator->errors() as $key => $errArray) {
                foreach ($errArray as $error) {
                    $errors[] = $error;
                }
            }
            $response = $this->response->withErrors(400, $errors);
        }
        return $response;
    }

    /**
     * @param $id
     */
    public function delete($id)
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
    public function detail($id)
    {
        if ($user = User::where('id', $id)->first()) {
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
    public function index(Request $request)
    {
        $pagination = new Pagination('search', $_GET);
        $pagination->setBaseUrl('/api/v1/user/index');
        $pagination->prepare(function (Pagination $pagination, array $conditions = []) {
            $model = new User();
            foreach ($conditions as $key => $value) {
                $model->where($key, 'LIKE', "%{$value}%");
            }
            $pagination->setTotal($model->count());
            $pagination->setData(
                $model
                    ->orderBy($pagination->getOrderBy(), $pagination->getSortOrder())
                    ->skip(($pagination->getCurrentPage() * $pagination->getPageSize()) - $pagination->getPageSize())
                    ->take($pagination->getPageSize())
                    ->get()
                    ->toArray()
            );
        });
        return $this->response->withPayload($pagination->results());
    }

    /**
     * @param $id
     */
    public function update($id)
    {
        if ($user = User::where('id', $id)->first()) {
            $post = $this->request->loadPostTo(new RequestAssignment);
            $validator = new UserValidation($post->all(), [$id]);
            if ($validator->validate('edit')) {
                $data = array_only($post->all(), ['username', 'email', 'status', 'deleted']);
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
                $errors = [];
                foreach ($validator->errors() as $key => $errArray) {
                    foreach ($errArray as $error) {
                        $errors[] = $error;
                    }
                }
                return $this->response->withErrors(400, $errors);
            }
        } else {
            return $this->response->withErrors(404, ['User not found']);
        }
    }
}
