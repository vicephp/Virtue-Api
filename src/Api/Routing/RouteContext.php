<?php

namespace Virtue\Api\Routing;

use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use RuntimeException;

class RouteContext
{
    public const ROUTING_RESULTS = '__routingResults__';

    public static function fromRequest(ServerRequest $serverRequest): self
    {
        $routingResults = $serverRequest->getAttribute(self::ROUTING_RESULTS);

        if ($routingResults === null) {
            throw new RuntimeException('Cannot create RouteContext before routing has been completed');
        }

        return new self($routingResults);
    }
    /**  @var array */
    private $routingResults = [];

    private function __construct(
        array $routingResults
    ) {
        $this->routingResults = $routingResults;
    }

    public function getRoutingResults(): array
    {
        return $this->routingResults;
    }

    public function getRoute(): Route
    {
        return $this->routingResults[1];
    }

    public function getRouteArgs(): array
    {
        return $this->routingResults[2];
    }
}
