<?php

namespace Virtue\Api\Routing;

class RouteParams
{
    private $params = [];

    public function __construct(array $params)
    {
        $this->params = $params;
    }

    public function get($name, $default = null)
    {
        return $this->params[$name] ?? $default;
    }

    public function asArray(): array
    {
        return $this->params;
    }
}
