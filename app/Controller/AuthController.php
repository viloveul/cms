<?php

namespace App\Controller;

use App\Component\Privilege as PrivilegeComponent;
use App\Component\Setting as SettingComponent;
use App\Entity\User;
use App\Validation\User as UserValidation;
use Viloveul\Auth\Contracts\Authentication;
use Viloveul\Auth\UserData;
use Viloveul\Http\Contracts\Request;
use Viloveul\Http\Contracts\Response;

class AuthController
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
     * @param Request  $request
     * @param Response $response
     */
    public function __construct(Request $request, Response $response, SettingComponent $setting)
    {
        $this->request = $request->request;
        $this->response = $response;
        $setting->load();
    }

    /**
     * @param  Authentication     $auth
     * @param  PrivilegeComponent $privilege
     * @return mixed
     */
    public function login(Authentication $auth, PrivilegeComponent $privilege)
    {
        $request = $this->request->all() ?: [];
        $validator = new UserValidation($request);
        if ($validator->validate('login')) {
            $data = array_only($request, ['username', 'password']);
            $user = User::where('username', $data['username'])->first();
            if ($user && $user->status == 1 && password_verify($data['password'], $user->password)) {
                $token = $auth->generate(
                    new UserData([
                        'sub' => $user->id,
                        'name' => $user->username,
                        'email' => $user->email,
                    ])
                );
                $auth->setToken($token);
                $this->response->setData([
                    'data' => $token,
                ]);
                $privilege->clear();
            } else {
                $this->response->setStatus(400);
                $this->response->addError(400, 'Invalid Credentials');
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
     * @return mixed
     */
    public function register()
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
                    'data' => $user,
                ]);
            } else {
                $this->response->setStatus(500);
                $this->response->addError(500, 'Something wrong !!!');
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
}
