<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Post;
use Viloveul\Http\Contracts\Response;
use Viloveul\Http\Contracts\ServerRequest;
use Viloveul\Pagination\Builder as Pagination;
use Viloveul\Pagination\Parameter;
use App\Component\AttrAssignment;

class BlogController
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
     * @param string $archive
     */
    public function archive(string $archive)
    {
        $model = Post::query();
        $parameter = new Parameter('search', $_GET);
        $parameter->setBaseUrl("/api/v1/blog/archive/{$archive}");
        $pagination = new Pagination($parameter);
        $pagination->prepare(function () use ($model, $archive) {
            $parameter = $this->getParameter();
            foreach ($parameter->getConditions() as $key => $value) {
                $model->where($key, 'like', "%{$value}%");
            }
            $model->where('type', 'post');
            $model->where('deleted', 0);
            $model->where('status', 1);

            $model->withCount('comments');
            $model->with([
                'author' => function($query) {
                    $query->select(['id', 'username', 'email']);
                },
                'tags' => function($query) {
                    $query->select(['tag_id', 'post_id', 'title', 'type', 'slug']);
                    $query->where('type', 'tag');
                }
            ]);

            $model->whereHas('tags', function ($query) use ($archive) {
                $query->where('slug', $archive);
                $query->where('status', 1);
                $query->where('deleted', 0);
            });

            $this->total = $model->count();
            $this->data = $model->orderBy($parameter->getOrderBy(), $parameter->getSortOrder())
                ->skip(($parameter->getCurrentPage() * $parameter->getPageSize()) - $parameter->getPageSize())
                ->take($parameter->getPageSize())
                ->get()
                ->toArray();
        });
        $results = $pagination->getResults();
        return $this->response->withPayload($results);
    }

    /**
     * @param string $author
     */
    public function author(string $author)
    {
        $model = Post::query();
        $parameter = new Parameter('search', $_GET);
        $parameter->setBaseUrl("/api/v1/blog/author/{$author}");
        $pagination = new Pagination($parameter);
        $pagination->prepare(function () use ($model, $author) {
            $parameter = $this->getParameter();
            foreach ($parameter->getConditions() as $key => $value) {
                $model->where($key, 'like', "%{$value}%");
            }
            $model->where('type', 'post');
            $model->where('deleted', 0);
            $model->where('status', 1);

            $model->withCount('comments');
            $model->with([
                'author' => function($query) {
                    $query->select(['id', 'username', 'email']);
                },
                'tags' => function($query) {
                    $query->select(['tag_id', 'post_id', 'title', 'type', 'slug']);
                    $query->where('type', 'tag');
                }
            ]);

            $model->whereHas('author', function ($query) use ($author) {
                $query->where('username', $author);
                $query->where('deleted', 0);
                $query->where('status', 1);
            });

            $this->total = $model->count();
            $this->data = $model->orderBy($parameter->getOrderBy(), $parameter->getSortOrder())
                ->skip(($parameter->getCurrentPage() * $parameter->getPageSize()) - $parameter->getPageSize())
                ->take($parameter->getPageSize())
                ->get()
                ->toArray();
        });
        $results = $pagination->getResults();
        return $this->response->withPayload($results);
    }

    /**
     * @param int $post_id
     */
    public function comment(int $post_id)
    {

    }

    /**
     * @param int $post_id
     */
    public function comments(int $post_id)
    {
        $model = Comment::query();
        $parameter = new Parameter('search', $_GET);
        $parameter->setBaseUrl("/api/v1/blog/comments/{$post_id}");
        $pagination = new Pagination($parameter);
        $pagination->prepare(function () use ($model, $post_id) {
            $parameter = $this->getParameter();
            foreach ($parameter->getConditions() as $key => $value) {
                $model->where($key, 'like', "%{$value}%");
            }
            $model->where('deleted', 0);
            $model->where('status', 1);
            $model->where('post_id', $post_id);

            $model->with('author');

            $this->total = $model->count();
            $this->data = $model->orderBy($parameter->getOrderBy(), $parameter->getSortOrder())
                ->skip(($parameter->getCurrentPage() * $parameter->getPageSize()) - $parameter->getPageSize())
                ->take($parameter->getPageSize())
                ->get()
                ->toArray();
        });
        $results = $pagination->getResults();

        return $this->response->withPayload($results);
    }

    /**
     * @param  string  $slug
     * @return mixed
     */
    public function detail(string $slug)
    {
        if ($post = Post::where('slug', $slug)->where('deleted', 0)->where('status', 1)->with(['author', 'tags'])->first()) {
            return $this->response->withPayload([
                'data' => [
                    'id' => $post->id,
                    'type' => 'post',
                    'attributes' => $post,
                ],
            ]);
        }
        return $this->response->withErrors(404, ['Page not found.']);
    }

    public function index()
    {
        $model = Post::query()->select(['id', 'author_id', 'created_at', 'title', 'description', 'slug', 'type']);
        $parameter = new Parameter('search', $_GET);
        $parameter->setBaseUrl('/api/v1/blog/index');
        $pagination = new Pagination($parameter);
        $pagination->prepare(function () use ($model) {
            $parameter = $this->getParameter();
            foreach ($parameter->getConditions() as $key => $value) {
                $model->where($key, 'like', "%{$value}%");
            }
            $model->where('type', 'post');
            $model->where('deleted', 0);
            $model->where('status', 1);

            $model->withCount('comments');
            $model->with([
                'author' => function($query) {
                    $query->select(['id', 'username', 'email']);
                },
                'tags' => function($query) {
                    $query->select(['tag_id', 'post_id', 'title', 'type', 'slug']);
                    $query->where('type', 'tag');
                }
            ]);

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
