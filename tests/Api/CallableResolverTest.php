<?php

namespace Virtue\Api;

use DI\ContainerBuilder;
use PHPUnit\Framework\TestCase;
use Slim\Handlers\Strategies\RequestResponse;
use Slim\Interfaces\InvocationStrategyInterface;
use Slim\Psr7\Response;
use Virtue\Api\Testing;

class CallableResolverTest extends TestCase
{
    public function testResolve()
    {
        $container = new ContainerBuilder();
        $container->addDefinitions([
            'klaatu' => 'barada',
            Testing\RequestHandlerStub::class => new Testing\RequestHandlerStub(new Response()),
            InvocationStrategyInterface::class => new RequestResponse()
        ]);
        $kernel = $container->build();

        $callable = new CallableResolver($kernel);
        $handler = $callable->resolve(Testing\HomeAction::class);
        $this->assertInstanceOf(Testing\HomeAction::class, $handler[CallableResolver::INSTANCE]);
        $this->assertEquals('__invoke', $handler[CallableResolver::METHOD]);

        $handler = $callable->resolve(Testing\HomeController::class);
        $this->assertInstanceOf(Testing\HomeController::class, $handler[CallableResolver::INSTANCE]);
        $this->assertEquals('__invoke', $handler[CallableResolver::METHOD]);

        $handler = $callable->resolve(sprintf('%s:%s', Testing\HomeController::class, 'home'));
        $this->assertInstanceOf(Testing\HomeController::class, $handler[CallableResolver::INSTANCE]);
        $this->assertEquals('home', $handler[CallableResolver::METHOD]);

        $handler = $callable->resolve(function () { return $this->get('klaatu'); });
        $this->assertEquals('barada', $handler());

        $handler = $callable->resolve($kernel->get(Testing\HomeAction::class));
        $this->assertInstanceOf(Testing\HomeAction::class, $handler);

        $this->expectException(\RuntimeException::class);
        $callable->resolve(Testing\RequestHandlerStub::class);
    }
}
