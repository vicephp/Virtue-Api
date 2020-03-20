<?php

namespace Virtue\Api;

use DI\ContainerBuilder;
use FastRoute;
use Psr\Container\ContainerInterface as Locator;
use Psr\Http\Message\ResponseFactoryInterface as ResponseFactory;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Slim\Factory\ServerRequestCreatorFactory;
use Slim\Handlers\Strategies\RequestResponse;
use Slim\Interfaces\CallableResolverInterface;
use Slim\Interfaces\InvocationStrategyInterface;
use Slim\Middleware\ErrorMiddleware;
use Slim\ResponseEmitter;
use Virtue\Api\Middleware\MiddlewareContainer;
use Virtue\Api\Middleware\Router;
use Virtue\Api\Routing\RouteCollector;
use Virtue\Api\Routing\RouteRunner;

class TestCase extends \PHPUnit\Framework\TestCase
{
    /** @var ContainerBuilder */
    protected $container;

    protected function setUp()
    {
        $this->container = new \DI\ContainerBuilder();
        $this->container->addDefinitions(
            [
                App::class => function (Locator $kernel) {
                    return new App(
                        $kernel,
                        $kernel->get(RouteCollector::class),
                        new MiddlewareContainer($kernel->get(RouteRunner::class))
                    );
                },
                InvocationStrategyInterface::class => new RequestResponse(),
                CallableResolverInterface::class => function (Locator $kernel) {
                    // Here we should pass a different Locator than the kernel
                    return new Handler\CallableResolver($kernel);
                },
                ResponseFactory::class => function () {
                    return \Slim\Factory\AppFactory::determineResponseFactory();
                },
                Routing\RouteCollector::class => function (Locator $kernel) {
                    return new Routing\FastRouter(
                        $kernel,
                        new FastRoute\RouteCollector(
                            new FastRoute\RouteParser\Std(),
                            new FastRoute\DataGenerator\GroupCountBased()
                        )
                    );
                },
                Router::class => function (Locator $kernel) {
                    return new Router(
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
