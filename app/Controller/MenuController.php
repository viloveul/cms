<?php

namespace App\Controller;

use App\Entity\Link;
use App\Entity\Menu;
use App\Component\Helper;
use App\Component\Setting;
use App\Component\Privilege;
use App\Component\AttrAssignment;
use Viloveul\Pagination\Parameter;
use Viloveul\Http\Contracts\Response;
use Viloveul\Router\Contracts\Dispatcher;
use Viloveul\Http\Contracts\ServerRequest;
use Viloveul\Auth\Contracts\Authentication;
use Viloveul\Config\Contracts\Configuration;
use Viloveul\Pagination\Builder as Pagination;

class MenuController
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
    protected $setting;

    /**
     * @var mixed
     */
    protected $user;

    /**
     * @param ServerRequest  $request
     * @param Response       $response
     * @param Privilege      $privilege
     * @param Configuration  $config
     * @param Setting        $setting
     * @param Helper         $helper
     * @param Authentication $auth
     * @param Dispatcher     $router
     */
    public function __construct(
        ServerRequest $request,
        Response $response,
        Privilege $privilege,
        Configuration $config,
        Setting $setting,
        Helper $helper,
        Authentication $auth,
        Dispatcher $router
    ) {
        $this->request = $request;
        $this->response = $response;
        $this->privilege = $privilege;
        $this->config = $config;
        $this->setting = $setting;
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
            'content',
        ]);
        $menu = new Menu();
        foreach ($data as $key => $value) {
            $menu->{$key} = $value;
        }
        $menu->status = 1;
        $menu->content = is_scalar($menu->content) ? $menu->content : json_encode($menu->content);
        $menu->author_id = $this->user->get('sub');
        $menu->created_at = date('Y-m-d H:i:s');
        $menu->id = $this->helper->uuid();
        if ($menu->save()) {
            return $this->response->withPayload([
                'data' => $menu,
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
        if ($menu = Menu::where('id', $id)->first()) {
            if ($this->privilege->check($this->route->getName(), 'access', $menu->author_id) !== true) {
                return $this->response->withErrors(403, [
                    "No direct access for route: {$this->route->getName()}",
                ]);
            }
            $menu->status = 3;
            $menu->deleted_at = date('Y-m-d H:i:s');
            if ($menu->save()) {
                return $this->response->withStatus(201);
            } else {
                return $this->response->withErrors(500, ['Something Wrong !!!']);
            }
        } else {
            return $this->response->withErrors(404, ['Menu not found']);
        }
    }

    /**
     * @param  string  $id
     * @return mixed
     */
    public function detail(string $id)
    {
        if ($tmp = Menu::where('id', $id)->first()) {
            if ($this->privilege->check($this->route->getName(), 'access', $tmp->author_id) !== true) {
                return $this->response->withErrors(403, [
                    "No direct access for route: {$this->route->getName()}",
                ]);
            }
            $menu = $tmp->toArray();
            $items = [];
            $links = Link::select(['id', 'label', 'icon', 'url'])->where('status', 1)->get();
            foreach ($links->toArray() ?: [] as $link) {
                $items[$link['id']] = $link;
            }
            $decoded = json_decode($menu['content'], true) ?: [];
            $menu['items'] = $this->helper->parseRecursive(is_array($decoded) ? $decoded : [], $items) ?: [];
            return $this->response->withPayload([
                'data' => $menu,
            ]);
        } else {
            return $this->response->withErrors(404, ['Menu not found']);
        }
    }

    /**
     * @return mixed
     */
    public function index()
    {
        $parameter = new Parameter('search', $_GET);
        $parameter->setBaseUrl("{$this->config->basepath}/menu/index");
        $pagination = new Pagination($parameter);
        $pagination->prepare(function () {
            $model = Menu::select(['id', 'label', 'description', 'author_id', 'status', 'created_at']);
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
        if ($menu = Menu::where('id', $id)->first()) {
            if ($this->privilege->check($this->route->getName(), 'access', $menu->author_id) !== true) {
                return $this->response->withErrors(403, [
                    "No direct access for route: {$this->route->getName()}",
                ]);
            }
            $attr = $this->request->loadPostTo(new AttrAssignment());
            $data = array_only($attr->getAttributes(), [
                'label',
                'description',
                'content',
            ]);
            foreach ($data as $key => $value) {
                $menu->{$key} = $value;
            }
            $menu->content = json_encode($menu->content ?: []);
            $menu->status = 1;
            $menu->updated_at = date('Y-m-d H:i:s');
            if ($menu->save()) {
                return $this->response->withPayload([
                    'data' => $menu,
                ]);
            } else {
                return $this->response->withErrors(500, ['Something Wrong !!!']);
            }
        } else {
            return $this->response->withErrors(404, ['Menu not found']);
        }
    }
}
