<?php

namespace App\Controller;

use App\Entity\Tag;
use Viloveul\Http\Contracts\Response;
use Viloveul\Http\Contracts\ServerRequest;
use Viloveul\Pagination\Builder as Pagination;
use Viloveul\Pagination\Parameter;

class TagController
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
     * @param  int     $id
     * @return mixed
     */
    public function delete(int $id)
    {
        if ($tag = Tag::where('id', $id)->first()) {
            $tag->deleted = 1;
            $tag->deleted_at = date('Y-m-d H:i:s');
            if ($tag->save()) {
                Tag::where('status', 1)->where('deleted', 0)->where('parent_id', $id)->update(['parent_id', 0]);
                return $this->response->withStatus(201);
            } else {
                return $this->response->withErrors(500, ['Something Wrong !!!']);
            }
        } else {
            return $this->response->withErrors(404, ['Tag not found']);
        }
    }
    public function create()
    {
        $data = $this->request->loadPostTo(new AttrAssignment);
        $validator = new PostValidation($data->getAttributes());
        if ($validator->validate('insert')) {
            $post = new Post();
            $data = array_only($data->getAttributes(), ['title', 'slug', 'content', 'description']);
            foreach ($data as $key => $value) {
                $post->{$key} = $value;
            }
            $post->created_at = date('Y-m-d H:i:s');
            if ($post->save()) {
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
     * @param  int     $id
     * @return mixed
     */
    public function detail(int $id)
    {
        if ($tag = Tag::where('id', $id)->with('childs')->first()) {
            return $this->response->withPayload([
                'data' => [
                    'id' => $tag->id,
                    'type' => 'tag',
                    'attributes' => $tag,
                ],
            ]);
        } else {
            return $this->response->withErrors(404, ['Tag not found']);
        }
    }

    public function index()
    {
        $parameter = new Parameter('search', $_GET);
        $parameter->setBaseUrl('/api/v1/tag/index');
        $pagination = new Pagination($parameter);
        $pagination->prepare(function () {
            $model = Tag::query();
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
