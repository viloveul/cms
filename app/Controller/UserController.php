<?php

namespace App\Controller;

use App\Entity\User;
use App\Validation\User as UserValidation;
use Viloveul\Event\Contracts\Dispatcher as Event;
use Viloveul\Http\Contracts\ServerRequest as Request;
use Viloveul\Http\Contracts\Response;
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
        $request = $this->request->all() ?: [];
        $validator = new UserValidation($request);
        if ($validator->validate('store')) {
            $user = new User();
            $data = array_only($request, ['username', 'email', 'password']);
            foreach ($data as $key => $value) {
                $user->{$key} = $value;
            }
            $user->created_at = date('Y-m-d H:i:s');
            $user->password = password_hash(array_get($data, 'password'), PASSWORD_DEFAULT);
            if ($user->save()) {
                $this->response->setData([
                    'data' => [
                        'id' => $user->id,
                        'type' => 'user',
                        'attributes' => $user,
                    ],
                ]);
            } else {
                $this->response->setStatus(500);
                $this->response->addError(500, 'Something Wrong !!!', 'Error Processing Request');
            }
        } else {
            $this->response->setStatus(400);
            foreach ($validator->errors() as $key => $errors) {
                foreach ($errors as $error) {
                    $this->response->addError(400, 'Invalid Value', $error);
                }
            }
        }
        return $this->response;
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
            } else {
                $this->response->addError(500, 'Something Wrong !!!', 'Error Processing Request');
                $this->response->setStatus(500);
            }
        } else {
            $this->response->setStatus(404);
            $this->response->addError(404, 'Invalid Id', 'User not found');
        }
        return $this->response;
    }

    /**
     * @param $id
     */
    public function detail($id)
    {
        if ($user = User::where('id', $id)->first()) {
            $this->response->setData([
                'data' => [
                    'id' => $user->id,
                    'type' => 'user',
                    'attributes' => $user,
                ],
            ]);
        } else {
            $this->response->setStatus(404);
            $this->response->addError(404, 'Invalid Id', 'User not found');
        }
        return $this->response;
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
            $request = $this->request->all() ?: [];
            $validator = new UserValidation($request, [$id]);
            if ($validator->validate('edit')) {
                $data = array_only($request, ['username', 'email', 'status', 'deleted']);
                foreach ($data as $key => $value) {
                    $user->{$key} = $value;
                }
                $user->updated_at = date('Y-m-d H:i:s');
                if ($password = array_get($data, 'password')) {
                    $user->password = password_hash($password, PASSWORD_DEFAULT);
                }
                if ($user->save()) {
                    $this->response->setData([
                        'data' => [
                            'id' => $id,
                            'type' => 'user',
                            'attributes' => $user,
                        ],
                    ]);
                } else {
                    $this->response->setStatus(500);
                    $this->response->addError(500, 'Something Wrong !!!', 'Error Processing Request');
                }
            } else {
                $this->response->setStatus(400);
                foreach ($validator->errors() as $key => $errors) {
                    foreach ($errors as $error) {
                        $this->response->addError(400, 'Invalid Value', $error);
                    }
                }
            }
        } else {
            $this->response->setStatus(404);
            $this->response->addError(404, 'Invalid Id', 'User not found');
        }
        return $this->response;
    }
}
