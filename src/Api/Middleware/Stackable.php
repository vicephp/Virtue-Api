<?php

namespace Virtue\Api\Middleware;

use Psr\Http\Server\RequestHandlerInterface as HandlesServerRequests;

interface Stackable
{
    public function stack(HandlesServerRequests $bottom): MiddlewareStack;
}
