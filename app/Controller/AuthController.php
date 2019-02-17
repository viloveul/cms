<?php

namespace App\Controller;

use App\Component\AttrAssignment;
use App\Component\Privilege;
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
    public function __construct(ServerRequest $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    /**
     * @param  Authentication $auth
     * @param  Privilege      $privilege
     * @return mixed
     */
    public function login(Authentication $auth, Privilege $privilege)
    {
        $attr = $this->request->loadPostTo(new AttrAssignment);
        $validator = new UserValidation($attr->getAttributes());
        if ($validator->validate('login')) {
            $data = array_only($attr->getAttributes(), ['username', 'password']);
            $user = User::where('username', $data['username'])->where('status', 1)->first();
            if ($user && password_verify($data['password'], $user->password)) {
                if (!$user->photo) {
                    $uri = $this->request->getUri();
                    $user->photo = sprintf(
                        '%s://%s:%s/images/no-image.jpg',
                        $uri->getScheme(),
                        $uri->getHost(),
                        $uri->getPort()
                    );
                }
                $privilege->clear();
                return $this->response->withPayload([
                    'data' => [
                        'token' => $auth->generate(
                            new UserData([
                                'sub' => $user->id,
                                'email' => $user->email,
                                'name' => $user->name,
                                'picture' => $user->picture,
                            ])
                        ),
                        'id' => $user->id,
                        'type' => 'token',
                    ],
                ]);
            } else {
                return $this->response->withErrors(400, ['Invalid Credentials']);
            }
        } else {
            return $this->response->withErrors(400, $validator->errors());
        }
    }

    /**
     * @return mixed
     */
    public function register()
    {
        $attr = $this->request->loadPostTo(new AttrAssignment);
        $validator = new UserValidation($attr->getAttributes());
        if ($validator->validate('store')) {
            $user = new User();
            $data = array_only($attr->getAttributes(), ['email', 'name', 'username']);
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
                        'attributes' => $user->getAttributes(),
                    ],
                ]);
            } else {
                return $this->response->withErrors(500, ['Something wrong !!!']);
            }
        } else {
            return $this->response->withErrors(400, $validator->errors());
        }
    }
}
