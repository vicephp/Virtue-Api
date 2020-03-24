<?php

namespace Virtue\Api\Middleware;

use Psr\Container\ContainerInterface as Locator;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Psr\Http\Server\MiddlewareInterface as ServerMiddleware;
use Psr\Http\Server\RequestHandlerInterface as HandlesServerRequests;
use Slim\Exception\HttpUnauthorizedException;
use Virtue\Access;

class Authorization implements ServerMiddleware
{
    /** @var Locator */
    private $services;

    public function __construct(Locator $services)
    {
        $this->services = $services;
    }

    public function process(ServerRequest $request, HandlesServerRequests $handler): Response
    {
        /** @var Access\Identity $user */
        $user = $this->services->get(Access\Identity::class);

        if ($user->isAuthenticated()) {
            return $handler->handle($request);
        }

        throw new HttpUnauthorizedException($request);
    }
}
