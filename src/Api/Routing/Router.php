<?php

namespace Virtue\Api\Routing;

use Psr\Http\Message\ServerRequestInterface as ServerRequest;

interface Router
{
    public function route(ServerRequest $request): ServerRequest;
}
