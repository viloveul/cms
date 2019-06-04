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
use App\Validation\Post as Validation;
use Viloveul\Database\Contracts\Query;
use Viloveul\Router\Contracts\Dispatcher;
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
     * @param  string  $id
     * @return mixed
     */
    public function approve(string $id)
    {
        if ($this->privilege->check($this->route->getName(), 'access') !== true) {
            return $this->response->withErrors(403, [
                "No direct access for route: {$this->route->getName()}",
            ]);
        }
        if ($post = Post::where(['id' => $id])->find()) {
            $previous = $post->getAttributes();
            $post->status = 1;
            $post->updated_at = date('Y-m-d H:i:s');
            $post->save();
            $this->audit->update($id, 'post', $post->getAttributes(), $previous);
            return $this->response->withStatus(204);
        } else {
            return $this->response->withErrors(404, ['Post not found']);
        }
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
        $validator = new Validation($attr->getAttributes());
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
            $post->description = substr(strip_tags($post->content), 0, 200);
            $post->status = (!$this->setting->get('moderations.post') || $this->privilege->check('post.approve')) ? 1 : 0;
            $post->id = str_uuid();
            $post->save();
            $tags = $attr->get('relations') ?: [];
            $post->sync('tagRelations', $tags);
            $post->load('tags');
            $this->audit->create($post->id, 'post');
            if ($users = $this->privilege->getRoleUsers('post.approve')) {
                $this->helper->sendNotification(
                    $users,
                    'New Post Created',
                    'new post. {post#' . $post->id . '}'
                );
            }

            return $this->response->withStatus(201)->withPayload([
                'data' => $post,
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
        if ($post = Post::where(['id' => $id])->find()) {
            if ($this->privilege->check($this->route->getName(), 'access', $post->author_id) !== true) {
                return $this->response->withErrors(403, [
                    "No direct access for route: {$this->route->getName()}",
                ]);
            }
            $post->status = 3;
            $post->deleted_at = date('Y-m-d H:i:s');
            $post->save();
            $this->audit->delete($id, 'post');
            return $this->response->withStatus(204);
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
        if ($post = Post::where(['id' => $id])->with(['author', 'tags'])->find()) {
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
        $model = Post::with('author');
        $model->select(['id', 'slug', 'author_id', 'title', 'description', 'created_at', 'status']);
        if ($this->privilege->check($this->route->getName(), 'access') !== true) {
            $model->where(['author_id' => $this->user->get('sub')]);
        }
        $parameter = new Parameter('search', $_GET);
        $pagination = new Pagination($parameter);
        $pagination->with(function ($conditions, $size, $page, $order, $sort) use ($model) {
            foreach ($conditions as $key => $value) {
                $model->where([$key => "%{$value}%"], Query::OPERATOR_LIKE);
            }
            $total = $model->count();
            $result = $model->orderBy($order, $sort === 'ASC' ? Query::SORT_ASC : Query::SORT_DESC)
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
        if ($post = Post::where(['id' => $id])->find()) {
            if ($this->privilege->check($this->route->getName(), 'access', $post->author_id) !== true) {
                return $this->response->withErrors(403, [
                    "No direct access for route: {$this->route->getName()}",
                ]);
            }
            $previous = $post->getAttributes();
            $attr = $this->request->loadPostTo(new AttrAssignment());
            $params = array_merge($previous, $attr->getAttributes());
            $validator = new Validation($params, compact('id'));
            if ($validator->validate('update')) {
                $data = array_only($params, [
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
                $post->description = substr(strip_tags($post->content), 0, 200);
                $post->status = (!$this->setting->get('moderations.post') || $this->privilege->check('post.approve')) ? 1 : 0;
                $post->save();
                $this->audit->update($id, 'post', $post->getAttributes(), $previous);
                $tags = $attr->get('relations') ?: [];
                $post->sync('tagRelations', $tags);
                $post->load('tags');
                if ($users = $this->privilege->getRoleUsers('post.approve')) {
                    $this->helper->sendNotification(
                        $users,
                        'New Post Updated',
                        'new updated post. {post#' . $post->id . '}'
                    );
                }
                return $this->response->withPayload([
                    'data' => $post,
                ]);
            } else {
                return $this->response->withErrors(400, $validator->errors());
            }
        } else {
            return $this->response->withErrors(404, ['Post not found']);
        }
    }
}
