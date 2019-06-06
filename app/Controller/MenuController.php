<?php

namespace App\Controller;

use App\Entity\Menu;
use App\Entity\MenuItem;
use App\Component\Helper;
use App\Component\Setting;
use App\Component\Privilege;
use App\Component\AuditTrail;
use App\Component\AttrAssignment;
use Viloveul\Pagination\Parameter;
use Viloveul\Pagination\ResultSet;
use Viloveul\Http\Contracts\Response;
use Viloveul\Database\Contracts\Query;
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
    protected $audit;

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
     * @param AuditTrail     $audit
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
        AuditTrail $audit,
        Authentication $auth,
        Dispatcher $router
    ) {
        $this->request = $request;
        $this->response = $response;
        $this->privilege = $privilege;
        $this->config = $config;
        $this->setting = $setting;
        $this->helper = $helper;
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
        ]);
        $menu = new Menu();
        foreach ($data as $key => $value) {
            $menu->{$key} = $value;
        }
        $menu->status = 1;
        $menu->author_id = $this->user->get('sub');
        $menu->created_at = date('Y-m-d H:i:s');
        $menu->id = str_uuid();
        $menu->save();
        $this->audit->create($menu->id, 'menu');
        return $this->response->withStatus(201)->withPayload([
            'data' => $menu,
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
        if ($menu = Menu::where(['id' => $id])->find()) {
            $menu->status = 3;
            $menu->deleted_at = date('Y-m-d H:i:s');
            $menu->save();
            $this->audit->delete($menu->id, 'menu');
            return $this->response->withStatus(204);
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
        if ($this->privilege->check($this->route->getName(), 'access') !== true) {
            return $this->response->withErrors(403, [
                "No direct access for route: {$this->route->getName()}",
            ]);
        }
        if ($tmp = Menu::where(['id' => $id])->find()) {
            $menu = $tmp->toArray();
            $results = MenuItem::select(['id', 'parent_id', 'label', 'icon', 'url'])
                ->where(['menu_id' => $id, 'status' => 1])
                ->order('order', Query::SORT_ASC)
                ->findAll();
            foreach ($results->toArray() ?: [] as $item) {
                $items[$item['parent_id']][] = $item;
            }
            $menu['items'] = $this->helper->parseRecursiveMenuItem($items ?: [], 0, true) ?: [];
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
        $pagination = new Pagination($parameter);
        $pagination->with(function ($conditions, $size, $page, $order, $sort) {
            $model = Menu::select(['id', 'label', 'description', 'author_id', 'status', 'created_at']);
            foreach ($conditions as $key => $value) {
                $model->where([$key => "%{$value}%"], Query::OPERATOR_LIKE);
            }
            $total = $model->count();
            $result = $model->order($order, $sort === 'ASC' ? Query::SORT_ASC : Query::SORT_DESC)
                ->limit($size, ($page * $size) - $size)
                ->findAll();
            return new ResultSet($total, $result->toArray());
        });
        return $this->response->withPayload([
            'meta' => $pagination->getMeta(),
            'data' => $pagination->getData(),
        ]);
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
        if ($menu = Menu::where(['id' => $id])->find()) {
            $attr = $this->request->loadPostTo(new AttrAssignment());
            $data = array_only($attr->getAttributes(), [
                'label',
                'description',
            ]);
            $previous = $menu->getAttributes();
            foreach ($data as $key => $value) {
                $menu->{$key} = $value;
            }
            $menu->status = 1;
            $menu->updated_at = date('Y-m-d H:i:s');
            $menu->save();
            if ($items = $this->helper->normalizeMenuItem($attr->get('items') ?: [])) {
                foreach ($items as $item) {
                    if ($object = MenuItem::where(['id' => $item['id'], 'menu_id' => $id])->find()) {
                        $object->parent_id = $item['parent_id'];
                        $object->order = $item['order'];
                        $object->save();
                    }
                }
            }
            $this->audit->update($id, 'menu', $menu->getAttributes(), $previous);
            return $this->response->withPayload([
                'data' => $menu,
            ]);
        } else {
            return $this->response->withErrors(404, ['Menu not found']);
        }
    }
}
