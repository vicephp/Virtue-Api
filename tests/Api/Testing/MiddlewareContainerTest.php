<?php

namespace Virtue\Api\Testing;

use GuzzleHttp\Psr7\Response;
use Virtue\Api\Middleware\CallableMiddleware;
use Virtue\Api\TestCase;

class MiddlewareContainerTest extends TestCase
{
    public function testContains()
    {
        $middlewares = new MiddlewareContainerStub(
            new CallableHandler(
                function ($request) {
                    return new Response();
                }
           ),
            [new CallableMiddleware(function () {})]
        );

        $this->assertEquals(1, $middlewares->contains(CallableMiddleware::class));
    }
}
