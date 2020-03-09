<?php

namespace Vice;

use Fig\Http\Message\StatusCodeInterface as StatusCode;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Psr\Http\Server\MiddlewareInterface as ServerMiddleware;
use Psr\Http\Server\RequestHandlerInterface as HandlesServerRequests;
use Slim\Psr7\Response;
use Vice\Testing\RequestHandlerStub;

class MiddlewareStackTest extends MockeryTestCase
{
    protected function mockMiddleware(callable $handle): ServerMiddleware
    {
        $middleware = \Mockery::mock(ServerMiddleware::class);
        $middleware->shouldReceive('process')->andReturnUsing($handle);

        return $middleware;
    }

    public function testMiddlewareAdded()
    {
        $set400 = $this->mockMiddleware(
            function(ServerRequest $request, HandlesServerRequests $next) {
                return $next->handle($request)->withStatus(StatusCode::STATUS_BAD_REQUEST);
            }
        );
        $set301 = $this->mockMiddleware(
            function(ServerRequest $request, HandlesServerRequests $next) {
                return $next->handle($request)->withStatus(StatusCode::STATUS_MOVED_PERMANENTLY);
            }
        );

        $stack = new MiddlewareStack();
        $stack->seedMiddlewareStack(new RequestHandlerStub(new Response()));
        $stack->add($set400);
        $stack->add($set301);
        $response = $stack->handle(\Mockery::mock(ServerRequest::class));
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertNotEquals(301, $response->getStatusCode());


    }

    public function testMiddlewareInConstructor()
    {
        $set400 = $this->mockMiddleware(
            function(ServerRequest $request, HandlesServerRequests $next) {
                return $next->handle($request)->withStatus(StatusCode::STATUS_BAD_REQUEST);
            }
        );
        $set301 = $this->mockMiddleware(
            function(ServerRequest $request, HandlesServerRequests $next) {
                return $next->handle($request)->withStatus(StatusCode::STATUS_MOVED_PERMANENTLY);
            }
        );

        $stack = new MiddlewareStack([$set400, $set301]);
        $stack->seedMiddlewareStack(new RequestHandlerStub(new Response()));
        $response = $stack->handle(\Mockery::mock(ServerRequest::class));
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertNotEquals(301, $response->getStatusCode());
    }
}
