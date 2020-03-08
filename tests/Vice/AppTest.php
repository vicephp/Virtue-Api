<?php

namespace Vice;

use Psr\Container\ContainerInterface as Container;
use DI\ContainerBuilder;
use Psr\Http\Message\ResponseFactoryInterface;
use Slim\Factory\ServerRequestCreatorFactory;
use Slim\Interfaces\CallableResolverInterface;
use Slim\Interfaces\MiddlewareDispatcherInterface;
use Slim\Interfaces\RouteCollectorInterface;
use Slim\Interfaces\RouteResolverInterface;
use Slim\Middleware\ErrorMiddleware;
use Slim\Middleware\RoutingMiddleware;
use Slim\Routing\RouteCollector;
use Slim\Routing\RouteResolver;
use PHPUnit\Framework\TestCase;
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
                App::class => function (Container $c) {
                    return new App($c);
                },
                ResponseFactoryInterface::class => function () {
                    return \Slim\Factory\AppFactory::determineResponseFactory();
                },
                CallableResolverInterface::class => function (Container $c) {
                    return new \Slim\CallableResolver($c);
                },
                RouteCollectorInterface::class => function (Container $c) {
                    return new RouteCollector(
                        $c->get(ResponseFactoryInterface::class),
                        $c->get(CallableResolverInterface::class),
                        $c
                    );
                },
                MiddlewareDispatcherInterface::class => function () {
                    return new MiddlewareStack();
                },
                RouteResolverInterface::class => function (Container $c) {
                    return new RouteResolver(
                        $c->get(RouteCollectorInterface::class)
                    );
                },
                \Psr\Http\Message\ServerRequestInterface::class => ServerRequestCreatorFactory::create()->createServerRequestFromGlobals()
            ]
        );
    }

    public function testRun()
    {
        $app = $this->container->build()->get(App::class);
        try {
            $app->run();
        } catch (\Slim\Exception\HttpNotFoundException $notFound) {
            $this->assertEquals('', $notFound->getRequest()->getUri()->getPath());
        }
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
        $container = $this->container->build();
        /** @var MiddlewareStackStub $stack */
        $stack = $container->get(MiddlewareDispatcherInterface::class);
        /** @var App $app */
        $app = $container->get(App::class);
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
        $container = $this->container->build();
        /** @var MiddlewareStackStub $stack */
        $stack = $container->get(MiddlewareDispatcherInterface::class);
        /** @var App $app */
        $app = $container->get(App::class);
        $app->addErrorMiddleware(false, false, false);

        $this->assertEquals(true, $stack->contains(ErrorMiddleware::class));
    }
}
