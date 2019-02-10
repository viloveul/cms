<?php

namespace App\Controller;

use App\Component\AttrAssignment;
use App\Entity\User;
use Viloveul\Event\Contracts\Dispatcher as Event;
use Viloveul\Http\Contracts\Response;
use Viloveul\Http\Contracts\ServerRequest;

class ProfileController
{
    /**
     * @var mixed
     */
    protected $event;

    /**
     * @var mixed
     */
    protected $request;

    /**
     * @var mixed
     */
    protected $response;

    /**
     * @param ServerRequest $request
     * @param Response      $response
     */
    public function __construct(ServerRequest $request, Response $response, Event $event)
    {
        $this->request = $request;
        $this->response = $response;
        $this->event = $event;
    }

    /**
     * @param  int     $id
     * @return mixed
     */
    public function detail(int $id)
    {
        if ($user = User::where('id', $id)->where('status', 1)->first()) {
            return $this->response->withPayload([
                'data' => [
                    'id' => $user->id,
                    'type' => 'profile',
                    'attributes' => $user->profile->pluck('value', 'name'),
                ],
            ]);
        } else {
            return $this->response->withErrors(400, ['User not actived.']);
        }
    }

    /**
     * @param  int     $id
     * @return mixed
     */
    public function update(int $id)
    {
        if ($user = User::where('id', $id)->where('status', 1)->first()) {
            $attr = $this->request->loadPostTo(new AttrAssignment);
            foreach ($attr->getAttributes() as $name => $value) {
                $user->profile()->updateOrCreate(compact('name'), [
                    'value' => $value,
                    'last_modified' => date('Y-m-d H:i:s'),
                ]);
            }
            return $this->response->withPayload([
                'data' => [
                    'id' => $id,
                    'type' => 'profile',
                    'attributes' => $user->profile->pluck('value', 'name'),
                ],
            ]);
        } else {
            return $this->response->withErrors(400, ['User not actived.']);
        }
    }
}
