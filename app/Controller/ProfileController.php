<?php

namespace App\Controller;

use App\Component\AttrAssignment;
use App\Component\Privilege;
use App\Entity\User;
use Viloveul\Http\Contracts\Response;
use Viloveul\Http\Contracts\ServerRequest;
use Viloveul\Router\Contracts\Dispatcher;

class ProfileController
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
    public function __construct(ServerRequest $request, Response $response, Privilege $privilege, Dispatcher $router)
    {
        $this->request = $request;
        $this->response = $response;
        $this->privilege = $privilege;
        $this->route = $router->routed();
    }

    /**
     * @param  int     $id
     * @return mixed
     */
    public function detail(int $id)
    {
        if ($user = User::where('id', $id)->where('status', 1)->first()) {
            return $this->response->withPayload([
                'data' => [
                    'id' => $user->id,
                    'type' => 'profile',
                    'attributes' => $user->profile->pluck('value', 'name'),
                ],
            ]);
        } else {
            return $this->response->withErrors(400, ['User not actived.']);
        }
    }

    /**
     * @param  int     $id
     * @return mixed
     */
    public function update(int $id)
    {
        if ($this->privilege->check($this->route->getName(), 'access', $id) !== true) {
            return $this->response->withErrors(403, ["No direct access for route: {$this->route->getName()}"]);
        }
        if ($user = User::where('id', $id)->where('status', 1)->first()) {
            $attr = $this->request->loadPostTo(new AttrAssignment);
            foreach ($attr->getAttributes() as $name => $value) {
                $user->profile()->updateOrCreate(compact('name'), [
                    'value' => $value,
                    'last_modified' => date('Y-m-d H:i:s'),
                ]);
            }
            return $this->response->withPayload([
                'data' => [
                    'id' => $id,
                    'type' => 'profile',
                    'attributes' => $user->profile->pluck('value', 'name'),
                ],
            ]);
        } else {
            return $this->response->withErrors(400, ['User not actived.']);
        }
    }
}
