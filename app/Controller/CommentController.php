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
     * setiap user sampai guest dapat membuat comment
     * @return mixed
     */
    public function create()
    {
        $attributes = $this->request->loadPostTo(new AttrAssignment());
        if ($id = $this->user->get('sub')) {
            $attributes['author_id'] = $id;
            $attributes['name'] = $this->user->get('name');
            $attributes['email'] = $this->user->get('email');
        }
        $validator = new Validation($attributes->getAttributes());
        if ($validator->validate('insert')) {
            $enabled = Post::select('comment_enabled')
                ->where(['id' => $attributes->get('post_id'), 'status' => 1])
                ->where(['created_at' => date('Y-m-d H:i:s')], Query::OPERATOR_LTE)
                ->getValue('comment_enabled');

            if ($enabled == 1) {
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
                $comment->status = !$this->setting->get('moderations.comment');
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
                return $this->response->withStatus(201)->withPayload([
                    'data' => $comment,
                ]);
            } else {
                return $this->response->withErrors(400, ['Page not found or comment was disabled.']);
            }
        } else {
            return $this->response->withErrors(400, $validator->errors());
        }
    }

    /**
     * hanya user yang punya access atau user yang membuat comment yang bisa delete
     * @param  string  $id
     * @return mixed
     */
    public function delete(string $id)
    {
        if ($comment = Comment::where(['id' => $id])->find()) {
            if ($this->privilege->check($this->route->getName(), 'access', $comment->author_id) !== true) {
                return $this->response->withErrors(403, [
                    "No direct access for route: {$this->route->getName()}",
                ]);
            }
            $comment->status = 3;
            $comment->deleted_at = date('Y-m-d H:i:s');
            $comment->save();
            $this->audit->delete($comment->id, 'comment');
            return $this->response->withStatus(204);
        } else {
            return $this->response->withErrors(404, ['Comment not found']);
        }
    }

    /**
     * hanya user yang punya access atau user yang membuat comment yang bisa melihat detail
     * @param  string  $id
     * @return mixed
     */
    public function detail(string $id)
    {
        if ($comment = Comment::where(['id' => $id])->with(['author', 'post'])->find()) {
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
        $model = Comment::with('post');
        if ($this->privilege->check($this->route->getName(), 'access') !== true) {
            $model->where(function($where) {
                $where->add(['author_id' => $this->user->get('sub')]);
                $where->add(['status' => 1], Query::OPERATOR_EQUAL, Query::SEPARATOR_OR);
            });
        }
        $parameter = new Parameter('search', $_GET);
        $pagination = new Pagination($parameter);
        $pagination->with(function ($conditions, $size, $page, $order, $sort) use ($model) {
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
        if ($comment = Comment::where(['id' => $id])->find()) {
            if ($this->privilege->check($this->route->getName(), 'access', $comment->author_id) !== true) {
                return $this->response->withErrors(403, [
                    "No direct access for route: {$this->route->getName()}",
                ]);
            }
            $attr = new AttrAssignment($comment->getAttributes());
            $this->request->loadPostTo($attr);
            $validator = new Validation($attr->getAttributes());
            if ($validator->validate('update')) {
                $previous = $comment->getAttributes();
                $data = array_only($attr->getAttributes(), [
                    'name',
                    'email',
                    'website',
                    'content',
                    'status',
                ]);
                foreach ($data as $key => $value) {
                    $comment->{$key} = $value;
                }
                $hasAccess = $this->privilege->check($this->route->getName(), 'access') === true;
                $needModeration = $this->setting->get('moderations.comment');
                // jika ada access update atau tidak perlu moderasi, maka status nya sesuai request
                // selain itu maka statusnya 0
                if ($hasAccess || !$needModeration) {
                    $comment->status = in_array($comment->status, [0, 1]) ? $comment->status : 1;
                } else {
                    $comment->status = 0;
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
