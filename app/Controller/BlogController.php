<?php

namespace App\Controller;

use App\Entity\Tag;
use App\Entity\Post;
use App\Entity\User;
use App\Entity\Comment;
use App\Component\Setting;
use Viloveul\Pagination\Parameter;
use Viloveul\Pagination\ResultSet;
use Viloveul\Http\Contracts\Response;
use Viloveul\Database\Contracts\Query;
use Viloveul\Http\Contracts\ServerRequest;
use Viloveul\Config\Contracts\Configuration;
use Viloveul\Pagination\Builder as Pagination;

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
        if ($archive = Tag::where(['slug' => $slug])->getResult()) {
            $model = Post::select([
                'id',
                'author_id',
                'created_at',
                'title',
                'description',
                'slug',
                'type',
                'comment_enabled',
            ]);
            $model->withCount('comments');
            $model->with('author', function ($query) {
                $query->select(['id', 'email', 'username', 'name', 'status']);
            });
            $model->with('tags', function ($query) {
                $query->select(['id', 'title', 'type', 'slug']);
            });
            $parameter = new Parameter('search', $_GET);
            $parameter->setBaseUrl("{$this->config->basepath}/blog/archive/{$slug}");
            $pagination = new Pagination($parameter);
            $pagination->with(function ($conditions, $size, $page, $order, $sort) use ($model, $slug) {
                foreach ($conditions as $key => $value) {
                    if (in_array($key, ['id', 'author_id', 'type', 'comment_enabled'])) {
                        $model->where([$key => $value]);
                    } else {
                        $model->where([$key => "%{$value}%"], Query::OPERATOR_LIKE);
                    }
                }
                $model->where([
                    'type' => 'post',
                    'status' => 1,
                ]);
                $model->whereHas('tags', function ($query) use ($slug) {
                    $query->where([
                        'slug' => $slug,
                        'status' => 1,
                    ]);
                });
                $model->where(['created_at' => date('Y-m-d H:i:s')], Query::OPERATOR_LTE);

                $total = $model->count();
                $result = $model->orderBy($order, $sort === 'ASC' ? Query::SORT_ASC : Query::SORT_DESC)
                    ->limit($size, ($page * $size) - $size)
                    ->getResults();
                return new ResultSet($total, $result->toArray());
            });
            return $this->response->withPayload([
                'meta' => array_merge($pagination->getMeta(), compact('archive')),
                'data' => $pagination->getData(),
                'links' => $pagination->getLinks(),
            ]);
        }
        return $this->response->withErrors(404, ["Archive {$slug} not found."]);
    }

    /**
     * @param string $name
     */
    public function author(string $name)
    {
        if ($author = User::where(['username' => $name])->getResult()) {
            $model = Post::select([
                'id',
                'author_id',
                'created_at',
                'title',
                'description',
                'slug',
                'type',
                'comment_enabled',
            ]);
            $model->withCount('comments');
            $model->with('author', function ($query) {
                $query->select(['id', 'email', 'username', 'name', 'status']);
            });
            $model->with('tags', function ($query) {
                $query->select(['id', 'title', 'type', 'slug']);
            });
            $parameter = new Parameter('search', $_GET);
            $parameter->setBaseUrl("{$this->config->basepath}/blog/author/{$name}");
            $pagination = new Pagination($parameter);
            $pagination->with(function ($conditions, $size, $page, $order, $sort) use ($model, $author) {
                foreach ($conditions as $key => $value) {
                    if (in_array($key, ['id', 'author_id', 'type', 'comment_enabled'])) {
                        $model->where([$key => $value]);
                    } else {
                        $model->where([$key => "%{$value}%"], Query::OPERATOR_LIKE);
                    }
                }
                $model->where([
                    'status', 1,
                    'author_id' => $author->id,
                ]);
                $model->where(['created_at' => date('Y-m-d H:i:s')], Query::OPERATOR_LTE);
                $total = $model->count();
                $result = $model->orderBy($order, $sort === 'ASC' ? Query::SORT_ASC : Query::SORT_DESC)
                    ->limit($size, ($page * $size) - $size)
                    ->getResults();
                return new ResultSet($total, $result->toArray());
            });
            return $this->response->withPayload([
                'meta' => array_merge($pagination->getMeta(), [
                    'author' => $author,
                    'profile' => $author->profile->convertList('name', 'value'),
                ]),
                'data' => $pagination->getData(),
                'links' => $pagination->getLinks(),
            ]);
        }
        return $this->response->withErrors(404, ["Author {$name} not found."]);
    }

    /**
     * @param string $post_id
     */
    public function comments(string $post_id)
    {
        if ($post = Post::where(['id' => $post_id, 'status' => 1, 'comment_enabled' => 1])->getResult()) {
            $model = Comment::with('author', function ($query) {
                $query->select(['id', 'email', 'username', 'name', 'status']);
            });
            $model->where([
                'status' => 1,
                'post_id' => $post_id,
            ]);
            $model->where(['created_at' => date('Y-m-d H:i:s')], Query::OPERATOR_LTE);
            $parameter = new Parameter('search', $_GET);
            $parameter->setBaseUrl("{$this->config->basepath}/blog/comments/{$post_id}");
            $pagination = new Pagination($parameter);
            $pagination->with(function ($conditions, $size, $page, $order, $sort) use ($model) {
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
        $model = Post::where(['created_at' => date('Y-m-d H:i:s')], Query::OPERATOR_LTE);
        if ($post = $model->where(['slug' => $slug, 'status' => 1])->with(['author', 'tags'])->getResult()) {
            return $this->response->withPayload([
                'data' => $post,
            ]);
        }
        return $this->response->withErrors(404, ['Page not found.']);
    }

    public function index()
    {
        $model = Post::select([
            'id',
            'author_id',
            'created_at',
            'title',
            'description',
            'slug',
            'type',
            'comment_enabled',
        ]);
        $model->withCount('comments');
        $model->with('author', function ($query) {
            $query->select(['id', 'email', 'username', 'name', 'status']);
        });
        $model->with('tags', function ($query) {
            $query->select(['id', 'title', 'type', 'slug']);
        });

        $parameter = new Parameter('search', $_GET);
        $parameter->setBaseUrl("{$this->config->basepath}/blog/index");
        $pagination = new Pagination($parameter);
        $pagination->with(function ($conditions, $size, $page, $order, $sort) use ($model) {
            foreach ($conditions as $key => $value) {
                if (in_array($key, ['id', 'author_id', 'type', 'comment_enabled'])) {
                    $model->where([$key => $value]);
                } else {
                    $model->where([$key => "%{$value}%"], Query::OPERATOR_LIKE);
                }
            }
            $model->where([
                'type' => 'post',
                'status' => 1,
            ]);
            $model->where(['created_at' => date('Y-m-d H:i:s')], Query::OPERATOR_LTE);

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
}
