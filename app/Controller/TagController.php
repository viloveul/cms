<?php

namespace App\Controller;

use App\Component\AttrAssignment;
use App\Component\Privilege;
use App\Component\SlugCreation;
use App\Entity\Tag;
use App\Validation\Tag as TagValidation;
use Viloveul\Auth\Contracts\Authentication;
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
                return [
                    'id' => $tag->id,
                    'attributes' => $tag->getAttributes(),
                ];
            }),
        ]);
    }

    /**
     * @param  Authentication $auth
     * @return mixed
     */
    public function create(Authentication $auth)
    {
        if ($this->privilege->check($this->route->getName(), 'access') !== true) {
            return $this->response->withErrors(403, ["No direct access for route: {$this->route->getName()}"]);
        }
        $attr = $this->request->loadPostTo(new AttrAssignment);
        if (!$attr->has('slug')) {
            $attr->slug = SlugCreation::create()->generate(Tag::class, 'slug', $attr->get('title'), null);
        }
        $validator = new TagValidation($attr->getAttributes());
        if ($validator->validate('insert')) {
            $tag = new Tag();
            $data = array_only($attr->getAttributes(), ['title', 'slug', 'type', 'parent_id', 'author_id']);
            foreach ($data as $key => $value) {
                $tag->{$key} = $value;
            }
            $tag->author_id = $auth->getUser()->get('sub');
            $tag->created_at = date('Y-m-d H:i:s');
            if ($tag->save()) {
                return $this->response->withPayload([
                    'data' => [
                        'id' => $tag->id,
                        'type' => 'tag',
                        'attributes' => $tag->getAttributes(),
                    ],
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
                return $this->response->withErrors(403, ["No direct access for route: {$this->route->getName()}"]);
            }
            $tag->status = 3;
            $tag->deleted_at = date('Y-m-d H:i:s');
            if ($tag->save()) {
                Tag::where('status', 1)->where('parent_id', $id)->update(['parent_id', 0]);
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
                return $this->response->withErrors(403, ["No direct access for route: {$this->route->getName()}"]);
            }
            return $this->response->withPayload([
                'data' => [
                    'id' => $tag->id,
                    'type' => 'tag',
                    'attributes' => $tag->getAttributes(),
                    'relationships' => [
                        'childs' => [
                            'data' => $tag->childs,
                        ],
                    ],
                ],
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
            return $this->response->withErrors(403, ["No direct access for route: {$this->route->getName()}"]);
        }
        $parameter = new Parameter('search', $_GET);
        $parameter->setBaseUrl('/api/v1/tag/index');
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
                ->map(function ($tag) {
                    return [
                        'id' => $tag->id,
                        'type' => 'tag',
                        'attributes' => $tag->getAttributes(),
                    ];
                })->toArray();
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
                return $this->response->withErrors(403, ["No direct access for route: {$this->route->getName()}"]);
            }
            $attr = $this->request->loadPostTo(new AttrAssignment);
            $validator = new TagValidation($attr->getAttributes(), compact('id'));
            if ($validator->validate('update')) {
                $data = array_only($attr->getAttributes(), ['title', 'slug', 'type', 'parent_id']);
                foreach ($data as $key => $value) {
                    $tag->{$key} = $value;
                }
                $tag->updated_at = date('Y-m-d H:i:s');
                if ($tag->save()) {
                    return $this->response->withPayload([
                        'data' => [
                            'id' => $id,
                            'type' => 'tag',
                            'attributes' => $tag->getAttributes(),
                        ],
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
