<?php

namespace Vice;

use DI\ContainerBuilder;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface as Locator;
use Psr\Http\Message\ResponseFactoryInterface as ResponseFactory;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Slim\Factory\ServerRequestCreatorFactory;
use Slim\Interfaces\CallableResolverInterface;
use Slim\Interfaces\MiddlewareDispatcherInterface;
use Slim\Interfaces\RouteCollectorInterface;
use Slim\Interfaces\RouteResolverInterface;
use Slim\Middleware\ErrorMiddleware;
use Slim\Middleware\RoutingMiddleware;
use Slim\Routing\RouteCollector;
use Slim\Routing\RouteResolver;
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
                MiddlewareDispatcherInterface::class => function (Locator $locator) {
                    $stack = new MiddlewareStack();
                    $stack->seedMiddlewareStack(
                        $locator->get(RouteRunner::class)
                    );

                    return $stack;
                },
                RouteResolverInterface::class => function (Locator $locator) {
                    return new RouteResolver(
                        $locator->get(RouteCollectorInterface::class)
                    );
                },
                RouteRunner::class => function (Locator $locator) {
                    $responseFactory = $locator->get(ResponseFactory::class);
                    return new Testing\RequestHandlerStub(
                        $responseFactory->createResponse()
                    );
                },
                ServerRequest::class => ServerRequestCreatorFactory::create()->createServerRequestFromGlobals()
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
