<?php

namespace App\Controller;

use App\Component\AttrAssignment;
use App\Component\Privilege;
use App\Entity\User;
use App\Validation\User as Validation;
use Viloveul\Auth\Contracts\Authentication;
use Viloveul\Auth\UserData;
use Viloveul\Http\Contracts\Response;
use Viloveul\Http\Contracts\ServerRequest;

class AuthController
{
    /**
     * @var mixed
     */
    protected $auth;

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
     * @param ServerRequest  $request
     * @param Response       $response
     * @param Privilege      $privilege
     * @param Authentication $auth
     */
    public function __construct(
        ServerRequest $request,
        Response $response,
        Privilege $privilege,
        Authentication $auth
    ) {
        $this->request = $request;
        $this->response = $response;
        $this->privilege = $privilege;
        $this->auth = $auth;
    }

    /**
     * @return mixed
     */
    public function login()
    {
        $attr = $this->request->loadPostTo(new AttrAssignment);
        $validator = new Validation($attr->getAttributes());
        if ($validator->validate('login')) {
            $data = array_only($attr->getAttributes(), ['username', 'password']);
            $user = User::where('username', $data['username'])->where('status', 1)->first();
            if ($user && password_verify($data['password'], $user->password)) {
                if (!$user->photo) {
                    $user->photo = sprintf(
                        '%s/images/no-image.jpg',
                        $this->request->getBaseUrl()
                    );
                }
                $this->privilege->clear();
                return $this->response->withPayload([
                    'data' => [
                        'token' => $this->auth->generate(
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
        $validator = new Validation($attr->getAttributes());
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
