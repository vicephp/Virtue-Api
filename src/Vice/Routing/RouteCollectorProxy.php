<?php

namespace Vice\Routing;

use Psr\Container\ContainerInterface as Locator;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Interfaces\CallableResolverInterface;

class RouteCollectorProxy
{
    /** @var Locator */
    private $services;
    /** @var CallableResolverInterface */
    private $callableResolver;
    /** @var RouteCollector */
    private $routeCollector;
    /** @var string */
    private $groupPattern = '';

    public function __construct(
        Locator $services,
        CallableResolverInterface $callableResolver,
        RouteCollector $routeCollector,
        ?string $groupPattern = ''
    ) {
        $this->services = $services;
        $this->callableResolver = $callableResolver;
        $this->routeCollector = $routeCollector;
        $this->groupPattern = $groupPattern;
    }

    public function get(string $pattern, $callable): Route
    {
        return $this->map(['GET'], $pattern, $callable);
    }

    /**
     * {@inheritdoc}
     */
    public function post(string $pattern, $callable): Route
    {
        return $this->map(['POST'], $pattern, $callable);
    }

    /**
     * {@inheritdoc}
     */
    public function put(string $pattern, $callable): Route
    {
        return $this->map(['PUT'], $pattern, $callable);
    }

    /**
     * {@inheritdoc}
     */
    public function patch(string $pattern, $callable): Route
    {
        return $this->map(['PATCH'], $pattern, $callable);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $pattern, $callable): Route
    {
        return $this->map(['DELETE'], $pattern, $callable);
    }

    /**
     * {@inheritdoc}
     */
    public function options(string $pattern, $callable): Route
    {
        return $this->map(['OPTIONS'], $pattern, $callable);
    }

    /**
     * {@inheritdoc}
     */
    public function any(string $pattern, $callable): Route
    {
        return $this->map(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], $pattern, $callable);
    }

    /**
     * {@inheritdoc}
     */
    public function map(array $methods, string $pattern, $callable): Route
    {
        return $this->routeCollector->map($methods, "{$this->groupPattern}{$pattern}", $callable);
    }

    /**
     * {@inheritdoc}
     */
    public function group(string $pattern, $callable): RouteGroup
    {
        return $this->routeCollector->group("{$this->groupPattern}{$pattern}", $callable);
    }

    /**
     * {@inheritdoc}
     */
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
