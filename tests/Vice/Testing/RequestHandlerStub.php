<?php

namespace Vice\Testing;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as ServerResponse;
use Psr\Http\Server\RequestHandlerInterface as HandlesServerRequests;

class RequestHandlerStub implements HandlesServerRequests
{
    /** @var Response */
    private $response;

    public function __construct(Response $response)
    {
        $this->response = $response;
    }

    public function handle(ServerResponse $request): Response
    {
        return $this->response;
    }
}
