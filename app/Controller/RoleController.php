<?php

namespace App\Controller;

use Viloveul\Http\Contracts\Response;
use Viloveul\Http\Contracts\ServerRequest;

class RoleController
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
     * @param ServerRequest $request
     * @param Response      $response
     */
    public function __construct(ServerRequest $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }
}
