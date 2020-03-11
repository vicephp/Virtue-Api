<?php

namespace Virtue\Api\Routing;

use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use RuntimeException;

class RoutingResults
{
    public const NOT_FOUND = 0;
    public const FOUND = 1;
    public const METHOD_NOT_ALLOWED = 2;
    public const REQUEST_PARAM = '__routingResults__';

    public static function fromRequest(ServerRequest $serverRequest): self
    {
        $routingResults = $serverRequest->getAttribute(self::REQUEST_PARAM);

        if ($routingResults === null) {
            throw new RuntimeException('Cannot create RouteContext before routing has been completed');
        }

        return new self($routingResults);
    }
    /**  @var array */
    private $routingResults = [];

    public function __construct(array $routingResults)
    {
        $this->routingResults = $routingResults;
    }

    public function getResult(): int
    {
        return $this->routingResults[0];
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

    public function getAllowedMethods(): array
    {
        return $this->routingResults[1];
    }
}
