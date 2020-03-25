<?php

namespace Virtue\Api\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Slim\Exception\HttpException;
use Virtue\Api\TestCase;
use Virtue\Api\Testing;

class ErrorLoggingTest extends TestCase
{
    public function testLogging()
    {
        $kernel = $this->container->build();
        $request = $kernel->get(ServerRequest::class);

        $errorHandling = new Testing\ErrorHandlingStub(
            $kernel->get(ResponseFactoryInterface::class)
        );

        $logger = new Testing\LoggerStub();
        $errorHandling->process(
            $request,
            new RequestHandler(
                new ErrorLogging($logger),
                new Testing\CallableHandlerStub(
                    function (ServerRequest $request) {
                        throw new \RuntimeException('anError');
                    }
                )
            )
        );

        $this->assertEquals(1, $logger->contains('error', 'anError'));
    }

    public function testPassesHttpError()
    {
        $kernel = $this->container->build();
        $request = $kernel->get(ServerRequest::class);

        $logger = new Testing\LoggerStub();
        $errorLogging = new ErrorLogging($logger);

        $this->expectException(HttpException::class);
        $errorLogging->process(
            $request,
            new Testing\CallableHandlerStub(
                function (ServerRequest $request) {
                    throw new HttpException($request);
                }
            )
        );

    }
}
