<?php

namespace Virtue\Api\Testing;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Psr\Http\Server\RequestHandlerInterface as HandlesServerRequests;

class RequestHandlerStub implements HandlesServerRequests
{
    /** @var Response */
    private $response;
    /** @var ServerRequest */
    private $request;

    public function __construct(Response $response)
    {
        $this->response = $response;
    }

    public function handle(ServerRequest $request): Response
    {
        $this->request = $request;

        return $this->response;
    }

    public function last(): ServerRequest
    {
        return $this->request;
    }
}
