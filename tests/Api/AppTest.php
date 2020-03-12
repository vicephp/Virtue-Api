<?php

namespace Virtue\Api;

use Fig\Http\Message\StatusCodeInterface as StatusCode;
use Psr\Container\ContainerInterface as Locator;
use Psr\Http\Message\ResponseFactoryInterface as ResponseFactory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Psr\Http\Server\RequestHandlerInterface as HandlesServerRequests;
use Slim\Middleware\ErrorMiddleware;
use Slim\ResponseEmitter;
use Virtue\Api\Middleware\CallableMiddleware;
use Virtue\Api\Middleware\RoutingMiddleware;
use Virtue\Api\Middleware\MiddlewareStack;
use Virtue\Api\Routing;
use Virtue\Api\Testing;

class AppTest extends AppTestCase
{
    public function testRun()
    {
        $kernel = $this->container->build();
        $app = $kernel->get(App::class);
        $app->add(RoutingMiddleware::class);
        $app->get('/run', function ($request, $response, $args) {
            return $response;
        });
        $request = $kernel->get(ServerRequest::class);
        $request = $request->withUri($request->getUri()->withPath('/run'));
        $app->run($request);
        /** @var Testing\ResponseEmitterStub $emitter */
        $emitter = $kernel->get(ResponseEmitter::class);
        $response = $emitter->last();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getReasonPhrase());
    }

    public function testAddRoutingMiddleware()
    {
        $this->container->addDefinitions(
            [
                Routing\RouteRunner::class => function (Locator $kernel) {
                    return new Testing\RequestHandlerStub(
                        $kernel->get(ResponseFactory::class)->createResponse()
                    );
                },
                MiddlewareStack::class => function (Locator $kernel) {
                    return new Testing\MiddlewareStackStub(
                        $kernel->get(Routing\RouteRunner::class)
                    );
                },
            ]
        );
        $kernel = $this->container->build();
        /** @var Testing\MiddlewareStackStub $stack */
        $stack = $kernel->get(MiddlewareStack::class);
        /** @var App $app */
        $app = $kernel->get(App::class);
        $app->add(RoutingMiddleware::class);

        $this->assertEquals(1, $stack->contains(RoutingMiddleware::class));
    }

    public function testAddErrorMiddleware()
    {
        $this->container->addDefinitions(
            [
                Routing\RouteRunner::class => function (Locator $kernel) {
                    return new Testing\RequestHandlerStub(
                        $kernel->get(ResponseFactory::class)->createResponse()
                    );
                },
                MiddlewareStack::class => function (Locator $kernel) {
                    return new Testing\MiddlewareStackStub(
                        $kernel->get(Routing\RouteRunner::class)
                    );
                },
            ]
        );
        $kernel = $this->container->build();
        /** @var Testing\MiddlewareStackStub $stack */
        $stack = $kernel->get(MiddlewareStack::class);

        $app = $kernel->get(App::class);
        $app->add(ErrorMiddleware::class);

        $this->assertEquals(1, $stack->contains(ErrorMiddleware::class));
    }
}
