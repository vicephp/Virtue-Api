<?php

namespace Virtue\Api\Handler;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Psr\Http\Server\RequestHandlerInterface as HandlesServerRequests;
use Slim\Interfaces\CallableResolverInterface as ResolvesCallables;
use Virtue\Api\Routing\Route;
use Virtue\Api\Routing\RouteParams;
use Virtue\View\ProvidesViews;

class RequestViewParams implements HandlesServerRequests
{
    /** @var ResolvesCallables */
    private $callables;
    /** @var ProvidesViews */
    private $view;

    /**
     * @param ResolvesCallables $callables
     * @param ProvidesViews $view
     */
    public function __construct(ResolvesCallables $callables, ProvidesViews $view)
    {
        $this->callables = $callables;
        $this->view = $view;
    }

    public function handle(ServerRequest $request): Response
    {
        $handle = $this->callables->resolve($request->getAttribute(Route::class)->getHandler());

        return $handle($request, $this->view, $request->getAttribute(RouteParams::class));
    }
}
