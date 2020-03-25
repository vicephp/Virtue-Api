<?php

namespace Virtue\Api\Handler;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Psr\Http\Server\RequestHandlerInterface as HandlesServerRequests;
use Slim\Interfaces\CallableResolverInterface as ResolvesCallables;
use Psr\Http\Message\ResponseFactoryInterface as ResponseFactory;
use Virtue\Api\Routing\Route;
use Virtue\Api\Routing\RouteParams;

class RequestResponseParams implements HandlesServerRequests
{
    /** @var ResolvesCallables */
    private $callables;
    /** @var ResponseFactory */
    private $factory;

    /**
     * @param ResolvesCallables $callables
     * @param ResponseFactory $factory
     */
    public function __construct(ResolvesCallables $callables, ResponseFactory $factory)
    {
        $this->callables = $callables;
        $this->factory = $factory;
    }

    public function handle(ServerRequest $request): Response
    {
        $handle = $this->callables->resolve($request->getAttribute(Route::class)->getHandler());

        return $handle($request, $this->factory->createResponse(), $request->getAttribute(RouteParams::class));
    }
}
