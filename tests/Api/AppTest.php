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
use Virtue\Api\Middleware\FastRouteMiddleware;
use Virtue\Api\Middleware\MiddlewareStack;
use Virtue\Api\Routing;
use Virtue\Api\Testing;

class AppTest extends AppTestCase
{
    public function testRun()
    {
        $kernel = $this->container->build();
        $app = $kernel->get(App::class);
        $app->add(FastRouteMiddleware::class);
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

    public function testHandle()
    {
        $kernel = $this->container->build();
        $app = $kernel->get(App::class);
        $app->add(FastRouteMiddleware::class);
        $app->get('/handle', function ($request, $response, $args) {
            return $response;
        });
        $request = $kernel->get(ServerRequest::class);
        $request = $request->withUri($request->getUri()->withPath('/handle'));
        $response = $app->handle($request);
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
        $app->add(FastRouteMiddleware::class);

        $this->assertEquals(1, $stack->contains(FastRouteMiddleware::class));
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

    public function testRunFastRouteMiddlewareTwice()
    {
        $this->container->addDefinitions(
            [
                Routing\RouteRunner::class => function (Locator $kernel) {
                    return new Testing\RequestHandlerStub(
                        $kernel->get(ResponseFactory::class)->createResponse()
                    );
                },
            ]
        );
        $kernel = $this->container->build();
        $app = $kernel->get(App::class);
        $app->add(FastRouteMiddleware::class);
        $path = '/run';
        $app->get($path, function (ServerRequest $request, Response $response, array $args) {
            return $response;
        });

        $request = $kernel->get(ServerRequest::class);
        $request = $request->withUri($request->getUri()->withPath($path));
        $app->run($request); // run twice, application should be stateless
        $app->run($request);
        /** @var Testing\RequestHandlerStub $handler */
        $handler = $kernel->get(Routing\RouteRunner::class);
        $context = Routing\RouteContext::fromRequest($handler->last());
        $this->assertNotNull($context->getRoute());
        $this->assertNotNull($context->getRoutingResults());
    }

    public function testRouteGroupWithGroupMiddleware()
    {
        $this->container->addDefinitions(
            [
                'bar' => new CallableMiddleware(
                    function (ServerRequest $request, HandlesServerRequests $next) {
                        $response = $next->handle($request);
                        $response->getBody()->write('bar');
                        return $response;
                    }
                ),
                'foo' => new CallableMiddleware(
                    function (ServerRequest $request, HandlesServerRequests $next) {
                        $response = $next->handle($request);
                        $response->getBody()->write('foo');
                        return $response;
                    }
                )
            ]
        );

        $kernel = $this->container->build();
        $app = $kernel->get(App::class);
        $app->add(FastRouteMiddleware::class);
        $app->group('/foo', function (Routing\Api $group) {
            $group->get('/bar', function (ServerRequest $request, Response $response, array $args) {
                return $response;
            })->add('bar');
        })->add('foo');
        $request = $kernel->get(ServerRequest::class);
        $request = $request->withUri($request->getUri()->withPath('/foo/bar'));

        $response = $app->handle($request);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('barfoo', (string) $response->getBody());
    }
}
