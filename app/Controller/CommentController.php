<?php

namespace App\Controller;

use App\Component\AttrAssignment;
use App\Entity\Comment;
use App\Validation\Comment as CommentValidation;
use Viloveul\Http\Contracts\Response;
use Viloveul\Http\Contracts\ServerRequest;
use Viloveul\Pagination\Builder as Pagination;
use Viloveul\Pagination\Parameter;

class CommentController
{
    /**
     * @var mixed
     */
    protected $request;

    /**
     * @var mixed
     */
    protected $response;

    /**
     * @param ServerRequest $request
     * @param Response      $response
     */
    public function __construct(ServerRequest $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    /**
     * @return mixed
     */
    public function create()
    {
        $attr = $this->request->loadPostTo(new AttrAssignment);
        $validator = new CommentValidation($attr->getAttributes());
        if ($validator->validate('insert')) {
            $comment = new Comment();
            $data = array_only($attr->getAttributes(), ['parent_id', 'post_id', 'author_id', 'fullname', 'email', 'website', 'content']);
            foreach ($data as $key => $value) {
                $comment->{$key} = $value;
            }
            $comment->created_at = date('Y-m-d H:i:s');
            if ($comment->save()) {
                return $this->response->withPayload([
                    'data' => [
                        'id' => $comment->id,
                        'type' => 'comment',
                        'attributes' => $comment,
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
        if ($comment = Comment::where('id', $id)->first()) {
            $comment->deleted = 1;
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
            return $this->response->withPayload([
                'data' => [
                    'id' => $comment->id,
                    'type' => 'comment',
                    'attributes' => $comment,
                ],
            ]);
        } else {
            return $this->response->withErrors(404, ['Comment not found']);
        }
    }

    public function index()
    {
        $parameter = new Parameter('search', $_GET);
        $parameter->setBaseUrl('/api/v1/comment/index');
        $pagination = new Pagination($parameter);
        $pagination->prepare(function () {
            $model = Comment::query();
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
        if ($comment = Comment::where('id', $id)->first()) {
            $attr = $this->request->loadPostTo(new AttrAssignment);
            $validator = new CommentValidation($attr->getAttributes());
            if ($validator->validate('update')) {
                $data = array_only($attr->getAttributes(), ['parent_id', 'post_id', 'author_id', 'fullname', 'email', 'website', 'content', 'status', 'deleted']);
                foreach ($data as $key => $value) {
                    $comment->{$key} = $value;
                }
                $comment->updated_at = date('Y-m-d H:i:s');
                if ($comment->save()) {
                    return $this->response->withPayload([
                        'data' => [
                            'id' => $id,
                            'type' => 'comment',
                            'attributes' => $comment,
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
