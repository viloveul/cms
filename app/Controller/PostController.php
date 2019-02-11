<?php

namespace App\Controller;

use App\Component\AttrAssignment;
use App\Component\Privilege;
use App\Component\Setting;
use App\Component\SlugCreation;
use App\Entity\Post;
use App\Validation\Post as PostValidation;
use Viloveul\Auth\Contracts\Authentication;
use Viloveul\Http\Contracts\Response;
use Viloveul\Http\Contracts\ServerRequest;
use Viloveul\Pagination\Builder as Pagination;
use Viloveul\Pagination\Parameter;
use Viloveul\Router\Contracts\Dispatcher;

class PostController
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
    public function create(Setting $setting, Authentication $auth)
    {
        if ($this->privilege->check($this->route->getName(), 'access') !== true) {
            return $this->response->withErrors(403, ["No direct access for route: {$this->route->getName()}"]);
        }
        $attr = $this->request->loadPostTo(new AttrAssignment);
        if (!$attr->has('slug')) {
            $attr->slug = SlugCreation::create()->generate(Post::class, 'slug', $attr->get('title'), null);
        }
        $validator = new PostValidation($attr->getAttributes());
        if ($validator->validate('insert')) {
            $post = new Post();
            $data = array_only($attr->getAttributes(), ['title', 'cover', 'slug', 'type', 'status', 'content', 'description', 'comment_enabled']);
            foreach ($data as $key => $value) {
                $post->{$key} = $value;
            }
            $post->created_at = date('Y-m-d H:i:s');
            $post->author_id = $auth->getUser()->get('sub');
            if (!$post->description) {
                $post->description = $post->content;
            }
            if ($post->status == 1 && !$this->privilege->check('post.publish')) {
                $post->status = !$setting->get('moderations.post');
            }
            if ($post->save()) {
                $tags = $attr->get('relations') ?: [];
                $post->tags()->sync($tags);
                $post->load('tags');
                return $this->response->withPayload([
                    'data' => [
                        'id' => $post->id,
                        'type' => 'post',
                        'attributes' => $post,
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
     * @param $id
     */
    public function delete(int $id)
    {
        if ($post = Post::where('id', $id)->first()) {
            if ($this->privilege->check($this->route->getName(), 'access', $post->author_id) !== true) {
                return $this->response->withErrors(403, ["No direct access for route: {$this->route->getName()}"]);
            }
            $post->status = 3;
            $post->deleted_at = date('Y-m-d H:i:s');
            if ($post->save()) {
                return $this->response->withStatus(201);
            } else {
                return $this->response->withErrors(500, ['Something Wrong !!!']);
            }
        } else {
            return $this->response->withErrors(404, ['Post not found']);
        }
    }

    /**
     * @param $id
     */
    public function detail(int $id)
    {
        if ($post = Post::where('id', $id)->with(['author', 'tags'])->first()) {
            if ($this->privilege->check($this->route->getName(), 'access', $post->author_id) !== true) {
                return $this->response->withErrors(403, ["No direct access for route: {$this->route->getName()}"]);
            }
            return $this->response->withPayload([
                'data' => [
                    'id' => $post->id,
                    'type' => 'post',
                    'attributes' => $post,
                ],
            ]);
        } else {
            return $this->response->withErrors(404, ['Post not found']);
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
        $parameter->setBaseUrl('/api/v1/post/index');
        $pagination = new Pagination($parameter);
        $pagination->prepare(function () {
            $model = Post::query()->with('author');
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
    public function publish(int $id)
    {
        if ($this->privilege->check($this->route->getName(), 'access') !== true) {
            return $this->response->withErrors(403, ["No direct access for route: {$this->route->getName()}"]);
        }
        if ($post = Post::where('id', $id)->first()) {
            $post->status = 1;
            if ($post->save()) {
                return $this->response->withStatus(201);
            } else {
                return $this->response->withErrors(500, ['Something Wrong !!!']);
            }
        } else {
            return $this->response->withErrors(404, ['Post not found']);
        }
    }

    /**
     * @param $id
     */
    public function update(int $id, Setting $setting)
    {
        if ($post = Post::where('id', $id)->first()) {
            if ($this->privilege->check($this->route->getName(), 'access', $post->author_id) !== true) {
                return $this->response->withErrors(403, ["No direct access for route: {$this->route->getName()}"]);
            }
            $attr = $this->request->loadPostTo(new AttrAssignment);
            $validator = new PostValidation($attr->getAttributes(), compact('id'));
            if ($validator->validate('update')) {
                $data = array_only($attr->getAttributes(), ['title', 'cover', 'slug', 'type', 'status', 'content', 'description', 'comment_enabled']);
                foreach ($data as $key => $value) {
                    $post->{$key} = $value;
                }
                $post->updated_at = date('Y-m-d H:i:s');
                if (!$post->description) {
                    $post->description = $post->content;
                }
                if ($post->status == 1 && !$this->privilege->check('post.publish')) {
                    $post->status = !$setting->get('moderations.post');
                }
                if ($post->save()) {
                    $tags = $attr->get('relations') ?: [];
                    $post->tags()->sync($tags);
                    $post->load('tags');
                    return $this->response->withPayload([
                        'data' => [
                            'id' => $post->id,
                            'type' => 'post',
                            'attributes' => $post,
                        ],
                    ]);
                } else {
                    return $this->response->withErrors(500, ['Something Wrong !!!']);
                }
            } else {
                return $this->response->withErrors(400, $validator->errors());
            }
        } else {
            return $this->response->withErrors(404, ['Post not found']);
        }
    }
}
