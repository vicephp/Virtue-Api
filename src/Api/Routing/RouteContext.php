<?php

namespace Virtue\Api\Routing;

use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use RuntimeException;
use Slim\Interfaces\RouteParserInterface;
use Slim\Routing\RoutingResults;

class RouteContext
{
    public const ROUTE = '__route__';
    public const ROUTE_PARSER = '__routeParser__';
    public const ROUTING_RESULTS = '__routingResults__';
    public const BASE_PATH = '__basePath__';

    public static function fromRequest(ServerRequest $serverRequest): self
    {
        $route = $serverRequest->getAttribute(self::ROUTE);
        $routeParser = $serverRequest->getAttribute(self::ROUTE_PARSER);
        $routingResults = $serverRequest->getAttribute(self::ROUTING_RESULTS);
        $basePath = $serverRequest->getAttribute(self::BASE_PATH);

        if ($routeParser === null || $routingResults === null) {
            throw new RuntimeException('Cannot create RouteContext before routing has been completed');
        }

        return new self($route, $routeParser, $routingResults, $basePath);
    }
    /** @var Route|null */
    private $route;
    /** @var RouteParserInterface */
    private $routeParser;
    /**  @var RoutingResults */
    private $routingResults;
    /** @var string|null */
    private $basePath;

    /**
     * @param Route|null  $route
     * @param RouteParserInterface $routeParser
     * @param RoutingResults       $routingResults
     * @param string|null          $basePath
     */
    private function __construct(
        ?Route $route,
        RouteParserInterface $routeParser,
        RoutingResults $routingResults,
        ?string $basePath = null
    ) {
        $this->route = $route;
        $this->routeParser = $routeParser;
        $this->routingResults = $routingResults;
        $this->basePath = $basePath;
    }

    public function getRoute(): Route
    {
        return $this->route;
    }

    public function getRouteParser(): RouteParserInterface
    {
        return $this->routeParser;
    }

    public function getRoutingResults(): RoutingResults
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
