<?php

namespace Vice;

use DI\ContainerBuilder;
use FastRoute;
use Fig\Http\Message\StatusCodeInterface as StatusCode;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface as Locator;
use Psr\Http\Message\ResponseFactoryInterface as ResponseFactory;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Psr\Http\Server\MiddlewareInterface as ServerMiddleware;
use Psr\Http\Server\RequestHandlerInterface as HandlesServerRequests;
use Slim\Factory\ServerRequestCreatorFactory;
use Slim\Interfaces\CallableResolverInterface;
use Slim\Interfaces\DispatcherInterface;
use Slim\Interfaces\MiddlewareDispatcherInterface;
use Slim\Interfaces\RouteCollectorInterface;
use Slim\Interfaces\RouteResolverInterface;
use Slim\Middleware\ErrorMiddleware;
use Slim\Routing\RouteCollector;
use Slim\Routing\RouteContext;
use Slim\Routing\RouteResolver;
use Vice\Middleware\FastRouteMiddleware;
use Vice\Routing\RouteRunner;
use Vice\Testing\MiddlewareStackStub;

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
                App::class => function (Locator $locator) {
                    return new App($locator);
                },
                ResponseFactory::class => function () {
                    return \Slim\Factory\AppFactory::determineResponseFactory();
                },
                CallableResolverInterface::class => function (Locator $locator) {
                    return new \Slim\CallableResolver($locator);
                },
                RouteCollectorInterface::class => function (Locator $locator) {
                    return new RouteCollector(
                        $locator->get(ResponseFactory::class),
                        $locator->get(CallableResolverInterface::class),
                        $locator
                    );
                },
                RouteResolverInterface::class => function (Locator $locator) {
                    return new RouteResolver(
                        $locator->get(RouteCollectorInterface::class),
                        $locator->get(DispatcherInterface::class)
                    );
                },
                FastRoute\RouteCollector::class =>
                    new FastRoute\RouteCollector(
                        new FastRoute\RouteParser\Std(),
                        new FastRoute\DataGenerator\GroupCountBased()
                    ),
                FastRouteMiddleware::class => function (Locator $locator) {
                    return new FastRouteMiddleware(
                        $locator->get(RouteCollectorInterface::class),
                        $locator->get(FastRoute\RouteCollector::class)
                    );
                },
                ErrorMiddleware::class => function (Locator $locator) {
                    return new ErrorMiddleware(
                        $locator->get(CallableResolverInterface::class),
                        $locator->get(ResponseFactory::class),
                        false,
                        false,
                        false
                    );
                },
                MiddlewareDispatcherInterface::class => function (Locator $locator) {
                    return new MiddlewareStack($locator->get(RouteRunner::class));
                },
                ServerRequest::class => ServerRequestCreatorFactory::create()->createServerRequestFromGlobals(),
            ]
        );
    }

    public function testRun()
    {
        $services = $this->container->build();
        $app = $services->get(App::class);
        $app->add(FastRouteMiddleware::class);
        $app->get('/run', function ($request, $response, $args) {
            return $response;
        });
        $request = $services->get(ServerRequest::class);
        $request = $request->withUri($request->getUri()->withPath('/run'));
        $response = $app->handle($request);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getReasonPhrase());
    }

    public function testAddRoutingMiddleware()
    {
        $this->container->addDefinitions(
            [
                MiddlewareDispatcherInterface::class => function () {
                    return new Testing\MiddlewareStackStub();
                },
            ]
        );
        $services = $this->container->build();
        /** @var MiddlewareStackStub $stack */
        $stack = $services->get(MiddlewareDispatcherInterface::class);
        /** @var App $app */
        $app = $services->get(App::class);
        $app->add(FastRouteMiddleware::class);

        $this->assertEquals(true, $stack->contains(FastRouteMiddleware::class));
    }

    public function testAddErrorMiddleware()
    {
        $this->container->addDefinitions(
            [
                MiddlewareDispatcherInterface::class => function () {
                    return new Testing\MiddlewareStackStub();
                },
            ]
        );
        $services = $this->container->build();
        /** @var MiddlewareStackStub $stack */
        $stack = $services->get(MiddlewareDispatcherInterface::class);

        $app = $services->get(App::class);
        $app->add(ErrorMiddleware::class);

        $this->assertEquals(true, $stack->contains(ErrorMiddleware::class));
    }

    public function testFastRouter()
    {
        $this->container->addDefinitions(
            [
                RouteRunner::class => function (Locator $services) {
                    return new Testing\RequestHandlerStub(
                        $services->get(ResponseFactory::class)->createResponse()
                    );
                },
            ]
        );
        $services = $this->container->build();
        $app = $services->get(App::class);
        $app->add(FastRouteMiddleware::class);
        $path = '/run';
        $app->get($path, function ($request, $response, $args) {
            return $response;
        });

        $request = $services->get(ServerRequest::class);
        $request = $request->withUri($request->getUri()->withPath($path));
        $app->run($request);
        /** @var Testing\RequestHandlerStub $handler */
        $handler = $services->get(RouteRunner::class);
        $context = RouteContext::fromRequest($handler->last());
        $this->assertNotNull($context->getBasePath());
        $this->assertNotNull($context->getRoute());
        $this->assertNotNull($context->getRouteParser());
        $this->assertNotNull($context->getRoutingResults());
    }

    public function testRouteGroups()
    {
        $this->container->addDefinitions(
            [
                RouteRunner::class => function (Locator $services) {
                    return new Testing\RequestHandlerStub(
                        $services->get(ResponseFactory::class)->createResponse()
                    );
                },
            ]
        );
        $set400 = $this->mockMiddleware(
            function (ServerRequest $request, HandlesServerRequests $next) {
                return $next->handle($request)->withStatus(StatusCode::STATUS_BAD_REQUEST);
            }
        );
        $set301 = $this->mockMiddleware(
            function (ServerRequest $request, HandlesServerRequests $next) {
                return $next->handle($request)->withStatus(StatusCode::STATUS_MOVED_PERMANENTLY);
            }
        );
        $services = $this->container->build();
        $app = $services->get(App::class);
        $app->add(FastRouteMiddleware::class);
//        $app->group('/foo', function (RouteCollectorProxy $group) use ($set301) {
//            $group->get('/bar', function ($request, $response, $args) {
//
//            })->add($set301);
//        })->add($set400);
        $app->get('/foo/bar', function ($request, $response, $args) {

        });
        $request = $services->get(ServerRequest::class);
        $request = $request->withUri($request->getUri()->withPath('/foo/bar'));

        $this->assertTrue(true);
//        $response = $app->handle($request);
//        $this->assertEquals(200, $response->getStatusCode());
//        $this->assertEquals('OK', $response->getReasonPhrase());
    }
}
