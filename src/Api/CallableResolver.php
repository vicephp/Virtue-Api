<?php

namespace Virtue\Api;

use Closure;
use Psr\Container\ContainerInterface as Locator;
use Psr\Http\Message\ResponseFactoryInterface as ResponseFactory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Psr\Http\Server\RequestHandlerInterface as HandlesServerRequests;
use RuntimeException;
use Slim\Interfaces\CallableResolverInterface;
use Slim\Interfaces\InvocationStrategyInterface;
use Slim\Interfaces\InvocationStrategyInterface as InvocationStrategy;
use Virtue\Api\Routing\RoutingResults;
use function is_array;
use function is_callable;
use function is_object;
use function is_string;
use function json_encode;
use function preg_match;
use function sprintf;

final class CallableResolver implements CallableResolverInterface, HandlesServerRequests
{
    const INSTANCE = 0;
    const METHOD = 1;

    public static $callablePattern = '!^([^\:]+)\:([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)$!';
    /** @var Locator */
    private $kernel;
    /** @var InvocationStrategy */
    private $invoker;

    public function __construct(Locator $kernel)
    {
        $this->kernel = $kernel;
        $this->invoker = $kernel->get(InvocationStrategyInterface::class);
    }

    public function handle(ServerRequest $request): Response
    {
        $results = RoutingResults::fromRequest($request);

        $handler = $this->resolve($results->getRoute()->getHandler());
        $strategy = $this->invoker;
        $response = $this->kernel->get(ResponseFactory::class)->createResponse();
        return $strategy($handler, $request, $response, $results->getRouteArgs());
    }

    public function resolve($resolvable): callable
    {
        if (is_callable($resolvable)) {
            return $this->bindToContainer($resolvable);
        }
        $resolved = $resolvable;
        if (is_string($resolvable)) {
            $resolved = $this->resolveSlimNotation($resolvable);
            $resolved[1] = $resolved[1] ?? '__invoke';
        }
        $callable = $this->assertCallable($resolved, $resolvable);
        return $this->bindToContainer($callable);
    }

    public function resolveRoute($resolvable): callable
    {
        return $this->resolve($resolvable);
    }

    private function resolveSlimNotation(string $resolvable): array
    {
        preg_match(CallableResolver::$callablePattern, $resolvable, $matches);
        [$class, $method] = $matches ? [$matches[1], $matches[2]] : [$resolvable, null];

        if ($this->kernel->has($class)) {
            return [$instance = $this->kernel->get($class), $method];
        }

        throw new RuntimeException(sprintf('Callable %s does not exist', $class));
    }

    private function assertCallable($resolved, $resolvable): callable
    {
        if (!is_callable($resolved)) {
            throw new RuntimeException(sprintf(
                '%s is not resolvable',
                is_callable($resolvable) || is_object($resolvable) || is_array($resolvable) ?
                    json_encode($resolvable) : $resolvable
            ));
        }
        return $resolved;
    }

    private function bindToContainer(callable $callable): callable
    {
        if (is_array($callable) && $callable[0] instanceof Closure) {
            $callable = $callable[0];
        }
        if ($this->kernel && $callable instanceof Closure) {
            $callable = $callable->bindTo($this->kernel);
        }
        return $callable;
    }
}
