<?php

namespace App\Controller;

use App\Component\Privilege as PrivilegeComponent;
use App\Component\RequestAssignment;
use App\Component\Setting as SettingComponent;
use App\Entity\User;
use App\Validation\User as UserValidation;
use Viloveul\Auth\Contracts\Authentication;
use Viloveul\Auth\UserData;
use Viloveul\Http\Contracts\Response;
use Viloveul\Http\Contracts\ServerRequest;

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
     * @param ServerRequest $request
     * @param Response      $response
     */
    public function __construct(ServerRequest $request, Response $response, SettingComponent $setting)
    {
        $this->request = $request;
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
        $post = $this->request->loadPostTo(new RequestAssignment);
        $validator = new UserValidation($post->all());
        if ($validator->validate('login')) {
            $data = array_only($post->all(), ['username', 'password']);
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
                $privilege->clear();
                $response = $this->response->withPayload([
                    'data' => $token,
                ]);
            } else {
                $response = $this->response->withErrors(400, ['Invalid Credentials']);
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
     * @return mixed
     */
    public function register()
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
                    'data' => $user,
                ]);
            } else {
                $response = $this->response->withErrors(500, ['Something wrong !!!']);
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
}
