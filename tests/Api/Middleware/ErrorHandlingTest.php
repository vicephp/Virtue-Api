<?php

namespace Virtue\Api\Middleware;

use Psr\Http\Message\ResponseFactoryInterface as ResponseFactory;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Virtue\Api\Testing;
use Virtue\Api\TestCase;
use Virtue\Http\Message\RequestParser;

class ErrorHandlingTest extends TestCase
{
    public function testHandling()
    {
        $kernel = $this->container->build();
        $request = $kernel->get(ServerRequest::class);
        $errorHandling = new ErrorHandling(
            new RequestParser(),
            ['text/html' => $kernel->get(Testing\ErrorHandling::class)]
        );
        $response = $errorHandling->process(
            $request->withHeader('Accept', 'text/html'),
            new Testing\RequestHandler(
                [$kernel->get(ResponseFactory::class)->createResponse()]
            )
        );

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('Internal Server Error', $response->getReasonPhrase());
    }
}
