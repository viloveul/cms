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
        if ($media = Media::where(['id' => $id])->find()) {
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
        if ($media = Media::where(['id' => $id])->with('author')->find()) {
            if ($this->privilege->check($this->route->getName(), 'access', $media->author_id) !== true) {
                return $this->response->withErrors(403, [
                    "No direct access for route: {$this->route->getName()}",
                ]);
            }
            $image_url = $media->url;
            if (false === stripos($media->type, 'image')) {
                $image_url = vsprintf('%s/images/no-image-available.jpg', [
                    $this->config->get('baseurl'),
                ]);
            }
            return $this->response->withPayload([
                'data' => array_merge($media->toArray(), [
                    'image_url' => $image_url,
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
            $model->where(['author_id' => $this->user->get('sub')]);
        }
        $parameter = new Parameter('search', $_GET);
        $pagination = new Pagination($parameter);
        $config = $this->config;
        $pagination->with(function ($conditions, $size, $page, $order, $sort) use ($config, $model) {
            foreach ($conditions as $key => $value) {
                $model->where([$key => "%{$value}%"], Query::OPERATOR_LIKE);
            }
            $total = $model->count();
            $result = $model->order($order, $sort === 'ASC' ? Query::SORT_ASC : Query::SORT_DESC)
                ->limit($size, ($page * $size) - $size)
                ->findAll();
            $data = array_map(function ($o) use ($config) {
                $o['image_url'] = $o['url'];
                if (false === stripos($o['type'], 'image')) {
                    $o['image_url'] = vsprintf('%s/images/no-image-available.jpg', [
                        $config->get('baseurl'),
                    ]);
                }
                return $o;
            }, $result->toArray());

            return new ResultSet($total, $data);
        });

        return $this->response->withPayload([
            'meta' => $pagination->getMeta(),
            'data' => $pagination->getData(),
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
        $config = $this->config;
        $response = $this->response;
        $user = $this->user;
        $audit = $this->audit;
        return $uploader->upload('*', function ($uploadedFiles, $errors, $files) use ($config, $response, $user, $audit) {
            $results = [];
            foreach ($uploadedFiles as $uploadedFile) {
                $media = new Media();
                $media->setAttributes([
                    'id' => str_uuid(),
                    'author_id' => $user->get('sub') ?: 0,
                    'name' => $uploadedFile['name'],
                    'filename' => $uploadedFile['filename'],
                    'type' => $uploadedFile['type'],
                    'url' => $uploadedFile['url'],
                    'size' => $uploadedFile['size'] ?: 0,
                    'status' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
                $media->save();
                $audit->create($media->id, 'media');
                $image_url = $media->url;
                if (false === stripos($media->type, 'image')) {
                    $image_url = vsprintf('%s/images/no-image-available.jpg', [
                        $config->get('baseurl'),
                    ]);
                }
                $results[] = array_merge($media->getAttributes(), [
                    'image_url' => $image_url,
                ]);
            }
            return $this->response->withStatus(201)->withPayload([
                'data' => $results,
            ]);
        });
    }
}
