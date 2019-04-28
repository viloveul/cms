<?php

namespace App\Controller;

use App\Entity\Tag;
use App\Component\Slug;
use App\Component\Privilege;
use App\Component\AuditTrail;
use App\Component\AttrAssignment;
use Viloveul\Pagination\Parameter;
use Viloveul\Pagination\ResultSet;
use App\Validation\Tag as Validation;
use Viloveul\Http\Contracts\Response;
use Viloveul\Database\Contracts\Query;
use Viloveul\Router\Contracts\Dispatcher;
use Viloveul\Http\Contracts\ServerRequest;
use Viloveul\Auth\Contracts\Authentication;
use Viloveul\Config\Contracts\Configuration;
use Viloveul\Pagination\Builder as Pagination;

class TagController
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
            $tag->id = str_uuid();
            $tag->save();
            $this->audit->create($tag->id, 'post');
            return $this->response->withStatus(201)->withPayload([
                'data' => $tag->getAttributes(),
            ]);
        } else {
            return $this->response->withErrors(400, $validator->errors());
        }
    }

    /**
     * @param  string  $id
     * @return mixed
     */
    public function delete(string $id)
    {
        if ($tag = Tag::where(['id' => $id])->getResult()) {
            if ($this->privilege->check($this->route->getName(), 'access', $tag->author_id) !== true) {
                return $this->response->withErrors(403, [
                    "No direct access for route: {$this->route->getName()}",
                ]);
            }
            $tag->status = 3;
            $tag->deleted_at = date('Y-m-d H:i:s');
            $tag->save();
            $this->audit->delete($id, 'tag');
            return $this->response->withStatus(204);
        } else {
            return $this->response->withErrors(404, ['Tag not found']);
        }
    }

    /**
     * @param  string  $id
     * @return mixed
     */
    public function detail(string $id)
    {
        if ($tag = Tag::where(['id' => $id])->with('childs')->getResult()) {
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
        $model = new Tag();
        if ($this->privilege->check($this->route->getName(), 'access') !== true) {
            $model->where(['author_id' => $this->user->get('sub')]);
        }
        $parameter = new Parameter('search', $_GET);
        $parameter->setBaseUrl("{$this->config->basepath}/tag/index");
        $pagination = new Pagination($parameter);
        $pagination->with(function ($conditions, $size, $page, $order, $sort) use ($model) {
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
        if ($tag = Tag::where(['id' => $id])->getResult()) {
            if ($this->privilege->check($this->route->getName(), 'access', $tag->author_id) !== true) {
                return $this->response->withErrors(403, [
                    "No direct access for route: {$this->route->getName()}",
                ]);
            }
            $attr = $this->request->loadPostTo(new AttrAssignment());
            $previous = $tag->getAttributes();
            $params = array_merge($previous, $attr->getAttributes());
            $validator = new Validation($params, compact('id'));
            if ($validator->validate('update')) {
                $data = array_only($params, [
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
                $tag->save();
                $this->audit->update($id, 'tag', $post->getAttributes(), $previous);
                return $this->response->withPayload([
                    'data' => $tag->getAttributes(),
                ]);
            } else {
                return $this->response->withErrors(400, $validator->errors());
            }
        } else {
            return $this->response->withErrors(404, ['Tag not found']);
        }
    }
}
