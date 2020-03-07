<?php

namespace Vice;

use Fig\Http\Message\StatusCodeInterface as StatusCode;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

class MiddlewareStackTest extends MockeryTestCase
{
    protected function mockMiddleware(callable $handle) {
        $middleware = \Mockery::mock(MiddlewareInterface::class);
        $middleware->shouldReceive('process')->andReturnUsing($handle);

        return $middleware;
    }

    public function testStack()
    {
        $set400 = $this->mockMiddleware(
            function(ServerRequestInterface $request, RequestHandlerInterface $next) {
                return $next->handle($request)->withStatus(StatusCode::STATUS_BAD_REQUEST);
            }
        );
        $set301 = $this->mockMiddleware(
            function(ServerRequestInterface $request, RequestHandlerInterface $next) {
                return $next->handle($request)->withStatus(StatusCode::STATUS_MOVED_PERMANENTLY);
            }
        );

        $stack = new MiddlewareStack();
        $stack->seedMiddlewareStack($kernel = \Mockery::mock(RequestHandlerInterface::class));
        $stack->add($set400);
        $stack->add($set301);
        $kernel->shouldReceive('handle')->andReturnUsing(
            function (ServerRequestInterface $request) {
                return new Response();
            }
        );
        $response = $stack->handle(\Mockery::mock(ServerRequestInterface::class));
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertNotEquals(301, $response->getStatusCode());
    }
}
