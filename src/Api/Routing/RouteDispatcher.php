<?php

namespace Virtue\Api\Routing;

use Psr\Http\Message\ServerRequestInterface as ServerRequest;

interface RouteDispatcher
{
    public function dispatch(ServerRequest $request): ServerRequest;
}
