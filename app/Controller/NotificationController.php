<?php

namespace App\Controller;

use Countable;
use App\Component\Privilege;
use App\Entity\Notification;
use Viloveul\Pagination\Parameter;
use Viloveul\Pagination\ResultSet;
use Viloveul\Http\Contracts\Response;
use Viloveul\Transport\Contracts\Bus;
use Viloveul\Database\Contracts\Query;
use Viloveul\Router\Contracts\Dispatcher;
use Viloveul\Http\Contracts\ServerRequest;
use Viloveul\Auth\Contracts\Authentication;
use Viloveul\Config\Contracts\Configuration;
use Viloveul\Pagination\Builder as Pagination;

class NotificationController implements Countable
{
    /**
     * @var mixed
     */
    protected $bus;

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
     * @param Bus            $bus
     * @param Authentication $auth
     * @param Dispatcher     $router
     */
    public function __construct(
        ServerRequest $request,
        Response $response,
        Privilege $privilege,
        Configuration $config,
        Bus $bus,
        Authentication $auth,
        Dispatcher $router
    ) {
        $this->request = $request;
        $this->response = $response;
        $this->privilege = $privilege;
        $this->config = $config;
        $this->bus = $bus;
        $this->user = $auth->getUser();
        $this->route = $router->routed();
    }

    /**
     * @return mixed
     */
    public function count()
    {
        $uid = $this->user->get('sub');
        return $this->response->withPayload([
            'data' => [
                'total' => Notification::where(['receiver_id' => $uid])->count(),
                'unread' => Notification::where(['receiver_id' => $uid, 'status' => 0])->count(),
                'read' => Notification::where(['receiver_id' => $uid, 'status' => 1])->count(),
            ],
        ]);
    }

    /**
     * @param  string  $id
     * @return mixed
     */
    public function detail(string $id)
    {
        $userId = $this->user->get('sub');
        if ($notification = Notification::where(['id' => $id, 'receiver_id' => $userId])->with('author')->find()) {
            if ($notification->status == 0) {
                $notification->status = 1;
                $notification->updated_at = date('Y-m-d H:i:s');
                $notification->save();
            }
            return $this->response->withPayload([
                'data' => $notification,
            ]);
        } else {
            return $this->response->withErrors(404, ['Notification not found']);
        }
    }

    public function index()
    {
        $userId = $this->user->get('sub');
        $parameter = new Parameter('search', $_GET);
        $pagination = new Pagination($parameter);
        $request = $this->request;
        $pagination->with(function ($conditions, $size, $page, $order, $sort) use ($request, $userId) {
            $model = Notification::select(['id', 'subject', 'content', 'status']);
            foreach ($conditions as $key => $value) {
                $model->where([$key => "%{$value}%"], Query::OPERATOR_LIKE);
            }
            $model->where(['receiver_id' => $userId]);
            $total = $model->count();
            $result = $model->order($order, $sort === 'ASC' ? Query::SORT_ASC : Query::SORT_DESC)
                ->limit($size, ($page * $size) - $size)
                ->findAll();
            return new ResultSet($total, $result->toArray());
        });

        return $this->response->withPayload([
            'meta' => $pagination->getMeta(),
            'data' => $pagination->getData(),
        ]);
    }
}
