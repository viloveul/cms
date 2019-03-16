<?php

namespace App\Controller;

use App\Component\Helper;
use App\Component\Privilege;
use App\Entity\Media;
use Viloveul\Auth\Contracts\Authentication;
use Viloveul\Config\Contracts\Configuration;
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
    protected $config;

    /**
     * @var mixed
     */
    protected $helper;

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
     * @var mixed
     */
    protected $user;

    /**
     * @param ServerRequest  $request
     * @param Response       $response
     * @param Privilege      $privilege
     * @param Configuration  $config
     * @param Helper         $helper
     * @param Dispatcher     $router
     * @param Authentication $auth
     */
    public function __construct(
        ServerRequest $request,
        Response $response,
        Privilege $privilege,
        Configuration $config,
        Helper $helper,
        Dispatcher $router,
        Authentication $auth
    ) {
        $this->request = $request;
        $this->response = $response;
        $this->privilege = $privilege;
        $this->config = $config;
        $this->helper = $helper;
        $this->route = $router->routed();
        $this->user = $auth->getUser();
    }

    /**
     * @param  int     $id
     * @return mixed
     */
    public function delete(int $id)
    {
        if ($media = Media::where('id', $id)->first()) {
            if ($this->privilege->check($this->route->getName(), 'access', $media->author_id) !== true) {
                return $this->response->withErrors(403, [
                    "No direct access for route: {$this->route->getName()}",
                ]);
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
                return $this->response->withErrors(403, [
                    "No direct access for route: {$this->route->getName()}",
                ]);
            }
            $url = vsprintf('%s/uploads/%s/%s/%s/%s', [
                $this->request->getBaseUrl(),
                $media->year,
                $media->month,
                $media->day,
                $media->filename,
            ]);
            $image = $url;
            if (false === stripos($media->type, 'image')) {
                $image = vsprintf('%s/images/media-image.png', [
                    $this->request->getBaseUrl(),
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
            return $this->response->withErrors(403, [
                "No direct access for route: {$this->route->getName()}",
            ]);
        }
        $parameter = new Parameter('search', $_GET);
        $parameter->setBaseUrl("{$this->config->basepath}/media/index");
        $pagination = new Pagination($parameter);
        $request = $this->request;
        $pagination->prepare(function () use ($request) {
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

            $this->data = array_map(function ($o) use ($request) {
                $o['attributes']['url'] = vsprintf('%s/uploads/%s/%s/%s/%s', [
                    $request->getBaseUrl(),
                    $o['attributes']['year'],
                    $o['attributes']['month'],
                    $o['attributes']['day'],
                    $o['attributes']['filename'],
                ]);
                $o['attributes']['image_url'] = $o['attributes']['url'];
                if (false === stripos($o['attributes']['type'], 'image')) {
                    $o['attributes']['image_url'] = vsprintf('%s/images/media-image.png', [
                        $request->getBaseUrl(),
                    ]);
                }
                return $o;
            }, $data);
        });

        return $this->response->withPayload($pagination->getResults());
    }

    /**
     * @param  Uploader $uploader
     * @return mixed
     */
    public function upload(Uploader $uploader)
    {
        $request = $this->request;
        $response = $this->response;
        $user = $this->user;
        return $uploader->upload('*', function ($uploadedFiles, $errors, $files) use ($request, $response, $user) {
            $results = [];
            foreach ($uploadedFiles as $uploadedFile) {
                $media = Media::create([
                    'author_id' => $user->get('sub') ?: 0,
                    'name' => $uploadedFile['name'],
                    'filename' => $uploadedFile['filename'],
                    'ref' => $uploadedFile['category'],
                    'type' => $uploadedFile['type'],
                    'size' => $uploadedFile['size'] ?: 0,
                    'year' => $uploadedFile['year'],
                    'month' => $uploadedFile['month'],
                    'day' => $uploadedFile['day'],
                    'status' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
                $url = vsprintf('%s/uploads/%s/%s/%s/%s', [
                    $request->getBaseUrl(),
                    $media->year,
                    $media->month,
                    $media->day,
                    $media->filename,
                ]);
                $image = $url;
                if (false === stripos($media->type, 'image')) {
                    $image = vsprintf('%s/images/media-image.png', [
                        $request->getBaseUrl(),
                    ]);
                }
                $results[] = [
                    'id' => $media->id,
                    'type' => 'media',
                    'attributes' => array_merge($media->getAttributes(), [
                        'url' => $url,
                        'image_url' => $image,
                    ]),
                ];
            }
            return $this->response->withPayload([
                'data' => $results,
            ]);
        });
    }
}
