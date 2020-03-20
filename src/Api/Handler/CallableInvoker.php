<?php

namespace Virtue\Api\Handler;

use Psr\Http\Message\ResponseFactoryInterface as ResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface as HandlesServerRequests;
use Slim\Interfaces\CallableResolverInterface as ResolvesCallables;
use Slim\Interfaces\InvocationStrategyInterface;
use Virtue\Api\ServerRequest\RoutingResults;

class CallableInvoker implements HandlesServerRequests
{
    /** @var ResolvesCallables */
    private $callables;
    /** @var ResponseFactory */
    private $factory;
    /** @var InvocationStrategyInterface */
    private $invoker;

    public function __construct(
        ResolvesCallables $callables,
        ResponseFactory $factory,
        InvocationStrategyInterface $invoker
    ) {
        $this->callables = $callables;
        $this->factory = $factory;
        $this->invoker = $invoker;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $results = RoutingResults::ofRequest($request);
        $handler = $this->callables->resolve($results->getRoute()->getHandler());
        $strategy = $this->invoker;

        return $strategy($handler, $request, $this->factory->createResponse(), $results->getRouteArgs());
    }
}
