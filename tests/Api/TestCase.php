<?php

namespace Virtue\Api;

use DI\ContainerBuilder;
use FastRoute;
use Psr\Container\ContainerInterface as Locator;
use Psr\Http\Message\ResponseFactoryInterface as ResponseFactory;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Psr\Http\Server\RequestHandlerInterface as HandlesServerRequests;
use Slim\Factory\ServerRequestCreatorFactory;
use Slim\Interfaces\CallableResolverInterface;
use Slim\Middleware\ErrorMiddleware;
use Slim\ResponseEmitter;
use Virtue\Api\Handler\RequestResponseParamsArray;
use Virtue\Api\Middleware;
use Virtue\Api\Routing;

class TestCase extends \PHPUnit\Framework\TestCase
{
    /** @var ContainerBuilder */
    protected $container;

    protected function setUp(): void
    {
        $this->container = new \DI\ContainerBuilder();
        $this->container->addDefinitions(
            [
                App::class => function (Locator $kernel) {
                    return new App(
                        $kernel,
                        $kernel->get(Routing\RouteCollector::class),
                        new Middleware\MiddlewareContainer(
                            $kernel->get(Routing\RouteRunner::class)
                        )
                    );
                },
                HandlesServerRequests::class => function (Locator $kernel) {
                    return new RequestResponseParamsArray(
                        $kernel->get(CallableResolverInterface::class),
                        $kernel->get(ResponseFactory::class)
                    );
                },
                CallableResolverInterface::class => function (Locator $kernel) {
                    // Here we should pass a different Locator than the kernel
                    return new Handler\CallableResolver($kernel);
                },
                ResponseFactory::class => function () {
                    return new \Http\Factory\Guzzle\ResponseFactory();
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
                Middleware\Routing::class => function (Locator $kernel) {
                    return new Middleware\Routing(
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
                ResponseEmitter::class => function () { return new Testing\ResponseEmitter(); },
            ]
        );
    }
}
