<?php

namespace Virtue\Api\Testing;

use Psr\Http\Message\ResponseFactoryInterface as ResponseFactory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Psr\Http\Server\MiddlewareInterface as ServerMiddleware;
use Psr\Http\Server\RequestHandlerInterface as HandlesServerRequests;

class ErrorHandling implements ServerMiddleware
{
    /** @var ResponseFactory */
    private $response;

    public function __construct(ResponseFactory $response)
    {
        $this->response = $response;
    }

    public function process(ServerRequest $request, HandlesServerRequests $next): Response
    {
        try {
            $next->handle($request);
        } catch (\Throwable $error) {

        }

        return $this->response->createResponse(500);
    }
}
