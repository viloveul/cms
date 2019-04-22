<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\Comment;
use App\Component\Helper;
use App\Component\Setting;
use App\Component\Privilege;
use App\Component\AuditTrail;
use App\Component\AttrAssignment;
use Viloveul\Pagination\Parameter;
use Viloveul\Pagination\ResultSet;
use Viloveul\Http\Contracts\Response;
use Viloveul\Database\Contracts\Query;
use App\Validation\Comment as Validation;
use Viloveul\Router\Contracts\Dispatcher;
use Viloveul\Http\Contracts\ServerRequest;
use Viloveul\Auth\Contracts\Authentication;
use Viloveul\Config\Contracts\Configuration;
use Viloveul\Pagination\Builder as Pagination;

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
        $attributes = $this->request->loadPostTo(new AttrAssignment());
        $post = Post::select('comment_enabled')
            ->where(['id' => $attributes->get('post_id'), 'status' => 1])
            ->getResult();

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
                $comment->id = str_uuid();
                $comment->save();
                if ($users = $this->privilege->getRoleUsers('moderator:comment', 'group')) {
                    $this->helper->sendNotification(
                        $users,
                        'New Comment Posted',
                        $comment->name . ' send new comment. {comment#' . $comment->id . '}'
                    );
                }
                $this->audit->create($comment->id, 'comment');
                $comment->load('author');
                return $this->response->withPayload([
                    'data' => $comment,
                ]);
            } else {
                return $this->response->withErrors(400, $validator->errors());
            }
        } else {
            return $this->response->withErrors(404, ['Page not found.']);
        }
    }

    /**
     * @param  string  $id
     * @return mixed
     */
    public function delete(string $id)
    {
        if ($comment = Comment::where(['id' => $id])->getResult()) {
            if ($this->privilege->check($this->route->getName(), 'access', $comment->author_id) !== true) {
                return $this->response->withErrors(403, [
                    "No direct access for route: {$this->route->getName()}",
                ]);
            }
            $comment->status = 3;
            $comment->deleted_at = date('Y-m-d H:i:s');
            $comment->save();
            $this->audit->delete($comment->id, 'comment');
            return $this->response->withStatus(201);
        } else {
            return $this->response->withErrors(404, ['Comment not found']);
        }
    }

    /**
     * @param  string  $id
     * @return mixed
     */
    public function detail(string $id)
    {
        if ($comment = Comment::where(['id' => $id])->with(['author', 'post'])->getResult()) {
            if ($this->privilege->check($this->route->getName(), 'access', $comment->author_id) !== true) {
                return $this->response->withErrors(403, [
                    "No direct access for route: {$this->route->getName()}",
                ]);
            }
            return $this->response->withPayload([
                'data' => $comment,
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
        $pagination->with(function ($conditions, $size, $page, $order, $sort) {
            $model = Comment::with('post');
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
        if ($comment = Comment::where(['id' => $id])->getResult()) {
            if ($this->privilege->check($this->route->getName(), 'access', $comment->author_id) !== true) {
                return $this->response->withErrors(403, [
                    "No direct access for route: {$this->route->getName()}",
                ]);
            }
            $attr = $this->request->loadPostTo(new AttrAssignment());
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
                $comment->save();
                $this->audit->update($comment->id, 'comment', $comment->getAttributes(), $previous);
                return $this->response->withPayload([
                    'data' => $comment,
                ]);
            } else {
                return $this->response->withErrors(400, $validator->errors());
            }
        } else {
            return $this->response->withErrors(404, ['Comment not found']);
        }
    }
}
