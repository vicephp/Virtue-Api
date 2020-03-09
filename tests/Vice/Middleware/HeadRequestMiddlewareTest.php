<?php

namespace Vice\Middleware;

use Slim\Factory\ServerRequestCreatorFactory;
use Vice\Http\RequestMethod;
use PHPUnit\Framework\TestCase;
use Vice\Testing\RequestHandlerStub;

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
