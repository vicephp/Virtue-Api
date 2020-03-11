<?php

namespace Virtue\Api\Routing;

interface RouteDispatcher
{
    public function dispatch(string $method, string $uri): RoutingResults;
}
