<?php

namespace App\Controller;

use App\Component\Privilege;
use App\Entity\Media;
use Viloveul\Auth\Contracts\Authentication;
use Viloveul\Http\Contracts\Response;
use Viloveul\Http\Contracts\ServerRequest;
use Viloveul\Media\Contracts\Uploader;
use Viloveul\Pagination\Builder as Pagination;
use Viloveul\Pagination\Parameter;
use Viloveul\Router\Contracts\Dispatcher;

class MediaController
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
     * @param Privilege     $privilege
     * @param Dispatcher    $router
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
        if ($media = Media::where('id', $id)->first()) {
            if ($this->privilege->check($this->route->getName(), 'access', $media->author_id) !== true) {
                return $this->response->withErrors(403, ["No direct access for route: {$this->route->getName()}"]);
            }
            $media->status = 3;
            $media->deleted_at = date('Y-m-d H:i:s');
            if ($media->save()) {
                return $this->response->withStatus(201);
            } else {
                return $this->response->withErrors(500, ['Something Wrong !!!']);
            }
        } else {
            return $this->response->withErrors(404, ['Media not found']);
        }
    }

    /**
     * @param  int     $id
     * @return mixed
     */
    public function detail(int $id)
    {
        if ($media = Media::where('id', $id)->with('author')->first()) {
            if ($this->privilege->check($this->route->getName(), 'access', $media->author_id) !== true) {
                return $this->response->withErrors(403, ["No direct access for route: {$this->route->getName()}"]);
            }
            $uri = $this->request->getUri();
            $schema = $uri->getScheme();
            $host = $uri->getHost();
            $port = $uri->getPort();
            $url = vsprintf('%s://%s:%s/uploads/%s/%s/%s/%s', [
                $schema,
                $host,
                $port,
                $media->year,
                $media->month,
                $media->day,
                $media->filename,
            ]);
            $image = $url;
            if (false === stripos($media->type, 'image')) {
                $image = vsprintf('%s://%s:%s/images/no-image.jpg', [
                    $schema,
                    $host,
                    $port,
                ]);
            }
            return $this->response->withPayload([
                'data' => [
                    'id' => $id,
                    'type' => 'media',
                    'attributes' => array_merge($media->getAttributes(), [
                        'url' => $url,
                        'image_url' => $image,
                    ]),
                    'relationships' => [
                        'author' => [
                            'data' => $media->author,
                        ],
                    ],
                ],
            ]);
        } else {
            return $this->response->withErrors(404, ['Media not found']);
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
        $parameter->setBaseUrl('/api/v1/media/index');
        $pagination = new Pagination($parameter);
        $uri = $this->request->getUri();
        $pagination->prepare(function () use ($uri) {
            $model = Media::query()->with('author');
            $parameter = $this->getParameter();
            foreach ($parameter->getConditions() as $key => $value) {
                $model->where($key, 'like', "%{$value}%");
            }
            $this->total = $model->count();
            $data = $model->orderBy($parameter->getOrderBy(), $parameter->getSortOrder())
                ->skip(($parameter->getCurrentPage() * $parameter->getPageSize()) - $parameter->getPageSize())
                ->take($parameter->getPageSize())
                ->get()
                ->map(function ($media) {
                    return [
                        'id' => $media->id,
                        'type' => 'media',
                        'attributes' => $media->getAttributes(),
                        'relationships' => [
                            'author' => [
                                'data' => $media->author,
                            ],
                        ],
                    ];
                })->toArray();

            $schema = $uri->getScheme();
            $host = $uri->getHost();
            $port = $uri->getPort();
            $this->data = array_map(function ($o) use ($schema, $host, $port) {
                $o['attributes']['url'] = vsprintf('%s://%s:%s/uploads/%s/%s/%s/%s', [
                    $schema,
                    $host,
                    $port,
                    $o['attributes']['year'],
                    $o['attributes']['month'],
                    $o['attributes']['day'],
                    $o['attributes']['filename'],
                ]);
                $o['attributes']['image_url'] = $o['attributes']['url'];
                if (false === stripos($o['attributes']['type'], 'image')) {
                    $o['attributes']['image_url'] = vsprintf('%s://%s:%s/images/no-image.jpg', [
                        $schema,
                        $host,
                        $port,
                    ]);
                }
                return $o;
            }, $data);
        });

        return $this->response->withPayload($pagination->getResults());
    }

    /**
     * @param  Uploader       $uploader
     * @param  Response       $response
     * @param  Authentication $auth
     * @return mixed
     */
    public function upload(Uploader $uploader, Response $response, Authentication $auth)
    {
        if ($this->privilege->check($this->route->getName(), 'access') !== true) {
            return $this->response->withErrors(403, ["No direct access for route: {$this->route->getName()}"]);
        }
        return $uploader->upload('*', function ($uploadedFiles, $errors, $files) use ($response, $auth) {
            $results = [];
            foreach ($uploadedFiles as $uploadedFile) {
                $media = Media::create([
                    'author_id' => $auth->getUser()->get('sub') ?: 0,
                    'name' => $uploadedFile['name'],
                    'filename' => $uploadedFile['filename'],
                    'type' => $uploadedFile['type'],
                    'size' => $uploadedFile['size'] ?: 0,
                    'year' => $uploadedFile['year'],
                    'month' => $uploadedFile['month'],
                    'day' => $uploadedFile['day'],
                    'status' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
                $results[] = [
                    'id' => $media->id,
                    'type' => 'media',
                    'attributes' => $media->getAttributes(),
                ];
            }
            return $this->response->withPayload([
                'data' => $results,
            ]);
        });
    }
}
