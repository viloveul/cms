<?php

namespace App\Controller;

use App\Entity\MenuItem;
use App\Component\Privilege;
use App\Component\AuditTrail;
use App\Component\AttrAssignment;
use Viloveul\Http\Contracts\Response;
use Viloveul\Router\Contracts\Dispatcher;
use Viloveul\Http\Contracts\ServerRequest;
use Viloveul\Auth\Contracts\Authentication;
use Viloveul\Config\Contracts\Configuration;

class MenuItemController
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
    protected $user;

    /**
     * @param ServerRequest  $request
     * @param Response       $response
     * @param Privilege      $privilege
     * @param Configuration  $config
     * @param AuditTrail     $audit
     * @param Authentication $auth
     * @param Dispatcher     $router
     */
    public function __construct(
        ServerRequest $request,
        Response $response,
        Privilege $privilege,
        Configuration $config,
        AuditTrail $audit,
        Authentication $auth,
        Dispatcher $router
    ) {
        $this->request = $request;
        $this->response = $response;
        $this->privilege = $privilege;
        $this->config = $config;
        $this->audit = $audit;
        $this->user = $auth->getUser();
        $this->route = $router->routed();
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
        $data = array_only($attr->getAttributes(), [
            'label',
            'description',
            'url',
            'icon',
            'role_id',
            'parent_id',
            'menu_id',
        ]);
        $item = new MenuItem();
        foreach ($data as $key => $value) {
            $item->{$key} = $value;
        }
        $item->status = 1;
        $item->author_id = $this->user->get('sub');
        $item->created_at = date('Y-m-d H:i:s');
        $item->id = str_uuid();
        $item->save();
        $this->audit->create($item->id, 'item');
        return $this->response->withPayload([
            'data' => $item,
        ]);
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
        if ($item = MenuItem::where(['id' => $id])->getResult()) {
            $item->status = 3;
            $item->deleted_at = date('Y-m-d H:i:s');
            $item->save();
            $this->audit->delete($item->id, 'item');

            $childs = $item->childs;
            foreach ($childs as $child) {
                $child->parent_id = $item->parent_id;
                $child->save();
            }

            return $this->response->withStatus(201);
        } else {
            return $this->response->withErrors(404, ['MenuItem not found']);
        }
    }

    /**
     * @param  string  $id
     * @return mixed
     */
    public function detail(string $id)
    {
        if ($this->privilege->check($this->route->getName(), 'access') !== true) {
            return $this->response->withErrors(403, [
                "No direct access for route: {$this->route->getName()}",
            ]);
        }
        if ($item = MenuItem::where(['id' => $id])->getResult()) {
            return $this->response->withPayload([
                'data' => $item,
            ]);
        } else {
            return $this->response->withErrors(404, ['MenuItem not found']);
        }
    }

    /**
     * @param  string  $id
     * @return mixed
     */
    public function update(string $id)
    {
        if ($this->privilege->check($this->route->getName(), 'access') !== true) {
            return $this->response->withErrors(403, [
                "No direct access for route: {$this->route->getName()}",
            ]);
        }
        if ($item = MenuItem::where(['id' => $id])->getResult()) {
            $attr = $this->request->loadPostTo(new AttrAssignment());
            $data = array_only($attr->getAttributes(), [
                'label',
                'description',
                'url',
                'icon',
                'role_id',
                'parent_id',
                'menu_id',
            ]);
            $previous = $item->getAttributes();
            foreach ($data as $key => $value) {
                $item->{$key} = $value;
            }
            $item->status = 1;
            $item->updated_at = date('Y-m-d H:i:s');
            $item->save();
            $this->audit->update($id, 'item', $item->getAttributes(), $previous);
            return $this->response->withPayload([
                'data' => $item,
            ]);
        } else {
            return $this->response->withErrors(404, ['MenuItem not found']);
        }
    }
}
