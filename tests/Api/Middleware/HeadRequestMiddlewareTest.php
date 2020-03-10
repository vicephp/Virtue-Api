<?php

namespace Virtue\Api\Middleware;

use Slim\Factory\ServerRequestCreatorFactory;
use Virtue\Api\Http\RequestMethod;
use PHPUnit\Framework\TestCase;
use Virtue\Api\Testing\RequestHandlerStub;

class HeadRequestMiddlewareTest extends TestCase
{
    public function testAssertBodyIsEmpty()
    {
        $responseFactory = \Slim\Factory\AppFactory::determineResponseFactory();
        $middleware = new HeadRequestMiddleware($responseFactory);
        $request = ServerRequestCreatorFactory::create()->createServerRequestFromGlobals();
        $request = $request->withMethod(RequestMethod::HEAD);
        $response = $middleware->process($request, new RequestHandlerStub($responseFactory->createResponse()));

        $this->assertEmpty($response->getBody()->getContents(), 'Response body must be empty on request method HEAD.');
    }
}
