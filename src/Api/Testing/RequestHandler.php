<?php

namespace Virtue\Api\Testing;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Psr\Http\Server\RequestHandlerInterface as HandlesServerRequests;

class RequestHandler implements HandlesServerRequests, \Countable
{
    /** @var array */
    private $queue;
    /** @var ServerRequest */
    private $lastRequest;

    public function __construct(array $queue = [])
    {
        $this->append(...array_values($queue));
    }

    public function handle(ServerRequest $request): Response
    {
        if (!$this->queue) {
            throw new \OutOfBoundsException('Mock queue is empty');
        }
        $this->lastRequest = $request;
        $response = \array_shift($this->queue);

        if (\is_callable($response)) {
            $response = $response($request);
        }

        if($response instanceof \Throwable) {
            throw $response;
        }

        return $response;
    }

    public function append(...$responses): void
    {
        foreach ($responses as $response) {
            if ($response instanceof Response
                || $response instanceof \Throwable
                || \is_callable($response)
            ) {
                $this->queue[] = $response;
            } else {
                throw new \TypeError('Expected a ResponseInterface, Throwable or callable. Found ' . \gettype($response));
            }
        }
    }

    public function lastRequest(): ServerRequest
    {
        return $this->lastRequest;
    }

    public function count(): int
    {
        return \count($this->queue);
    }

    public function reset(): void
    {
        $this->queue = [];
    }
}
