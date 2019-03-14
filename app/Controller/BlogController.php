<?php

namespace App\Controller;

use App\Component\Setting;
use App\Entity\Comment;
use App\Entity\Post;
use App\Entity\Tag;
use App\Entity\User;
use Viloveul\Config\Contracts\Configuration;
use Viloveul\Http\Contracts\Response;
use Viloveul\Http\Contracts\ServerRequest;
use Viloveul\Pagination\Builder as Pagination;
use Viloveul\Pagination\Parameter;

class BlogController
{
    /**
     * @var mixed
     */
    protected $config;

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
    protected $setting;

    /**
     * @param ServerRequest $request
     * @param Response      $response
     * @param Setting       $setting
     * @param Configuration $config
     */
    public function __construct(
        ServerRequest $request,
        Response $response,
        Setting $setting,
        Configuration $config
    ) {
        $this->request = $request;
        $this->response = $response;
        $this->setting = $setting;
        $this->config = $config;
    }

    /**
     * @param string $slug
     */
    public function archive(string $slug)
    {
        if ($archive = Tag::where('slug', $slug)->first()) {
            $model = Post::query()->select([
                'id',
                'author_id',
                'created_at',
                'title',
                'description',
                'slug',
                'type',
                'comment_enabled',
            ]);
            $parameter = new Parameter('search', $_GET);
            $parameter->setBaseUrl("{$this->config->basepath}/blog/archive/{$slug}");
            $pagination = new Pagination($parameter);
            $pagination->prepare(function () use ($model, $slug) {
                $parameter = $this->getParameter();
                foreach ($parameter->getConditions() as $key => $value) {
                    $model->where($key, 'like', "%{$value}%");
                }
                $model->where('type', 'post');
                $model->where('status', 1);

                $model->withCount('comments');
                $model->with([
                    'author' => function ($query) {
                        $query->select(['id', 'email', 'username', 'name', 'status']);
                    },
                    'tags' => function ($query) {
                        $query->select(['tag_id', 'post_id', 'title', 'type', 'slug']);
                    },
                ]);

                $model->whereHas('tags', function ($query) use ($slug) {
                    $query->where('slug', $slug);
                    $query->where('status', 1);
                });

                $this->total = $model->count();
                $this->data = $model->orderBy($parameter->getOrderBy(), $parameter->getSortOrder())
                    ->skip(($parameter->getCurrentPage() * $parameter->getPageSize()) - $parameter->getPageSize())
                    ->take($parameter->getPageSize())
                    ->get()
                    ->map(function ($post) {
                        return [
                            'id' => $post->id,
                            'type' => 'post',
                            'attributes' => $post->getAttributes(),
                            'relationships' => [
                                'tags' => [
                                    'data' => $post->tags,
                                ],
                                'author' => [
                                    'data' => $post->author,
                                ],
                            ],
                        ];
                    })->toArray();
            });
            $results = $pagination->getResults();
            $results['meta']['archive'] = $archive;
            return $this->response->withPayload($results);
        }
        return $this->response->withErrors(404, ["Archive {$slug} not found."]);
    }

    /**
     * @param string $name
     */
    public function author(string $name)
    {
        if ($author = User::where('username', $name)->first()) {
            $model = Post::query()->select([
                'id',
                'author_id',
                'created_at',
                'title',
                'description',
                'slug',
                'type',
                'comment_enabled',
            ]);
            $parameter = new Parameter('search', $_GET);
            $parameter->setBaseUrl("{$this->config->basepath}/blog/author/{$name}");
            $pagination = new Pagination($parameter);
            $pagination->prepare(function () use ($model, $author) {
                $parameter = $this->getParameter();
                foreach ($parameter->getConditions() as $key => $value) {
                    $model->where($key, 'like', "%{$value}%");
                }
                $model->where('status', 1);
                $model->where('author_id', $author->id);

                $model->withCount('comments');
                $model->with([
                    'author' => function ($query) {
                        $query->select(['id', 'email', 'username', 'name', 'status']);
                    },
                    'tags' => function ($query) {
                        $query->select(['tag_id', 'post_id', 'title', 'type', 'slug']);
                    },
                ]);

                $this->total = $model->count();
                $this->data = $model->orderBy($parameter->getOrderBy(), $parameter->getSortOrder())
                    ->skip(($parameter->getCurrentPage() * $parameter->getPageSize()) - $parameter->getPageSize())
                    ->take($parameter->getPageSize())
                    ->get()
                    ->map(function ($post) {
                        return [
                            'id' => $post->id,
                            'type' => 'post',
                            'attributes' => $post->getAttributes(),
                            'relationships' => [
                                'tags' => [
                                    'data' => $post->tags,
                                ],
                                'author' => [
                                    'data' => $post->author,
                                ],
                            ],
                        ];
                    })->toArray();
            });
            $results = $pagination->getResults();
            $results['meta']['author'] = $author;
            $results['meta']['profile'] = $author->profile->pluck('value', 'name');
            return $this->response->withPayload($results);
        }
        return $this->response->withErrors(404, ["Author {$name} not found."]);
    }

