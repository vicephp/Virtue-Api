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
use Slim\Interfaces\MiddlewareDispatcherInterface;
use Slim\Middleware\ErrorMiddleware;
use Virtue\Api\Middleware\FastRouteMiddleware;
use Virtue\Api\Middleware\MiddlewareStack;
use Virtue\Api\Routing\Api;
use Virtue\Api\Routing\RouteCollector;
use Virtue\Api\Routing\RouteContext;
use Virtue\Api\Routing\RouteRunner;
use Virtue\Api\Testing\MiddlewareStackStub;

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
                App::class => function (Locator $services) {
                    return new App($services);
                },
                InvocationStrategyInterface::class => new RequestResponse(),
                AdvancedCallableResolverInterface::class => function (Locator $services) {
                    return new \Slim\CallableResolver($services);
                },
                ResponseFactory::class => function () {
                    return \Slim\Factory\AppFactory::determineResponseFactory();
                },
                ResponseInterface::class => function (Locator $services) {
                    return $services->get(ResponseFactory::class)->createResponse();
                },
                CallableResolverInterface::class => function (Locator $services) {
                    return new \Slim\CallableResolver($services);
                },
                RouteCollector::class => function (Locator $services) {
                    return new RouteCollector($services);
                },
                FastRoute\RouteCollector::class =>
                    new FastRoute\RouteCollector(
                        new FastRoute\RouteParser\Std(),
                        new FastRoute\DataGenerator\GroupCountBased()
                    ),
                FastRouteMiddleware::class => function (Locator $services) {
                    return new FastRouteMiddleware(
                        $services->get(RouteCollector::class),
                        $services->get(FastRoute\RouteCollector::class)
                    );
                },
                ErrorMiddleware::class => function (Locator $services) {
                    return new ErrorMiddleware(
                        $services->get(CallableResolverInterface::class),
                        $services->get(ResponseFactory::class),
                        false,
                        false,
                        false
                    );
                },
                MiddlewareStack::class => function (Locator $services) {
                    return new MiddlewareStack($services->get(RouteRunner::class));
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
                RouteRunner::class => function (Locator $services) {
                    return new Testing\RequestHandlerStub(
                        $services->get(ResponseFactory::class)->createResponse()
                    );
                },
                MiddlewareStack::class => function (Locator $services) {
                    return new Testing\MiddlewareStackStub(
                        $services->get(RouteRunner::class)
                    );
                },
            ]
        );
        $services = $this->container->build();
        /** @var MiddlewareStackStub $stack */
        $stack = $services->get(MiddlewareStack::class);
        /** @var App $app */
        $app = $services->get(App::class);
        $app->add(FastRouteMiddleware::class);

        $this->assertEquals(1, $stack->contains(FastRouteMiddleware::class));
    }

    public function testAddErrorMiddleware()
    {
        $this->container->addDefinitions(
            [
                RouteRunner::class => function (Locator $services) {
                    return new Testing\RequestHandlerStub(
                        $services->get(ResponseFactory::class)->createResponse()
                    );
                },
                MiddlewareStack::class => function (Locator $services) {
                    return new Testing\MiddlewareStackStub(
                        $services->get(RouteRunner::class)
                    );
                },
            ]
        );
        $services = $this->container->build();
        /** @var MiddlewareStackStub $stack */
        $stack = $services->get(MiddlewareStack::class);

        $app = $services->get(App::class);
        $app->add(ErrorMiddleware::class);

        $this->assertEquals(1, $stack->contains(ErrorMiddleware::class));
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

        $services = $this->container->build();
        $app = $services->get(App::class);
        $app->add(FastRouteMiddleware::class);
        $app->group('/foo', function (Api $group) {
            $group->get('/bar', function ($request, $response, $args) {
                return $response;
            })->add('set301');
        })->add('set400');
        $request = $services->get(ServerRequest::class);
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
        $services = $this->container->build();
        $app = $services->get(App::class);
        $app->add(FastRouteMiddleware::class);
        $app->group('/foo', function (Api $group) {
            $group->get('/bar', function ($request, $response, $args) {
                return $response;
            })->add('set301');
        });
        $request = $services->get(ServerRequest::class);
        $request = $request->withUri($request->getUri()->withPath('/foo/bar'));

        $response = $app->handle($request);
        $this->assertEquals(301, $response->getStatusCode());
        $this->assertEquals('Moved Permanently', $response->getReasonPhrase());
    }
}
