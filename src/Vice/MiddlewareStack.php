<?php

namespace Vice;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Psr\Http\Server\MiddlewareInterface as ServerMiddleware;
use Psr\Http\Server\RequestHandlerInterface as HandlesServerRequests;
use Slim\Interfaces\MiddlewareDispatcherInterface;
use Vice\Middleware\RequestHandler;

class MiddlewareStack implements MiddlewareDispatcherInterface
{
    /** @var ServerMiddleware[] */
    private $stack = [];
    /** @var HandlesServerRequests */
    private $kernel;

    public function __construct(HandlesServerRequests $kernel, array $stack = [])
    {
        $this->seedMiddlewareStack($kernel);
        foreach ($stack as $middleware) {
            $this->addMiddleware($middleware);
        }
    }

    /**
     * @deprecated
     * @param callable|ServerMiddleware|string $middleware
     * @return MiddlewareDispatcherInterface
     */
    public function add($middleware): MiddlewareDispatcherInterface
    {
        trigger_error(sprintf("The %s method is deprecated and will be removed.", __METHOD__), E_USER_DEPRECATED);

        $this->stack[] = $middleware;

        return $this;
    }

    public function addMiddleware(ServerMiddleware $middleware): MiddlewareDispatcherInterface
    {
        $this->append($middleware);

        return $this;
    }

    public function append(ServerMiddleware $middleware): void
    {
        $this->stack[] = $middleware;
    }

    public function prepend(ServerMiddleware $middleware): void
    {
        array_unshift($this->stack, $middleware);
    }

    public function seedMiddlewareStack(HandlesServerRequests $kernel): void
    {
        $this->kernel = $kernel;
    }

    public function handle(ServerRequest $request): Response
    {
        return $this->buildStack()->handle($request);
    }

    private function buildStack($index = 0): HandlesServerRequests
    {
        return isset($this->stack[$index]) ? new RequestHandler($this->stack[$index], $this->buildStack($index + 1)) : $this->kernel;
    }
}
