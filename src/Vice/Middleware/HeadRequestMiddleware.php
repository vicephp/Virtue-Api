<?php

namespace Vice\Middleware;

use Psr\Http\Message\ResponseFactoryInterface as ResponseFactory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Psr\Http\Server\MiddlewareInterface as ServerMiddleware;
use Psr\Http\Server\RequestHandlerInterface as HandlesServerRequests;
use Vice\Http\RequestMethod;

class HeadRequestMiddleware implements ServerMiddleware
{
    /** @var ResponseFactory */
    private $responseFactory;

    public function __construct(ResponseFactory $responseFactory)
    {
        $this->responseFactory = $responseFactory;
    }

    /**
     * This is to be in compliance with RFC 2616, Section 9.
     * If the incoming request method is HEAD, we need to ensure that the response body
     * is empty as the request may fall back on a GET route handler due to FastRoute's
     * routing logic which could potentially append content to the response body
     * https://www.w3.org/Protocols/rfc2616/rfc2616-sec9.html#sec9.4
     *
     * @param ServerRequest $request
     * @param HandlesServerRequests $next
     * @return Response
     */
    public function process(ServerRequest $request, HandlesServerRequests $next): Response
    {
        $response = $next->handle($request);
        if(strtoupper($request->getMethod()) === RequestMethod::HEAD) {
            $response = $response->withBody($this->responseFactory->createResponse()->getBody());
        }

        return $response;
    }
}
