<?php

namespace Virtue\Api\Handler;

use Psr\Http\Message\ResponseFactoryInterface as ResponseFactory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Psr\Http\Server\RequestHandlerInterface as HandlesServerRequests;
use Slim\Interfaces\CallableResolverInterface as ResolvesCallables;
use Slim\Interfaces\InvocationStrategyInterface;
use Virtue\Api\Routing\Route;
use Virtue\Api\Routing\RouteParams;

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

    public function handle(ServerRequest $request): Response
    {
        $handler = $this->callables->resolve($request->getAttribute(Route::class)->getHandler());
        $strategy = $this->invoker;

        return $strategy(
            $handler,
            $request,
            $this->factory->createResponse(),
            $request->getAttribute(RouteParams::class)->asArray()
        );
    }
}
