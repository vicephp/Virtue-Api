<?php

namespace Virtue\Api\Routing;

use Psr\Container\ContainerInterface as Locator;
use Psr\Http\Message\ResponseInterface as Response;

class Api
{
    /** @var Locator */
    private $services;
    /** @var RouteCollector */
    private $routeCollector;
    /** @var string */
    private $groupPattern = '';

    public function __construct(
        Locator $kernel,
        RouteCollector $routeCollector,
        ?string $groupPattern = ''
    ) {
        $this->services = $kernel;
        $this->routeCollector = $routeCollector;
        $this->groupPattern = $groupPattern;
    }

    public function get(string $pattern, $handler): Route
    {
        return $this->map(['GET'], $pattern, $handler);
    }

    public function post(string $pattern, $handler): Route
    {
        return $this->map(['POST'], $pattern, $handler);
    }

    public function put(string $pattern, $handler): Route
    {
        return $this->map(['PUT'], $pattern, $handler);
    }

    public function patch(string $pattern, $handler): Route
    {
        return $this->map(['PATCH'], $pattern, $handler);
    }

    public function delete(string $pattern, $handler): Route
    {
        return $this->map(['DELETE'], $pattern, $handler);
    }

    public function options(string $pattern, $handler): Route
    {
        return $this->map(['OPTIONS'], $pattern, $handler);
    }

    public function any(string $pattern, $handler): Route
    {
        return $this->map(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], $pattern, $handler);
    }

    public function map(array $methods, string $pattern, $handler): Route
    {
        return $this->routeCollector->map($methods, "{$this->groupPattern}{$pattern}", $handler);
    }

    public function group(string $pattern, $callable): RouteGroup
    {
        return $this->routeCollector->group("{$this->groupPattern}{$pattern}", $callable);
    }

    public function redirect(string $from, $to, int $status = 302): Route
    {
        $response = $this->services->get(Response::class);
        $response->withStatus($status)->withHeader('Location', (string) $to);

        $handler = function () use ($response) {
            return $response;
        };

        return $this->get($from, $handler);
    }
}
