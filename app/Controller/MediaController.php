<?php

namespace App\Controller;

use App\Entity\Media;
use App\Component\Privilege;
use App\Component\AuditTrail;
use Viloveul\Pagination\Parameter;
use Viloveul\Pagination\ResultSet;
use Viloveul\Http\Contracts\Response;
use Viloveul\Database\Contracts\Query;
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
     * @param AuditTrail     $audit
     * @param Dispatcher     $router
     * @param Authentication $auth
     */
    public function __construct(
        ServerRequest $request,
        Response $response,
        Privilege $privilege,
        Configuration $config,
        AuditTrail $audit,
        Dispatcher $router,
        Authentication $auth
    ) {
        $this->request = $request;
        $this->response = $response;
        $this->privilege = $privilege;
        $this->config = $config;
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
        if ($media = Media::where(['id' => $id])->getResult()) {
            if ($this->privilege->check($this->route->getName(), 'access', $media->author_id) !== true) {
                return $this->response->withErrors(403, [
                    "No direct access for route: {$this->route->getName()}",
                ]);
            }
            $media->status = 3;
            $media->deleted_at = date('Y-m-d H:i:s');
            $media->save();
            $this->audit->delete($id, 'media');
            return $this->response->withStatus(204);
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
        if ($media = Media::where(['id' => $id])->with('author')->getResult()) {
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
        $model = Media::with('author');
        if ($this->privilege->check($this->route->getName(), 'access') !== true) {
            $model->where(function ($where) {
                $where->add(['author_id' => $this->user->get('sub')]);
                $where->add(['status' => 1], Query::OPERATOR_LIKE, Query::SEPARATOR_OR);
            });
        }
        $parameter = new Parameter('search', $_GET);
        $parameter->setBaseUrl("{$this->config->basepath}/media/index");
        $pagination = new Pagination($parameter);
        $request = $this->request;
        $pagination->with(function ($conditions, $size, $page, $order, $sort) use ($request, $model) {
            foreach ($conditions as $key => $value) {
                $model->where([$key => "%{$value}%"], Query::OPERATOR_LIKE);
            }
            $total = $model->count();
            $result = $model->orderBy($order, $sort === 'ASC' ? Query::SORT_ASC : Query::SORT_DESC)
                ->limit($size, ($page * $size) - $size)
                ->getResults();
            $data = array_map(function ($o) use ($request) {
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
            }, $result->toArray());

            return new ResultSet($total, $data);
        });

        return $this->response->withPayload([
            'meta' => $pagination->getMeta(),
            'data' => $pagination->getData(),
            'links' => $pagination->getLinks(),
        ]);
    }

    /**
     * @param  Uploader $uploader
     * @return mixed
     */
    public function upload(Uploader $uploader)
    {
        if ($this->privilege->check($this->route->getName(), 'access') !== true) {
            return $this->response->withErrors(403, [
                "No direct access for route: {$this->route->getName()}",
            ]);
        }
        $request = $this->request;
        $response = $this->response;
        $user = $this->user;
        $audit = $this->audit;
        return $uploader->upload('*', function ($uploadedFiles, $errors, $files) use ($request, $response, $user, $audit) {
            $results = [];
            foreach ($uploadedFiles as $uploadedFile) {
                $media = new Media();
                $media->setAttributes([
                    'id' => str_uuid(),
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
                $media->save();
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
            return $this->response->withStatus(201)->withPayload([
                'data' => $results,
            ]);
        });
    }
}
