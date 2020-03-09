<?php

namespace Vice;

use DI\ContainerBuilder;
use FastRoute\RouteCollector as FastRouteCollector;
use FastRoute\RouteParser\Std;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface as Locator;
use Psr\Http\Message\ResponseFactoryInterface as ResponseFactory;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Slim\Factory\ServerRequestCreatorFactory;
use Slim\Interfaces\CallableResolverInterface;
use Slim\Interfaces\DispatcherInterface;
use Slim\Interfaces\MiddlewareDispatcherInterface;
use Slim\Interfaces\RouteCollectorInterface;
use Slim\Interfaces\RouteParserInterface;
use Slim\Interfaces\RouteResolverInterface;
use Slim\Middleware\ErrorMiddleware;
use Slim\Middleware\RoutingMiddleware;
use Slim\Routing\FastRouteDispatcher;
use Slim\Routing\RouteCollector;
use Vice\Routing\Dispatcher;
use Vice\Routing\RouteResolver;
use Vice\Routing\RouteRunner;
use Vice\Testing\MiddlewareStackStub;

class AppTest extends TestCase
{
    /** @var ContainerBuilder */
    private $container;

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
                RouteParserInterface::class => function (Locator $locator) {
                    return $locator->get(RouteCollectorInterface::class)->getRouteParser();
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
                    $stack = new MiddlewareStack();
                    $stack->seedMiddlewareStack(
                        $locator->get(RouteRunner::class)
                    );

                    return $stack;
                },
                RouteResolverInterface::class => function (Locator $locator) {
                    return new RouteResolver(
                        $locator->get(RouteCollectorInterface::class),
                        $locator->get(DispatcherInterface::class)
                    );
                },
                RouteRunner::class => function (Locator $locator) {
                    $responseFactory = $locator->get(ResponseFactory::class);
                    return new Testing\RequestHandlerStub(
                        $responseFactory->createResponse()
                    );
                },
                ServerRequest::class => ServerRequestCreatorFactory::create()->createServerRequestFromGlobals(),
                FastRouteDispatcher::class => function (Locator $locator) {
                    $routeDefinitionCallback = function (FastRouteCollector $r) use ($locator) {
                        $basePath = $locator->get(RouteCollectorInterface::class)->getBasePath();
                        foreach ($locator->get(RouteCollectorInterface::class)->getRoutes() as $route) {
                            $r->addRoute($route->getMethods(), $basePath . $route->getPattern(), $route->getIdentifier());
                        }
                    };
                    /** @var FastRouteDispatcher $dispatcher */
                    $dispatcher = \FastRoute\simpleDispatcher($routeDefinitionCallback, [
                        'dispatcher' => FastRouteDispatcher::class,
                         'routeParser' => new Std(),
                    ]);

                    return $dispatcher;
                },
                DispatcherInterface::class => function (Locator $locator) {
                    return new Dispatcher(
                        $locator->get(RouteCollectorInterface::class),
                        $locator->get(FastRouteDispatcher::class)
                    );
                },
            ]
        );
    }

    public function testRun()
    {
        $services = $this->container->build();
        $app = $services->get(App::class);
        $response = $app->handle($services->get(ServerRequest::class));
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
        $app->addRoutingMiddleware();

        $this->assertEquals(true, $stack->contains(RoutingMiddleware::class));
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
        /** @var App $app */
        $app = $services->get(App::class);
        $app->addErrorMiddleware(false, false, false);

        $this->assertEquals(true, $stack->contains(ErrorMiddleware::class));
    }
}
