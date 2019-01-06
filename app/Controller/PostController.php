<?php

namespace App\Controller;

use Viloveul\Http\Contracts\Request;
use Viloveul\Http\Contracts\Response;

class PostController
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
     * @param Request  $request
     * @param Response $response
     */
    public function __construct(Request $request, Response $response)
    {
        $this->request = $request->request;
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
