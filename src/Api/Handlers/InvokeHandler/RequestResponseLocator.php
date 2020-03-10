<?php

namespace Virtue\Api\Handlers\InvokeHandler;

use Psr\Container\ContainerInterface as Locator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Interfaces\InvocationStrategyInterface;

class RequestResponseLocator implements InvocationStrategyInterface
{
    /** @var Locator */
    private $services;

    public function __construct(Locator $services)
    {
        $this->services = $services;
    }

    public function __invoke(
        callable $callable,
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $routeArguments
    ): ResponseInterface {
        return $callable($request, $response, $this->services);
    }
}
