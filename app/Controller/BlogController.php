<?php

namespace App\Controller;

use App\Component\AttrAssignment;
use App\Component\Setting;
use App\Entity\Comment;
use App\Entity\Post;
use App\Entity\Tag;
use App\Entity\User;
use App\Validation\Comment as CommentValidation;
use Viloveul\Auth\Contracts\Authentication;
use Viloveul\Http\Contracts\Response;
use Viloveul\Http\Contracts\ServerRequest;
use Viloveul\Pagination\Builder as Pagination;
use Viloveul\Pagination\Parameter;

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
     * @var mixed
     */
    protected $setting;

    /**
     * @param ServerRequest $request
     * @param Response      $response
     * @param Setting       $setting
     */
    public function __construct(ServerRequest $request, Response $response, Setting $setting)
    {
        $this->request = $request;
        $this->response = $response;
        $this->setting = $setting;
    }

    /**
     * @param string $slug
     */
    public function archive(string $slug)
    {
        if ($archive = Tag::where('slug', $name)->first()) {
            $model = Post::query();
            $parameter = new Parameter('search', $_GET);
            $parameter->setBaseUrl("/api/v1/blog/archive/{$slug}");
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
                        $query->select(['id', 'email', 'name', 'nickname']);
                    },
                    'tags' => function ($query) {
                        $query->select(['tag_id', 'post_id', 'title', 'type', 'slug']);
                        $query->where('type', 'tag');
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
                    ->toArray();
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
        if ($author = User::where('nickname', $name)->first()) {
            $model = Post::query();
            $parameter = new Parameter('search', $_GET);
            $parameter->setBaseUrl("/api/v1/blog/author/{$name}");
            $pagination = new Pagination($parameter);
            $pagination->prepare(function () use ($model, $author) {
                $parameter = $this->getParameter();
                foreach ($parameter->getConditions() as $key => $value) {
                    $model->where($key, 'like', "%{$value}%");
                }
                $model->where('type', 'post');
                $model->where('status', 1);
                $model->where('author_id', $author->id);

                $model->withCount('comments');
                $model->with([
                    'author' => function ($query) {
                        $query->select(['id', 'email', 'name', 'nickname']);
                    },
                    'tags' => function ($query) {
                        $query->select(['tag_id', 'post_id', 'title', 'type', 'slug']);
                        $query->where('type', 'tag');
                    },
                ]);

                $this->total = $model->count();
                $this->data = $model->orderBy($parameter->getOrderBy(), $parameter->getSortOrder())
                    ->skip(($parameter->getCurrentPage() * $parameter->getPageSize()) - $parameter->getPageSize())
                    ->take($parameter->getPageSize())
                    ->get()
                    ->toArray();
            });
            $results = $pagination->getResults();
            $results['meta']['author'] = $author;
            return $this->response->withPayload($results);
        }
        return $this->response->withErrors(404, ["Author {$name} not found."]);
    }

    /**
     * @param  int            $post_id
     * @param  Authentication $auth
     * @return mixed
     */
    public function comment(int $post_id, Authentication $auth)
    {
        if ($post = Post::where('status', 1)->where('id', $post_id)->where('comment_enabled', 1)->first()) {
            $attributes = new AttrAssignment();
            $this->request->loadPostTo($attributes);
            $user = $auth->getUser();
            if ($id = $user->get('sub')) {
                $attributes['author_id'] = $id;
                $attributes['name'] = $user->get('name');
                $attributes['nickname'] = $user->get('nickname');
                $attributes['email'] = $user->get('email');
            }
            $attributes['post_id'] = $post_id;
            $validator = new CommentValidation($attributes->getAttributes());
            if ($validator->validate('insert')) {
                $comment = new Comment();
                $data = array_only($attributes->getAttributes(), ['parent_id', 'post_id', 'author_id', 'name', 'nickname', 'email', 'website', 'content']);
                foreach ($data as $key => $value) {
                    $comment->{$key} = $value;
                }
                $comment->status = !$this->setting->get('moderations.comment');
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
        } else {
            return $this->response->withErrors(404, ['Page not found.']);
        }
    }

    /**
     * @param int $post_id
     */
    public function comments(int $post_id)
    {
        if ($post = Post::where('id', $post_id)->where('status', 1)->where('comment_enabled', 1)->first()) {
            $model = Comment::query();
            $parameter = new Parameter('search', $_GET);
            $parameter->setBaseUrl("/api/v1/blog/comments/{$post_id}");
            $pagination = new Pagination($parameter);
            $pagination->prepare(function () use ($model, $post_id) {
                $parameter = $this->getParameter();
                foreach ($parameter->getConditions() as $key => $value) {
                    $model->where($key, 'like', "%{$value}%");
                }
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
            $model->where('status', 1);

            $model->withCount('comments');
            $model->with([
                'author' => function ($query) {
                    $query->select(['id', 'email', 'name', 'nickname']);
                },
                'tags' => function ($query) {
                    $query->select(['tag_id', 'post_id', 'title', 'type', 'slug']);
                    $query->where('type', 'tag');
                },
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
