<?php

namespace Virtue\Api;

use DI\ContainerBuilder;
use FastRoute;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface as Locator;
use Psr\Http\Message\ResponseFactoryInterface as ResponseFactory;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Slim\Factory\ServerRequestCreatorFactory;
use Slim\Handlers\Strategies\RequestResponse;
use Slim\Interfaces\AdvancedCallableResolverInterface;
use Slim\Interfaces\CallableResolverInterface;
use Slim\Interfaces\InvocationStrategyInterface;
use Slim\Middleware\ErrorMiddleware;
use Slim\ResponseEmitter;
use Virtue\Api\Middleware\RoutingMiddleware;
use Virtue\Api\Middleware\MiddlewareContainer;

class AppTestCase extends TestCase
{
    /** @var ContainerBuilder */
    protected $container;

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
                RoutingMiddleware::class => function (Locator $kernel) {
                    return new RoutingMiddleware(
                        $kernel->get(Routing\RouteCollector::class)
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
                ServerRequest::class => ServerRequestCreatorFactory::create()->createServerRequestFromGlobals(),
                ResponseEmitter::class => function () { return new Testing\ResponseEmitterStub(); },
            ]
        );
    }
}
