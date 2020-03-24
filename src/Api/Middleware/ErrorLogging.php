<?php

namespace Virtue\Api\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Psr\Http\Server\MiddlewareInterface as ServerMiddleware;
use Psr\Http\Server\RequestHandlerInterface as HandlesServerRequests;
use Psr\Log\LoggerInterface as Logger;
use Slim\Exception\HttpException;

class ErrorLogging implements ServerMiddleware
{
    /** @var Logger */
    private $log;

    public function __construct(Logger $log)
    {
        $this->log = $log;
    }

    public function process(ServerRequest $request, HandlesServerRequests $handler): Response
    {
        try {
            return $handler->handle($request);
        } catch (HttpException $httpException) {
            // Just pass on
            throw $httpException;
        } catch (\Throwable $serverError) {
            $this->log->error($serverError->getMessage(), ['exception' => $serverError]);

            throw $serverError;
        }
    }
}
