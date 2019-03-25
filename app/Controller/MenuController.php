<?php

namespace App\Controller;

use App\Component\AttrAssignment;
use App\Component\Helper;
use App\Component\Privilege;
use App\Component\Setting;
use App\Entity\Menu;
use Viloveul\Auth\Contracts\Authentication;
use Viloveul\Config\Contracts\Configuration;
use Viloveul\Http\Contracts\Response;
use Viloveul\Http\Contracts\ServerRequest;
use Viloveul\Pagination\Builder as Pagination;
use Viloveul\Pagination\Parameter;
use Viloveul\Router\Contracts\Dispatcher;

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
        $attr = $this->request->loadPostTo(new AttrAssignment);
        $data = array_only($attr->getAttributes(), [
            'label',
            'icon',
            'type',
            'description',
            'url',
            'status',
        ]);
        $menu = new Menu();
        foreach ($data as $key => $value) {
            $menu->{$key} = $value;
        }
        $menu->status = 1;
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
        if ($menu = Menu::where('id', $id)->first()) {
            if ($this->privilege->check($this->route->getName(), 'access', $menu->author_id) !== true) {
                return $this->response->withErrors(403, [
                    "No direct access for route: {$this->route->getName()}",
                ]);
            }
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
        if ($this->privilege->check($this->route->getName(), 'access') !== true) {
            return $this->response->withErrors(403, [
                "No direct access for route: {$this->route->getName()}",
            ]);
        }
        $parameter = new Parameter('search', $_GET);
        $parameter->setBaseUrl("{$this->config->basepath}/menu/index");
        $pagination = new Pagination($parameter);
        $pagination->prepare(function () {
            $model = Menu::query();
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
     * @param  string  $type
     * @param  Setting $setting
     * @return mixed
     */
    public function load(string $type = 'menu', Setting $setting)
    {
        $items = [];
        $model = Menu::select(['id', 'label', 'icon', 'url'])->where('type', $type)->where('status', 1)->get();
        foreach ($model->toArray() as $item) {
            $items[$item['id']] = $item;
        }
        $menus = $this->parseRecursive($setting->get('menu-' . $type) ?: [], $items);
        foreach ($items as $item) {
            $menus[] = $item;
        }
        return $this->response->withPayload([
            'data' => $menus,
        ]);
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
            $attr = $this->request->loadPostTo(new AttrAssignment);
            $data = array_only($attr->getAttributes(), [
                'label',
                'icon',
                'type',
                'description',
                'url',
                'status',
            ]);
            foreach ($data as $key => $value) {
                $menu->{$key} = $value;
            }
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

    /**
     * @param  array   $items
     * @param  array   $ids
     * @return mixed
     */
    protected function parseRecursive(array $items, array &$ids = [])
    {
        $menus = [];
        foreach ($items as $item) {
            $menu = (array) $item;
            if (array_key_exists($menu['id'], $ids)) {
                $chids = isset($menu['children']) ? $menu['children'] : [];
                $menu = array_merge($ids[$menu['id']], [
                    'children' => $chids,
                ]);
                unset($ids[$menu['id']]);
                $menu['children'] = $this->parseRecursive($menu['children'] ?: [], $ids);
                $menus[] = $menu;
            }
        }
        return $menus;
    }
}
