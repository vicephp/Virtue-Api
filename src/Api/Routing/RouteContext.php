<?php

namespace Virtue\Api\Routing;

use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use RuntimeException;

class RouteContext
{
    public const ROUTE = '__route__';
    public const ROUTE_PARSER = '__routeParser__';
    public const ROUTING_RESULTS = '__routingResults__';
    public const BASE_PATH = '__basePath__';

    public static function fromRequest(ServerRequest $serverRequest): self
    {
        $route = $serverRequest->getAttribute(self::ROUTE);
        $routingResults = $serverRequest->getAttribute(self::ROUTING_RESULTS);
        $basePath = $serverRequest->getAttribute(self::BASE_PATH);

        if ($route === null || $routingResults === null) {
            throw new RuntimeException('Cannot create RouteContext before routing has been completed');
        }

        return new self($route, $routingResults, $basePath);
    }
    /** @var Route|null */
    private $route;
    /**  @var RoutingResults */
    private $routingResults;
    /** @var string|null */
    private $basePath;

    private function __construct(
        Route $route,
        array $routingResults,
        ?string $basePath = null
    ) {
        $this->route = $route;
        $this->routingResults = $routingResults;
        $this->basePath = $basePath;
    }

    public function getRoute(): Route
    {
        return $this->route;
    }

    public function getRoutingResults(): array
    {
        return $this->routingResults;
    }

    public function getBasePath(): string
    {
        if ($this->basePath === null) {
            throw new RuntimeException('No base path defined.');
        }
        return $this->basePath;
    }
}
