<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\UserProfile;
use App\Component\Privilege;
use App\Component\AttrAssignment;
use Viloveul\Http\Contracts\Response;
use Viloveul\Router\Contracts\Dispatcher;
use Viloveul\Http\Contracts\ServerRequest;

class UserProfileController
{
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
     * @param ServerRequest $request
     * @param Response      $response
     * @param Privilege     $privilege
     * @param Dispatcher    $router
     */
    public function __construct(
        ServerRequest $request,
        Response $response,
        Privilege $privilege,
        Dispatcher $router
    ) {
        $this->request = $request;
        $this->response = $response;
        $this->privilege = $privilege;
        $this->route = $router->routed();
    }

    /**
     * @param  string  $id
     * @return mixed
     */
    public function detail(string $id)
    {
        if ($user = User::where(['id' => $id, 'status' => 1])->find()) {
            return $this->response->withPayload([
                'data' => $user->profile->convertList('name', 'value'),
            ]);
        } else {
            return $this->response->withErrors(400, ['User not actived.']);
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
        if ($user = User::where(['id' => $id, 'status' => 1])->find()) {
            $attr = $this->request->loadPostTo(new AttrAssignment());
            foreach ($attr->getAttributes() as $name => $value) {
                $o = UserProfile::where(['name' => $name])->findOrNew([
                    'id' => str_uuid(),
                    'user_id' => $id,
                    'name' => $name,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
                $o->value = $value;
                $o->updated_at = date('Y-m-d H:i:s');
                $o->save();
            }
            return $this->response->withPayload([
                'data' => $user->profile->convertList('name', 'value'),
            ]);
        } else {
            return $this->response->withErrors(400, ['User not actived.']);
        }
    }
}
