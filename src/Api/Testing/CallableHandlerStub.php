<?php

namespace Virtue\Api\Testing;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Psr\Http\Server\RequestHandlerInterface as HandlesServerRequests;

class CallableHandlerStub implements HandlesServerRequests
{
    /** @var callable */
    private $handler;

    public function __construct(callable $handler)
    {
        $this->handler = $handler;
    }

    public function handle(ServerRequest $request): Response
    {
        return ($this->handler)($request);
    }
}
