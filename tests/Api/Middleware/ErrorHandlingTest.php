<?php

namespace Virtue\Api\Middleware;

use Psr\Http\Message\ResponseFactoryInterface as ResponseFactory;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Virtue\Api\Testing;
use Virtue\Api\TestCase;
use Virtue\Http\Message\HeaderParser;

class ErrorHandlingTest extends TestCase
{
    public function testHandling()
    {
        $kernel = $this->container->build();
        $request = $kernel->get(ServerRequest::class);
        $errorHandling = new ErrorHandling(
            new HeaderParser(),
            ['text/html' => $kernel->get(Testing\ErrorHandlingStub::class)]
        );
        $response = $errorHandling->process(
            $request->withHeader('Accept', 'text/html'),
            new Testing\RequestHandlerStub(
                $kernel->get(ResponseFactory::class)->createResponse()
            )
        );

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('Internal Server Error', $response->getReasonPhrase());
    }
}
