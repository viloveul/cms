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

class PostController
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
            if (!$post->description) {
                $post->description = $post->content;
            }
            if ($post->save()) {
                $tags = [];
                $tagIds = $attr->get('tags');
                foreach (Tag::whereIn('id', $tagIds)->get() as $tag) {
                    if (PostTag::create(['post_id' => $post->id, 'tag_id' => $tag->id, 'created_at' => date('Y-m-d H:i:s')])) {
                        $tags[] = $tag;
                    }
                }
                return $this->response->withPayload([
                    'data' => [
                        'id' => $post->id,
                        'type' => 'post',
                        'attributes' => array_merge($post->toArray(), compact('tags')),
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

    /**
     * @param $id
     */
    public function update(int $id)
    {
        if ($post = Post::where('id', $id)->first()) {
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
                if ($post->save()) {
                    $tags = [];
                    $tagIds = $attr->get('tags') ?: [];
                    PostTag::where('post_id', $id)->whereNotIn('tag_id', $tagIds)->delete();
                    foreach (Tag::whereIn('id', $tagIds)->get() as $tag) {
                        if (PostTag::firstOrCreate(['post_id' => $post->id, 'tag_id' => $tag->id], ['created_at' => date('Y-m-d H:i:s')])) {
                            $tags[] = $tag;
                        }
                    }
                    return $this->response->withPayload([
                        'data' => [
                            'id' => $post->id,
                            'type' => 'post',
                            'attributes' => array_merge($post->toArray(), compact('tags')),
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
