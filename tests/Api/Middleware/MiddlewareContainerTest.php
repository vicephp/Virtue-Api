<?php

namespace Virtue\Api\Middleware;

use GuzzleHttp\Psr7\Response;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Psr\Http\Server\RequestHandlerInterface as HandlesServerRequests;
use Virtue\Api\Testing\KlaatuBaradaNword;
use Virtue\Api\Testing\RequestHandler;

class MiddlewareContainerTest extends MockeryTestCase
{
    private $middlewares = [];

    protected function setUp()
    {
        parent::setUp();
        $this->middlewares = [
            'klaatu' => new CallableMiddleware(
                function (ServerRequest $request, HandlesServerRequests $next) {
                    $response = $next->handle($request);
                    $response->getBody()->write('klaatu ');
                    return $response;
                }
            ),
            'barada' => new CallableMiddleware(
                function (ServerRequest $request, HandlesServerRequests $next) {
                    $response = $next->handle($request);
                    $response->getBody()->write('barada ');
                    return $response;
                }
            ),
            'nikto' => new CallableMiddleware(
                function (ServerRequest $request, HandlesServerRequests $next) {
                    $response = $next->handle($request);
                    $response->getBody()->write('nikto ');
                    return $response;
                }
            )
        ];
    }

    public function testMiddlewareInConstructor()
    {
        $container = new MiddlewareContainer(
            new RequestHandler(new Response()),
            [$this->middlewares['nikto'], $this->middlewares['barada'], $this->middlewares['klaatu']]
        );

        $response = $container->handle(\Mockery::mock(ServerRequest::class));
        $this->assertEquals('klaatu barada nikto ', (string) $response->getBody(), new KlaatuBaradaNword());
    }

    public function testMiddlewareAdded()
    {
        $middlewares = new MiddlewareContainer(new RequestHandler(new Response()));
        $middlewares->append($this->middlewares['barada']);
        $middlewares->append($this->middlewares['klaatu']);
        $middlewares->prepend($this->middlewares['nikto']);

        $response = $middlewares->handle(\Mockery::mock(ServerRequest::class));
        $this->assertEquals('klaatu barada nikto ', (string) $response->getBody(), new KlaatuBaradaNword());
    }

    public function testStack()
    {
        $aStack = new MiddlewareContainer(new RequestHandler(new Response()));
        $aStack->append($this->middlewares['barada']);
        $aStack->append($this->middlewares['klaatu']);
        $bStack = new MiddlewareContainer(new RequestHandler(new Response()));
        $bStack->append($this->middlewares['nikto']);

        $response = $bStack->stack($aStack)->handle(\Mockery::mock(ServerRequest::class));
        $this->assertEquals('klaatu barada nikto ', (string) $response->getBody(), new KlaatuBaradaNword());

        $this->expectException(\InvalidArgumentException::class);
        $aStack->stack($aStack);
    }
}
