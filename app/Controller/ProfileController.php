<?php

namespace App\Controller;

use App\Entity\User;
use App\Component\Helper;
use App\Component\Privilege;
use App\Component\AttrAssignment;
use Viloveul\Http\Contracts\Response;
use Viloveul\Router\Contracts\Dispatcher;
use Viloveul\Http\Contracts\ServerRequest;

class ProfileController
{
    /**
     * @var mixed
     */
    protected $helper;

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
     * @param Helper        $helper
     * @param Dispatcher    $router
     */
    public function __construct(
        ServerRequest $request,
        Response $response,
        Privilege $privilege,
        Helper $helper,
        Dispatcher $router
    ) {
        $this->request = $request;
        $this->response = $response;
        $this->privilege = $privilege;
        $this->helper = $helper;
        $this->route = $router->routed();
    }

    /**
     * @param  string  $id
     * @return mixed
     */
    public function detail(string $id)
    {
        if ($user = User::where('id', $id)->where('status', 1)->first()) {
            return $this->response->withPayload([
                'data' => $user->profile->pluck('value', 'name'),
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
        if ($user = User::where('id', $id)->where('status', 1)->first()) {
            $attr = $this->request->loadPostTo(new AttrAssignment());
            foreach ($attr->getAttributes() as $name => $value) {
                $o = $user->profile()->firstOrNew(compact('name'), [
                    'id' => $this->helper->uuid(),
                ]);
                $o->value = $value;
                $o->last_modified = date('Y-m-d H:i:s');
                $o->save();
            }
            return $this->response->withPayload([
                'data' => $user->profile->pluck('value', 'name'),
            ]);
        } else {
            return $this->response->withErrors(400, ['User not actived.']);
        }
    }
}
