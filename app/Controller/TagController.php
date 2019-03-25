<?php

namespace App\Controller;

use App\Component\AttrAssignment;
use App\Component\Privilege;
use App\Component\Slug;
use App\Entity\Tag;
use App\Validation\Tag as Validation;
use Viloveul\Auth\Contracts\Authentication;
use Viloveul\Config\Contracts\Configuration;
use Viloveul\Http\Contracts\Response;
use Viloveul\Http\Contracts\ServerRequest;
use Viloveul\Pagination\Builder as Pagination;
use Viloveul\Pagination\Parameter;
use Viloveul\Router\Contracts\Dispatcher;

class TagController
{
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
     * @param Authentication $auth
     * @param Dispatcher     $router
     */
    public function __construct(
        ServerRequest $request,
        Response $response,
        Privilege $privilege,
        Configuration $config,
        Authentication $auth,
        Dispatcher $router
    ) {
        $this->request = $request;
        $this->response = $response;
        $this->privilege = $privilege;
        $this->config = $config;
        $this->user = $auth->getUser();
        $this->route = $router->routed();
    }

    /**
     * @return mixed
     */
    public function all()
    {
        $tag = Tag::select(['id', 'title', 'slug', 'type', 'parent_id']);
        foreach ($_GET as $key => $value) {
            $tag->where($key, $value);
        }
        $tag->where('status', 1);
        return $this->response->withPayload([
            'data' => $tag->get()->map(function ($tag) {
                return $tag->getAttributes();
            }),
        ]);
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
        if (!$attr->has('slug')) {
            $attr->slug = Slug::create()->generate(Tag::class, 'slug', $attr->get('title'), null);
        }
        $validator = new Validation($attr->getAttributes());
        if ($validator->validate('insert')) {
            $tag = new Tag();
            $data = array_only($attr->getAttributes(), [
                'title',
                'slug',
                'type',
                'parent_id',
                'author_id',
            ]);
            foreach ($data as $key => $value) {
                $tag->{$key} = $value;
            }
            $tag->author_id = $this->user->get('sub') ?: 0;
            $tag->created_at = date('Y-m-d H:i:s');
            if ($tag->save()) {
                return $this->response->withPayload([
                    'data' => $tag->getAttributes(),
                ]);
            } else {
                return $this->response->withErrors(500, ['Something Wrong !!!']);
            }
        } else {
            return $this->response->withErrors(400, $validator->errors());
        }
    }

    /**
     * @param  int     $id
     * @return mixed
     */
    public function delete(int $id)
    {
        if ($tag = Tag::where('id', $id)->first()) {
            if ($this->privilege->check($this->route->getName(), 'access', $tag->author_id) !== true) {
                return $this->response->withErrors(403, [
                    "No direct access for route: {$this->route->getName()}",
                ]);
            }
            $tag->status = 3;
            $tag->deleted_at = date('Y-m-d H:i:s');
            if ($tag->save()) {
                Tag::where('status', 1)->where('parent_id', $id)->update(['parent_id' => 0]);
                return $this->response->withStatus(201);
            } else {
                return $this->response->withErrors(500, ['Something Wrong !!!']);
            }
        } else {
            return $this->response->withErrors(404, ['Tag not found']);
        }
    }

    /**
     * @param  int     $id
     * @return mixed
     */
    public function detail(int $id)
    {
        if ($tag = Tag::where('id', $id)->with('childs')->first()) {
            if ($this->privilege->check($this->route->getName(), 'access', $tag->author_id) !== true) {
                return $this->response->withErrors(403, [
                    "No direct access for route: {$this->route->getName()}",
                ]);
            }
            return $this->response->withPayload([
                'data' => $tag,
            ]);
        } else {
            return $this->response->withErrors(404, ['Tag not found']);
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
        $parameter->setBaseUrl("{$this->config->basepath}/tag/index");
        $pagination = new Pagination($parameter);
        $pagination->prepare(function () {
            $model = Tag::query();
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
     * @param  int     $id
     * @return mixed
     */
    public function update(int $id)
    {
        if ($tag = Tag::where('id', $id)->first()) {
            if ($this->privilege->check($this->route->getName(), 'access', $tag->author_id) !== true) {
                return $this->response->withErrors(403, [
                    "No direct access for route: {$this->route->getName()}",
                ]);
            }
            $attr = $this->request->loadPostTo(new AttrAssignment);
            $validator = new Validation($attr->getAttributes(), compact('id'));
            if ($validator->validate('update')) {
                $data = array_only($attr->getAttributes(), [
                    'title',
                    'slug',
                    'type',
                    'parent_id',
                ]);
                foreach ($data as $key => $value) {
                    $tag->{$key} = $value;
                }
                $tag->status = 1;
                $tag->updated_at = date('Y-m-d H:i:s');
                if ($tag->save()) {
                    return $this->response->withPayload([
                        'data' => $tag->getAttributes(),
                    ]);
                } else {
                    return $this->response->withErrors(500, ['Something Wrong !!!']);
                }
            } else {
                return $this->response->withErrors(400, $validator->errors());
            }
        } else {
            return $this->response->withErrors(404, ['Tag not found']);
        }
    }
}
