<?php

namespace App\Controller;

use App\Entity\Post;
use App\Component\Slug;
use App\Component\Helper;
use App\Component\Setting;
use App\Component\Privilege;
use App\Component\AuditTrail;
use App\Component\AttrAssignment;
use Viloveul\Pagination\Parameter;
use Viloveul\Pagination\ResultSet;
use Viloveul\Http\Contracts\Response;
use Viloveul\Router\Contracts\Dispatcher;
use App\Validation\Post as PostValidation;
use Viloveul\Http\Contracts\ServerRequest;
use Viloveul\Auth\Contracts\Authentication;
use Viloveul\Config\Contracts\Configuration;
use Viloveul\Pagination\Builder as Pagination;

class PostController
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
        if (!$attr->has('slug')) {
            $attr->slug = Slug::create()->generate(Post::class, 'slug', $attr->get('title'), null);
        }
        $validator = new PostValidation($attr->getAttributes());
        if ($validator->validate('insert')) {
            $post = new Post();
            $data = array_only($attr->getAttributes(), [
                'title',
                'cover',
                'slug',
                'type',
                'status',
                'content',
                'description',
                'comment_enabled',
            ]);
            foreach ($data as $key => $value) {
                $post->{$key} = $value;
            }
            $post->created_at = date('Y-m-d H:i:s');
            $post->author_id = $this->user->get('sub') ?: 0;
            $post->description = $post->content;
            if ($post->status == 1) {
                $post->status = (!$this->setting->get('moderations.post') || $this->privilege->check('moderator:post', 'group'));
            }
            $post->id = $this->helper->uuid();
            if ($post->save()) {
                $tags = $attr->get('relations') ?: [];
                $post->tags()->sync($tags);
                $post->load('tags');
                $this->audit->create($post->id, 'post');
                if ($users = $this->privilege->getRoleUsers('post.publish')) {
                    $this->helper->sendNotification(
                        $users,
                        'New Post Created',
                        $post->name . ' send new post. {post#' . $post->id . '}'
                    );
                }

                return $this->response->withPayload([
                    'data' => $post,
                ]);
            } else {
                return $this->response->withErrors(500, ['Something Wrong !!!']);
            }
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
        if ($post = Post::where('id', $id)->first()) {
            if ($this->privilege->check($this->route->getName(), 'access', $post->author_id) !== true) {
                return $this->response->withErrors(403, [
                    "No direct access for route: {$this->route->getName()}",
                ]);
            }
            $post->status = 3;
            $post->deleted_at = date('Y-m-d H:i:s');
            if ($post->save()) {
                $this->audit->delete($id, 'post');
                return $this->response->withStatus(201);
            } else {
                return $this->response->withErrors(500, ['Something Wrong !!!']);
            }
        } else {
            return $this->response->withErrors(404, ['Post not found']);
        }
    }

    /**
     * @param  string  $id
     * @return mixed
     */
    public function detail(string $id)
    {
        if ($post = Post::where('id', $id)->with(['author', 'tags'])->first()) {
            if ($this->privilege->check($this->route->getName(), 'access', $post->author_id) !== true) {
                return $this->response->withErrors(403, [
                    "No direct access for route: {$this->route->getName()}",
                ]);
            }
            return $this->response->withPayload([
                'data' => $post,
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
            return $this->response->withErrors(403, [
                "No direct access for route: {$this->route->getName()}",
            ]);
        }
        $parameter = new Parameter('search', $_GET);
        $parameter->setBaseUrl("{$this->config->basepath}/post/index");
        $pagination = new Pagination($parameter);
        $pagination->with(function ($conditions, $size, $page, $order, $sort) {
            $model = Post::query()->with('author');
            foreach ($conditions as $key => $value) {
                $model->where($key, 'like', "%{$value}%");
            }
            $total = $model->count();
            $result = $model->orderBy($order, $sort)->skip(($page * $size) - $size)->take($size)->get();
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
        if ($post = Post::where('id', $id)->first()) {
            if ($this->privilege->check($this->route->getName(), 'access', $post->author_id) !== true) {
                return $this->response->withErrors(403, [
                    "No direct access for route: {$this->route->getName()}",
                ]);
            }
            $attr = $this->request->loadPostTo(new AttrAssignment());
            $validator = new PostValidation($attr->getAttributes(), compact('id'));
            if ($validator->validate('update')) {
                $previous = $post->getAttributes();
                $data = array_only($attr->getAttributes(), [
                    'title',
                    'cover',
                    'slug',
                    'type',
                    'status',
                    'content',
                    'description',
                    'comment_enabled',
                ]);
                foreach ($data as $key => $value) {
                    $post->{$key} = $value;
                }
                $post->updated_at = date('Y-m-d H:i:s');
                $post->description = $post->content;
                if ($post->status == 1) {
                    $post->status = (!$this->setting->get('moderations.post') || $this->privilege->check('moderator:post', 'group'));
                }
                if ($post->save()) {
                    $this->audit->update($id, 'post', $post->getAttributes(), $previous);
                    $tags = $attr->get('relations') ?: [];
                    $post->tags()->sync($tags);
                    $post->load('tags');
                    return $this->response->withPayload([
                        'data' => $post,
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
