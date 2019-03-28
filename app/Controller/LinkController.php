<?php

namespace App\Controller;

use App\Entity\Link;
use App\Component\Helper;
use App\Component\Privilege;
use App\Component\AttrAssignment;
use Viloveul\Pagination\Parameter;
use Viloveul\Http\Contracts\Response;
use Viloveul\Router\Contracts\Dispatcher;
use Viloveul\Http\Contracts\ServerRequest;
use Viloveul\Auth\Contracts\Authentication;
use Viloveul\Config\Contracts\Configuration;
use Viloveul\Pagination\Builder as Pagination;

class LinkController
{
    /**
     * @var mixed
     */
    protected $config;

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
     * @var mixed
     */
    protected $user;

    /**
     * @param ServerRequest  $request
     * @param Response       $response
     * @param Privilege      $privilege
     * @param Configuration  $config
     * @param Helper         $helper
     * @param Authentication $auth
     * @param Dispatcher     $router
     */
    public function __construct(
        ServerRequest $request,
        Response $response,
        Privilege $privilege,
        Configuration $config,
        Helper $helper,
        Authentication $auth,
        Dispatcher $router
    ) {
        $this->request = $request;
        $this->response = $response;
        $this->privilege = $privilege;
        $this->config = $config;
        $this->helper = $helper;
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
        ]);
        $link = new Link();
        foreach ($data as $key => $value) {
            $link->{$key} = $value;
        }
        $link->status = 1;
        $link->author_id = $this->user->get('sub');
        $link->created_at = date('Y-m-d H:i:s');
        $link->id = $this->helper->uuid();
        if ($link->save()) {
            return $this->response->withPayload([
                'data' => $link,
            ]);
        } else {
            return $this->response->withErrors(500, ['Something Wrong !!!']);
        }
    }

    /**
     * @param  string  $id
     * @return mixed
     */
    public function delete(string $id)
    {
        if ($link = Link::where('id', $id)->first()) {
            if ($this->privilege->check($this->route->getName(), 'access', $link->author_id) !== true) {
                return $this->response->withErrors(403, [
                    "No direct access for route: {$this->route->getName()}",
                ]);
            }
            $link->status = 3;
            $link->deleted_at = date('Y-m-d H:i:s');
            if ($link->save()) {
                return $this->response->withStatus(201);
            } else {
                return $this->response->withErrors(500, ['Something Wrong !!!']);
            }
        } else {
            return $this->response->withErrors(404, ['Link not found']);
        }
    }

    /**
     * @param  string  $id
     * @return mixed
     */
    public function detail(string $id)
    {
        if ($link = Link::where('id', $id)->first()) {
            return $this->response->withPayload([
                'data' => $link,
            ]);
        } else {
            return $this->response->withErrors(404, ['Link not found']);
        }
    }

    /**
     * @return mixed
     */
    public function index()
    {
        $parameter = new Parameter('search', $_GET);
        $parameter->setBaseUrl("{$this->config->basepath}/link/index");
        $pagination = new Pagination($parameter);
        $pagination->prepare(function () {
            $model = Link::query();
            $parameter = $this->getParameter();
            foreach ($parameter->getConditions() as $key => $value) {
                $model->where($key, 'like', "%{$value}%");
            }
            $this->total = $model->count();
            $this->data = $model->orderBy($parameter->getOrderBy(), $parameter->getSortOrder())
                ->skip(($parameter->getCurrentPage() * $parameter->getPageSize()) - $parameter->getPageSize())
                ->take($parameter->getPageSize())
                ->get()
                ->toArray();
        });
        return $this->response->withPayload($pagination->getResults());
    }

    /**
     * @param  string  $id
     * @return mixed
     */
    public function update(string $id)
    {
        if ($link = Link::where('id', $id)->first()) {
            if ($this->privilege->check($this->route->getName(), 'access', $link->author_id) !== true) {
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
            ]);
            foreach ($data as $key => $value) {
                $link->{$key} = $value;
            }
            $link->status = 1;
            $link->updated_at = date('Y-m-d H:i:s');
            if ($link->save()) {
                return $this->response->withPayload([
                    'data' => $link,
                ]);
            } else {
                return $this->response->withErrors(500, ['Something Wrong !!!']);
            }
        } else {
            return $this->response->withErrors(404, ['Link not found']);
        }
    }
}
