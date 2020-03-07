<?php

namespace Vice;

use DI\Container;
use Psr\Http\Message\ResponseFactoryInterface;
use Slim\Factory\ServerRequestCreatorFactory;
use Slim\Interfaces\CallableResolverInterface;
use Slim\Interfaces\MiddlewareDispatcherInterface;
use Slim\Interfaces\RouteCollectorInterface;
use Slim\Interfaces\RouteResolverInterface;
use Slim\Routing\RouteCollector;
use Slim\Routing\RouteResolver;
use PHPUnit\Framework\TestCase;

class AppTest extends TestCase
{
    public function testRun()
    {
        $builder = new \DI\ContainerBuilder();
        $builder->addDefinitions(
            [
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

        $app = new \Vice\App($builder->build());
        try {
            $app->run();
        } catch (\Slim\Exception\HttpNotFoundException $notFound) {
            $this->assertEquals('', $notFound->getRequest()->getUri()->getPath());
        }
    }
}
