<?php

namespace Virtue\Api\Routing;

class Found extends Result
{
    public function getRoute(): Route
    {
        return $this->results[1];
    }

    public function getRouteParams(): RouteParams
    {
        return $this->results[2];
    }
}
