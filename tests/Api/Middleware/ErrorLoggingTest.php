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

        $errorHandling = new Testing\ErrorHandling(
            $kernel->get(ResponseFactoryInterface::class)
        );

        $logger = new Testing\Logger();
        $errorHandling->process(
            $request,
            new RequestHandler(
                new ErrorLogging($logger),
                new Testing\CallableHandler(
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

        $logger = new Testing\Logger();
        $errorLogging = new ErrorLogging($logger);

        $this->expectException(HttpException::class);
        $errorLogging->process(
            $request,
            new Testing\CallableHandler(
                function (ServerRequest $request) {
                    throw new HttpException($request);
                }
            )
        );

    }
}
