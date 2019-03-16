<?php

namespace App\Controller;

use App\Component\AttrAssignment;
use App\Component\AuditTrail;
use App\Component\Helper;
use App\Component\Privilege;
use App\Component\Setting;
use App\Entity\Comment;
use App\Entity\Post;
use App\Validation\Comment as Validation;
use Viloveul\Auth\Contracts\Authentication;
use Viloveul\Config\Contracts\Configuration;
use Viloveul\Http\Contracts\Response;
use Viloveul\Http\Contracts\ServerRequest;
use Viloveul\Pagination\Builder as Pagination;
use Viloveul\Pagination\Parameter;
use Viloveul\Router\Contracts\Dispatcher;

class CommentController
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
     * @param Setting        $setting
     * @param Helper         $helper
     * @param AuditTrail     $audit
     * @param Configuration  $config
     * @param Dispatcher     $router
     * @param Authentication $auth
     */
    public function __construct(
        ServerRequest $request,
        Response $response,
        Privilege $privilege,
        Setting $setting,
        Helper $helper,
        AuditTrail $audit,
        Configuration $config,
        Dispatcher $router,
        Authentication $auth
    ) {
        $this->request = $request;
        $this->response = $response;
        $this->privilege = $privilege;
        $this->setting = $setting;
        $this->helper = $helper;
        $this->audit = $audit;
        $this->config = $config;
        $this->route = $router->routed();
        $this->user = $auth->getUser();
    }

    /**
     * @return mixed
     */
    public function create()
    {
        $attributes = $this->request->loadPostTo(new AttrAssignment);
        $post = Post::select('comment_enabled')
            ->where('id', $attributes->get('post_id'))
            ->where('status', 1)
            ->first();

        if ($post && $post->comment_enabled != 0) {
            if ($id = $this->user->get('sub')) {
                $attributes['author_id'] = $id;
                $attributes['name'] = $this->user->get('name');
                $attributes['email'] = $this->user->get('email');
            }
            $validator = new Validation($attributes->getAttributes());
            if ($validator->validate('insert')) {
                $comment = new Comment();
                $data = array_only($attributes->getAttributes(), [
                    'parent_id',
                    'post_id',
                    'author_id',
                    'name',
                    'email',
                    'website',
                    'content',
                ]);
                foreach ($data as $key => $value) {
                    $comment->{$key} = $value;
                }
                $comment->status = (!$this->setting->get('moderations.comment') || $this->privilege->check('moderator:comment', 'group'));
                $comment->created_at = date('Y-m-d H:i:s');
                if ($comment->save()) {
                    if ($users = $this->privilege->getRoleUsers('moderator:comment', 'group')) {
                        $this->helper->sendNotification(
                            $users,
                            'New Comment Posted',
                            $comment->name . ' send new comment. {comment#' . $comment->id . '}'
                        );
                    }
                    $comment->load('author');
                    $this->audit->create($comment->id, 'comment');
                    return $this->response->withPayload([
                        'data' => [
                            'id' => $comment->id,
                            'type' => 'comment',
                            'attributes' => $comment->getAttributes(),
                            'relationships' => [
                                'author' => [
                                    'data' => $comment->author,
                                ],
                            ],
                        ],
                    ]);
                } else {
                    return $this->response->withErrors(500, ['Something Wrong !!!']);
                }
            } else {
                return $this->response->withErrors(400, $validator->errors());
            }
        } else {
            return $this->response->withErrors(404, ['Page not found.']);
        }
    }

    /**
     * @param  int     $id
     * @return mixed
     */
    public function delete(int $id)
    {
        if ($comment = Comment::where('id', $id)->first()) {
            if ($this->privilege->check($this->route->getName(), 'access', $comment->author_id) !== true) {
                return $this->response->withErrors(403, [
                    "No direct access for route: {$this->route->getName()}",
                ]);
            }
            $comment->status = 3;
            $comment->deleted_at = date('Y-m-d H:i:s');
            if ($comment->save()) {
                $this->audit->delete($comment->id, 'comment');
                return $this->response->withStatus(201);
            } else {
                return $this->response->withErrors(500, ['Something Wrong !!!']);
            }
        } else {
            return $this->response->withErrors(404, ['Comment not found']);
        }
    }

    /**
     * @param  int     $id
     * @return mixed
     */
    public function detail(int $id)
    {
        if ($comment = Comment::where('id', $id)->first()) {
            if ($this->privilege->check($this->route->getName(), 'access', $comment->author_id) !== true) {
                return $this->response->withErrors(403, [
                    "No direct access for route: {$this->route->getName()}",
                ]);
            }
            return $this->response->withPayload([
                'data' => [
                    'id' => $comment->id,
                    'type' => 'comment',
                    'attributes' => $comment->getAttributes(),
                ],
            ]);
        } else {
            return $this->response->withErrors(404, ['Comment not found']);
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
        $parameter->setBaseUrl("{$this->config->basepath}/comment/index");
        $pagination = new Pagination($parameter);
        $pagination->prepare(function () {
            $model = Comment::query()->with('post');
            $parameter = $this->getParameter();
            foreach ($parameter->getConditions() as $key => $value) {
                $model->where($key, 'like', "%{$value}%");
            }
            $this->total = $model->count();
            $this->data = $model->orderBy($parameter->getOrderBy(), $parameter->getSortOrder())
                ->skip(($parameter->getCurrentPage() * $parameter->getPageSize()) - $parameter->getPageSize())
                ->take($parameter->getPageSize())
                ->get()
                ->map(function ($comment) {
                    return [
                        'id' => $comment->id,
                        'type' => 'comment',
                        'attributes' => $comment->getAttributes(),
                        'relationships' => [
                            'post' => [
                                'data' => $comment->post,
                            ],
                        ],
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
        if ($comment = Comment::where('id', $id)->first()) {
            if ($this->privilege->check($this->route->getName(), 'access', $comment->author_id) !== true) {
                return $this->response->withErrors(403, [
                    "No direct access for route: {$this->route->getName()}",
                ]);
            }
            $attr = $this->request->loadPostTo(new AttrAssignment);
            $validator = new Validation($attr->getAttributes());
            if ($validator->validate('update')) {
                $previous = $comment->getAttributes();
                $data = array_only($attr->getAttributes(), [
                    'parent_id',
                    'post_id',
                    'name',
                    'email',
                    'website',
                    'content',
                    'status',
                ]);
                foreach ($data as $key => $value) {
                    $comment->{$key} = $value;
                }
                $comment->updated_at = date('Y-m-d H:i:s');
                if ($comment->save()) {
                    $this->audit->update($comment->id, 'comment', $comment->getAttributes(), $previous);
                    return $this->response->withPayload([
                        'data' => [
                            'id' => $id,
                            'type' => 'comment',
                            'attributes' => $comment->getAttributes(),
                        ],
                    ]);
                } else {
                    return $this->response->withErrors(500, ['Something Wrong !!!']);
                }
            } else {
                return $this->response->withErrors(400, $validator->errors());
            }
        } else {
            return $this->response->withErrors(404, ['Comment not found']);
        }
    }
}
