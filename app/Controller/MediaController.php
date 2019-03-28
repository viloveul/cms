<?php

namespace App\Controller;

use App\Entity\Media;
use App\Component\Helper;
use App\Component\Privilege;
use App\Component\AuditTrail;
use Viloveul\Pagination\Parameter;
use Viloveul\Http\Contracts\Response;
use Viloveul\Media\Contracts\Uploader;
use Viloveul\Router\Contracts\Dispatcher;
use Viloveul\Http\Contracts\ServerRequest;
use Viloveul\Auth\Contracts\Authentication;
use Viloveul\Config\Contracts\Configuration;
use Viloveul\Pagination\Builder as Pagination;

class MediaController
{
    /**
     * @var mixed
     */
    protected $audit;

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
     * @param AuditTrail     $audit
     * @param Dispatcher     $router
     * @param Authentication $auth
     */
    public function __construct(
        ServerRequest $request,
        Response $response,
        Privilege $privilege,
        Configuration $config,
        Helper $helper,
        AuditTrail $audit,
        Dispatcher $router,
        Authentication $auth
    ) {
        $this->request = $request;
        $this->response = $response;
        $this->privilege = $privilege;
        $this->config = $config;
        $this->helper = $helper;
        $this->audit = $audit;
        $this->route = $router->routed();
        $this->user = $auth->getUser();
    }

    /**
     * @param  string  $id
     * @return mixed
     */
    public function delete(string $id)
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
                $this->audit->delete($id, 'media');
                return $this->response->withStatus(201);
            } else {
                return $this->response->withErrors(500, ['Something Wrong !!!']);
            }
        } else {
            return $this->response->withErrors(404, ['Media not found']);
        }
    }

    /**
     * @param  string  $id
     * @return mixed
     */
    public function detail(string $id)
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
                'data' => array_merge($media->toArray(), [
                    'url' => $url,
                    'image_url' => $image,
                ]),
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
                ->toArray();

            $this->data = array_map(function ($o) use ($request) {
                $o['url'] = vsprintf('%s/uploads/%s/%s/%s/%s', [
                    $request->getBaseUrl(),
                    $o['year'],
                    $o['month'],
                    $o['day'],
                    $o['filename'],
                ]);
                $o['image_url'] = $o['url'];
                if (false === stripos($o['type'], 'image')) {
                    $o['image_url'] = vsprintf('%s/images/media-image.png', [
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
        $audit = $this->audit;
        $helper = $this->helper;
        return $uploader->upload('*', function ($uploadedFiles, $errors, $files) use ($request, $response, $user, $audit, $helper) {
            $results = [];
            foreach ($uploadedFiles as $uploadedFile) {
                $media = Media::create([
                    'id' => $helper->uuid(),
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
                $audit->create($media->id, 'media');
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
                $results[] = array_merge($media->getAttributes(), [
                    'url' => $url,
                    'image_url' => $image,
                ]);
            }
            return $this->response->withPayload([
                'data' => $results,
            ]);
        });
    }
}
