<?php

namespace Virtue\Api;

use DI\ContainerBuilder;
use FastRoute;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface as Locator;
use Psr\Http\Message\ResponseFactoryInterface as ResponseFactory;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Psr\Http\Server\MiddlewareInterface as ServerMiddleware;
use Slim\Factory\ServerRequestCreatorFactory;
use Slim\Handlers\Strategies\RequestResponse;
use Slim\Interfaces\AdvancedCallableResolverInterface;
use Slim\Interfaces\CallableResolverInterface;
use Slim\Interfaces\InvocationStrategyInterface;
use Slim\Middleware\ErrorMiddleware;
use Slim\ResponseEmitter;
use Virtue\Api\Middleware\FastRouteMiddleware;
use Virtue\Api\Middleware\MiddlewareStack;

class AppTestCase extends TestCase
{
    /** @var ContainerBuilder */
    protected $container;

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
}
