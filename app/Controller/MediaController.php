<?php

namespace App\Controller;

use App\Component\AttrAssignment;
use App\Component\SlugCreation;
use App\Entity\Post;
use App\Entity\PostTag;
use App\Entity\Tag;
use App\Validation\Post as PostValidation;
use Viloveul\Http\Contracts\Response;
use Viloveul\Http\Contracts\ServerRequest;
use Viloveul\Pagination\Builder as Pagination;
use Viloveul\Pagination\Parameter;

class MediaController
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
    public function upload()
    {
        if ($validator->validate('insert')) {
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
            $post->deleted = 1;
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

    public function index()
    {
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
}
