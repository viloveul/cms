<?php

namespace App\Controller;

use App\Component\Privilege;
use App\Entity\Notification;
use Countable;
use Viloveul\Auth\Contracts\Authentication;
use Viloveul\Config\Contracts\Configuration;
use Viloveul\Http\Contracts\Response;
use Viloveul\Http\Contracts\ServerRequest;
use Viloveul\Pagination\Builder as Pagination;
use Viloveul\Pagination\Parameter;
use Viloveul\Router\Contracts\Dispatcher;

class NotificationController implements Countable
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
     * @var mixed
     */
    protected $user;

    /**
     * @param ServerRequest $request
     * @param Response      $response
     * @param Privilege     $privilege
     * @param Dispatcher    $router
     */
    public function __construct(ServerRequest $request, Response $response, Privilege $privilege, Authentication $auth, Dispatcher $router)
    {
        $this->request = $request;
        $this->response = $response;
        $this->privilege = $privilege;
        $this->user = $auth->getUser();
        $this->route = $router->routed();
    }

    /**
     * @return mixed
     */
    public function count()
    {
        return $this->response->withPayload([
            'data' => [
                'total' => Notification::where('receiver_id', $this->user->get('sub'))->where('status', 0)->count(),
            ],
        ]);
    }

    /**
     * @param  int     $id
     * @return mixed
     */
    public function detail(int $id)
    {
        if ($notification = Notification::where('id', $id)->where('receiver_id', $this->user->get('sub'))->first()) {
            if ($notification->status == 0) {
                $notification->status = 1;
                $notification->save();
            }
            return $this->response->withPayload([
                'data' => [
                    'id' => $notification->id,
                    'type' => 'notification',
                    'attributes' => $notification->getAttributes(),
                ],
            ]);
        } else {
            return $this->response->withErrors(404, ['Notification not found']);
        }
    }

    /**
     * @param Configuration $config
     */
    public function index(Configuration $config)
    {
        $userId = $this->user->get('sub');
        $parameter = new Parameter('search', $_GET);
        $parameter->setBaseUrl("{$config->basepath}/notification/index");
        $pagination = new Pagination($parameter);
        $request = $this->request;
        $pagination->prepare(function () use ($request, $userId) {
            $model = Notification::query()->with('author');
            $parameter = $this->getParameter();
            foreach ($parameter->getConditions() as $key => $value) {
                $model->where($key, 'like', "%{$value}%");
            }
            $model->where('receiver_id', $userId);
            $this->total = $model->count();
            $this->data = $model->orderBy($parameter->getOrderBy(), $parameter->getSortOrder())
                ->skip(($parameter->getCurrentPage() * $parameter->getPageSize()) - $parameter->getPageSize())
                ->take($parameter->getPageSize())
                ->get()
                ->map(function ($notification) {
                    return [
                        'id' => $notification->id,
                        'type' => 'notification',
                        'attributes' => $notification->getAttributes(),
                        'relationships' => [
                            'author' => [
                                'data' => $notification->author,
                            ],
                        ],
                    ];
                })->toArray();
        });

        return $this->response->withPayload($pagination->getResults());
    }
}
