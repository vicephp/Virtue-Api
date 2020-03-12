<?php

namespace Virtue\Api\Routing;

use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use RuntimeException;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpNotFoundException;

class RoutingResults
{
    public const NOT_FOUND = 0;
    public const FOUND = 1;
    public const METHOD_NOT_ALLOWED = 2;
    private const REQUEST_ATTR = '__routingResults__';
    /** @var array */
    private $routingResults = [];

    public static function fromRequest(ServerRequest $request): self
    {
        $routingResults = $request->getAttribute(self::REQUEST_ATTR);

        if ($routingResults === null) {
            throw new RuntimeException('Cannot create RoutingResults before routing has been completed.');
        }

        return new self($routingResults);
    }

    public function __construct(array $routingResults)
    {
        $this->routingResults = $routingResults;
    }

    public function getRoute(): Route
    {
        return $this->routingResults[1];
    }

    public function getRouteArgs(): array
    {
        return $this->routingResults[2];
    }

    /**
     * @param  ServerRequest $request
     * @return ServerRequest
     *
     * @throws HttpNotFoundException
     * @throws HttpMethodNotAllowedException
     * @throws RuntimeException
     */
    public function withRequest(ServerRequest $request): ServerRequest
    {
        switch ($this->routingResults[0]) {
            case self::FOUND:
                return $request->withAttribute(self::REQUEST_ATTR, $this->routingResults);

            case self::NOT_FOUND:
                throw new HttpNotFoundException($request);

            case self::METHOD_NOT_ALLOWED:
                $exception = new HttpMethodNotAllowedException($request);
                $exception->setAllowedMethods($this->routingResults[1]);
                throw $exception;

            default:
                throw new RuntimeException('An unexpected error occurred while performing routing.');
        }
    }
}
