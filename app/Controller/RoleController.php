<?php

namespace App\Controller;

use Viloveul\Http\Contracts\Response;
use Viloveul\Http\Contracts\Request;

class RoleController
{
	protected $request;
	protected $response;
	public function __construct(Request $request, Response $response)
	{
		$this->request = $request;
		$this->response = $response;
	}
	
}