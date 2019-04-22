<?php

namespace App\Controller;

use App\Entity\Link;
use App\Component\Helper;
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

class LinkController
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
    protected $user;

    /**
     * @param ServerRequest  $request
     * @param Response       $response
     * @param Privilege      $privilege
     * @param Configuration  $config
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
        Helper $helper,
        AuditTrail $audit,
        Authentication $auth,
        Dispatcher $router
    ) {
        $this->request = $request;
        $this->response = $response;
        $this->privilege = $privilege;
        $this->config = $config;
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
        $link->id = str_uuid();
        $link->save();
        $this->audit->create($link->id, 'link');
        return $this->response->withPayload([
            'data' => $link,
        ]);
    }

    /**
     * @param  string  $id
     * @return mixed
     */
    public function delete(string $id)
    {
        if ($link = Link::where(['id' => $id])->getResult()) {
            if ($this->privilege->check($this->route->getName(), 'access', $link->author_id) !== true) {
                return $this->response->withErrors(403, [
                    "No direct access for route: {$this->route->getName()}",
                ]);
            }
            $link->status = 3;
            $link->deleted_at = date('Y-m-d H:i:s');
            $link->save();
            $this->audit->delete($link->id, 'link');
            return $this->response->withStatus(201);
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
        if ($link = Link::where(['id' => $id])->getResult()) {
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
        $pagination->with(function ($conditions, $size, $page, $order, $sort) {
            $model = Link::newInstance();
            foreach ($conditions as $key => $value) {
                $model->where([$key => "%{$value}%"], Query::OPERATOR_LIKE);
            }
            $total = $model->count();
            $result = $model->orderBy($order, $sort === 'ASC' ? Query::SORT_ASC : Query::SORT_DESC)
                ->limit($size, ($page * $size) - $size)
                ->getResults();
            return new ResultSet($total, $result->toArray());
        });
        return $this->response->withPayload([
            'meta' => $pagination->getMeta(),
            'data' => $pagination->getData(),
            'links' => $pagination->getLinks(),
        ]);
    }

    /**
     * @param  string  $id
     * @return mixed
     */
    public function update(string $id)
    {
        if ($link = Link::where(['id' => $id])->getResult()) {
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
            $previous = $link->getAttributes();
            foreach ($data as $key => $value) {
                $link->{$key} = $value;
            }
            $link->status = 1;
            $link->updated_at = date('Y-m-d H:i:s');
            $link->save();
            $this->audit->update($id, 'link', $link->getAttributes(), $previous);
            return $this->response->withPayload([
                'data' => $link,
            ]);
        } else {
            return $this->response->withErrors(404, ['Link not found']);
        }
    }
}