    /**
     * @param int $post_id
     */
    public function comments(int $post_id)
    {
        if ($post = Post::where('id', $post_id)->where('status', 1)->where('comment_enabled', 1)->first()) {
            $model = Comment::query();
            $parameter = new Parameter('search', $_GET);
            $parameter->setBaseUrl("{$this->config->basepath}/blog/comments/{$post_id}");
            $pagination = new Pagination($parameter);
            $pagination->prepare(function () use ($model, $post_id) {
                $parameter = $this->getParameter();
                foreach ($parameter->getConditions() as $key => $value) {
                    $model->where($key, 'like', "%{$value}%");
                }
                $model->where('status', 1);
                $model->where('post_id', $post_id);
                $model->with([
                    'author' => function ($query) {
                        $query->select(['id', 'email', 'username', 'name', 'status']);
                    },
                ]);

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
                                'author' => [
                                    'data' => $comment->author,
                                ],
                            ],
                        ];
                    })->toArray();
            });
            $results = $pagination->getResults();
            return $this->response->withPayload($results);
        } else {
            return $this->response->withErrors(404, ['Comments not found or not enabled.']);
        }
    }

    /**
     * @param  string  $slug
     * @return mixed
     */
    public function detail(string $slug)
    {
        if ($post = Post::where('slug', $slug)->where('status', 1)->with(['author', 'tags'])->first()) {
            return $this->response->withPayload([
                'data' => [
                    'id' => $post->id,
                    'type' => 'post',
                    'attributes' => $post->getAttributes(),
                    'relationships' => [
                        'tags' => [
                            'data' => $post->tags,
                        ],
                        'author' => [
                            'data' => $post->author,
                        ],
                    ],
                ],
            ]);
        }
        return $this->response->withErrors(404, ['Page not found.']);
    }

    public function index()
    {
        $model = Post::query()->select([
            'id',
            'author_id',
            'created_at',
            'title',
            'description',
            'slug',
            'type',
            'comment_enabled',
        ]);
        $parameter = new Parameter('search', $_GET);
        $parameter->setBaseUrl("{$this->config->basepath}/blog/index");
        $pagination = new Pagination($parameter);
        $pagination->prepare(function () use ($model) {
            $parameter = $this->getParameter();
            foreach ($parameter->getConditions() as $key => $value) {
                $model->where($key, 'like', "%{$value}%");
            }
            $model->where('type', 'post');
            $model->where('status', 1);

            $model->withCount('comments');
            $model->with([
                'author' => function ($query) {
                    $query->select(['id', 'email', 'username', 'name', 'status']);
                },
                'tags' => function ($query) {
                    $query->select(['tag_id', 'post_id', 'title', 'type', 'slug']);
                },
            ]);

            $this->total = $model->count();
            $this->data = $model->orderBy($parameter->getOrderBy(), $parameter->getSortOrder())
                ->skip(($parameter->getCurrentPage() * $parameter->getPageSize()) - $parameter->getPageSize())
                ->take($parameter->getPageSize())
                ->get()
                ->map(function ($post) {
                    return [
                        'id' => $post->id,
                        'type' => 'post',
                        'attributes' => $post->getAttributes(),
                        'relationships' => [
                            'tags' => [
                                'data' => $post->tags,
                            ],
                            'author' => [
                                'data' => $post->author,
                            ],
                        ],
                    ];
                })->toArray();
        });

        return $this->response->withPayload($pagination->getResults());
    }
}
