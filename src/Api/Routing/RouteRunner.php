<?php

namespace Virtue\Api\Routing;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Psr\Http\Server\RequestHandlerInterface as HandlesServerRequests;
use Virtue\Api\ServerRequest\RoutingResults;

class RouteRunner implements HandlesServerRequests
{
    public function handle(ServerRequest $request): Response
    {
        return RoutingResults::fromRequest($request)->getRoute()->handle($request);
    }
}
