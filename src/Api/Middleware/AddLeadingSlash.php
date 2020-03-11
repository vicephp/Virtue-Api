<?php

namespace Virtue\Api\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface as ServerMiddleware;
use Psr\Http\Server\RequestHandlerInterface;

class AddLeadingSlash implements ServerMiddleware
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $uri = $request->getUri()->getPath();
        $uri = rawurldecode($uri);
        $uri = ($uri === '' || $uri[0] !== '/') ? "/{$uri}" : $uri;
        return $handler->handle($request->withUri($request->getUri()->withPath($uri)));
    }
}
