<?php

namespace Virtue\Api\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Psr\Http\Server\MiddlewareInterface as ServerMiddleware;
use Psr\Http\Server\RequestHandlerInterface as HandlesServerRequests;

class RawUrlDecode implements ServerMiddleware
{
    public function process(ServerRequest $request, HandlesServerRequests $handler): Response
    {
        $request = $request->withUri($request->getUri()->withPath(rawurldecode($request->getUri()->getPath())));
        return $handler->handle($request);
    }
}
