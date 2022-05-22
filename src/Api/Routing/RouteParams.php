<?php

namespace Virtue\Api\Routing;

class RouteParams
{
    private $params;

    public function __construct(array $params)
    {
        $this->params = $params;
    }

    /**
     * Returns url decoded value of a route parameter
     *
     * @param string $name
     * @param string $default
     * @return string
     */
    public function get(string $name, string $default = ''): string
    {
        return urldecode($this->params[$name] ?? $default);
    }

    public function asArray(): array
    {
        return $this->params;
    }
}
