<?php

namespace App\Controller;

use App\Component\AttrAssignment;
use App\Component\Privilege;
use App\Entity\Comment;
use App\Validation\Comment as CommentValidation;
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
     */
    public function __construct(ServerRequest $request, Response $response, Privilege $privilege, Dispatcher $router)
    {
        $this->request = $request;
        $this->response = $response;
        $this->privilege = $privilege;
        $this->route = $router->routed();
    }

    /**
     * @param  int     $id
     * @return mixed
     */
    public function delete(int $id)
    {
        if ($comment = Comment::where('id', $id)->first()) {
            if ($this->privilege->check($this->route->getName(), 'access', $comment->author_id) !== true) {
                return $this->response->withErrors(403, ["No direct access for route: {$this->route->getName()}"]);
            }
            $comment->status = 3;
            $comment->deleted_at = date('Y-m-d H:i:s');
            if ($comment->save()) {
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
                return $this->response->withErrors(403, ["No direct access for route: {$this->route->getName()}"]);
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
            return $this->response->withErrors(403, ["No direct access for route: {$this->route->getName()}"]);
        }
        $parameter = new Parameter('search', $_GET);
        $parameter->setBaseUrl('/api/v1/comment/index');
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
    public function publish(int $id)
    {
        if ($this->privilege->check($this->route->getName(), 'access') !== true) {
            return $this->response->withErrors(403, ["No direct access for route: {$this->route->getName()}"]);
        }
        if ($comment = Comment::where('id', $id)->first()) {
            $comment->status = 1;
            if ($comment->save()) {
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
    public function update(int $id)
    {
        if ($comment = Comment::where('id', $id)->first()) {
            if ($this->privilege->check($this->route->getName(), 'access', $comment->author_id) !== true) {
                return $this->response->withErrors(403, ["No direct access for route: {$this->route->getName()}"]);
            }
            $attr = $this->request->loadPostTo(new AttrAssignment);
            $validator = new CommentValidation($attr->getAttributes());
            if ($validator->validate('update')) {
                $data = array_only($attr->getAttributes(), ['parent_id', 'post_id', 'name', 'email', 'website', 'content', 'status']);
                foreach ($data as $key => $value) {
                    $comment->{$key} = $value;
                }
                $comment->updated_at = date('Y-m-d H:i:s');
                if ($comment->save()) {
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
