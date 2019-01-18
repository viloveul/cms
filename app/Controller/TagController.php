<?php

namespace App\Controller;

use Viloveul\Http\Contracts\ServerRequest;
use Viloveul\Http\Contracts\Response;

class TagController
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
     * @param ServerRequest  $request
     * @param Response $response
     */
    public function __construct(ServerRequest $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    public function create()
    {

    }

    /**
     * @param $id
     */
    public function delete($id)
    {

    }

    /**
     * @param $id
     */
    public function detail($id)
    {

    }

    public function index()
    {

    }

    /**
     * @param $id
     */
    public function update($id)
    {

    }
}
