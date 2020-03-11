<?php

namespace Virtue\Api;

use DI\ContainerBuilder;
use FastRoute;
use Fig\Http\Message\StatusCodeInterface as StatusCode;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface as Locator;
use Psr\Http\Message\ResponseFactoryInterface as ResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Psr\Http\Server\MiddlewareInterface as ServerMiddleware;
use Psr\Http\Server\RequestHandlerInterface as HandlesServerRequests;
use Slim\Factory\ServerRequestCreatorFactory;
use Slim\Handlers\Strategies\RequestResponse;
use Slim\Interfaces\AdvancedCallableResolverInterface;
use Slim\Interfaces\CallableResolverInterface;
use Slim\Interfaces\InvocationStrategyInterface;
use Slim\Middleware\ErrorMiddleware;
use Slim\ResponseEmitter;
use Virtue\Api\Middleware\FastRouteMiddleware;
use Virtue\Api\Middleware\MiddlewareStack;
use Virtue\Api\Routing;
use Virtue\Api\Testing\MiddlewareStackStub;
use Virtue\Api\Testing\ResponseEmitterStub;

class AppTest extends TestCase
{
    /** @var ContainerBuilder */
    private $container;

    protected function mockMiddleware(callable $handle): ServerMiddleware
    {
        $middleware = \Mockery::mock(ServerMiddleware::class);
        $middleware->shouldReceive('process')->andReturnUsing($handle);

        return $middleware;
    }

    protected function setUp()
    {
        $this->container = new \DI\ContainerBuilder();
        $this->container->addDefinitions(
            [
                App::class => function (Locator $kernel) {
                    return new App($kernel);
                },
                InvocationStrategyInterface::class => new RequestResponse(),
                AdvancedCallableResolverInterface::class => function (Locator $kernel) {
                    // Here we should pass a different Locator than the kernel
                    return new \Slim\CallableResolver($kernel);
                },
                ResponseFactory::class => function () {
                    return \Slim\Factory\AppFactory::determineResponseFactory();
                },
                ResponseInterface::class => function (Locator $kernel) {
                    return $kernel->get(ResponseFactory::class)->createResponse();
                },
                CallableResolverInterface::class => function (Locator $kernel) {
                    return new \Slim\CallableResolver($kernel);
                },
                Routing\RouteCollector::class => function (Locator $kernel) {
                    return new Routing\FastRouter($kernel);
                },
                FastRoute\RouteCollector::class => function() {
                    return new FastRoute\RouteCollector(
                        new FastRoute\RouteParser\Std(),
                        new FastRoute\DataGenerator\GroupCountBased()
                    );
                },
                FastRouteMiddleware::class => function (Locator $kernel) {
                    return new FastRouteMiddleware(
                        $kernel->get(FastRoute\RouteCollector::class)
                    );
                },
                ErrorMiddleware::class => function (Locator $kernel) {
                    return new ErrorMiddleware(
                        $kernel->get(CallableResolverInterface::class),
                        $kernel->get(ResponseFactory::class),
                        false,
                        false,
                        false
                    );
                },
                MiddlewareStack::class => function (Locator $kernel) {
                    return new MiddlewareStack($kernel->get(Routing\RouteRunner::class));
                },
                ServerRequest::class => ServerRequestCreatorFactory::create()->createServerRequestFromGlobals(),
                ResponseEmitter::class => function () { return new Testing\ResponseEmitterStub(); },
            ]
        );
    }

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
        /** @var ResponseEmitterStub $emitter */
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
        /** @var MiddlewareStackStub $stack */
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
        /** @var MiddlewareStackStub $stack */
        $stack = $kernel->get(MiddlewareStack::class);

        $app = $kernel->get(App::class);
        $app->add(ErrorMiddleware::class);

        $this->assertEquals(1, $stack->contains(ErrorMiddleware::class));
    }

    public function testFastRouter()
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
        $app->get($path, function ($request, $response, $args) {
            return $response;
        });

        $request = $kernel->get(ServerRequest::class);
        $request = $request->withUri($request->getUri()->withPath($path));
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
                'set400' => $this->mockMiddleware(
                    function (ServerRequest $request, HandlesServerRequests $next) {
                        return $next->handle($request)->withStatus(StatusCode::STATUS_BAD_REQUEST);
                    }
                ),
                'set301' => $this->mockMiddleware(
                    function (ServerRequest $request, HandlesServerRequests $next) {
                        return $next->handle($request)->withStatus(StatusCode::STATUS_MOVED_PERMANENTLY);
                    }
                )
            ]
        );

        $kernel = $this->container->build();
        $app = $kernel->get(App::class);
        $app->add(FastRouteMiddleware::class);
        $app->group('/foo', function (Routing\Api $group) {
            $group->get('/bar', function ($request, $response, $args) {
                return $response;
            })->add('set301');
        })->add('set400');
        $request = $kernel->get(ServerRequest::class);
        $request = $request->withUri($request->getUri()->withPath('/foo/bar'));

        $response = $app->handle($request);
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('Bad Request', $response->getReasonPhrase());
    }

    public function testRouteGroupWithRouteMiddleware()
    {
        $this->container->addDefinitions(
            [
                'set400' => $this->mockMiddleware(
                    function (ServerRequest $request, HandlesServerRequests $next) {
                        return $next->handle($request)->withStatus(StatusCode::STATUS_BAD_REQUEST);
                    }
                ),
                'set301' => $this->mockMiddleware(
                    function (ServerRequest $request, HandlesServerRequests $next) {
                        return $next->handle($request)->withStatus(StatusCode::STATUS_MOVED_PERMANENTLY);
                    }
                )
            ]
        );
        $kernel = $this->container->build();
        $app = $kernel->get(App::class);
        $app->add(FastRouteMiddleware::class);
        $app->group('/foo', function (Routing\Api $group) {
            $group->get('/bar', function ($request, $response, $args) {
                return $response;
            })->add('set301');
        });
        $request = $kernel->get(ServerRequest::class);
        $request = $request->withUri($request->getUri()->withPath('/foo/bar'));

        $response = $app->handle($request);
        $this->assertEquals(301, $response->getStatusCode());
        $this->assertEquals('Moved Permanently', $response->getReasonPhrase());
    }
}
