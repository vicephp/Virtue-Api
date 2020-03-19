<?php

namespace Virtue\Api;

use Closure;
use Psr\Container\ContainerInterface as Locator;
use Psr\Http\Server\RequestHandlerInterface as HandlesServerRequests;
use RuntimeException;
use Slim\Interfaces\CallableResolverInterface;
use Slim\Interfaces\InvocationStrategyInterface as InvocationStrategy;
use function is_array;
use function is_callable;
use function is_object;
use function is_string;
use function json_encode;
use function preg_match;
use function sprintf;

final class CallableResolver implements CallableResolverInterface
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
//        $this->invoker = $invoker;
    }

    public function resolve($toResolve): callable
    {
        if (is_callable($toResolve)) {
            return $this->bindToContainer($toResolve);
        }
        $resolved = $toResolve;
        if (is_string($toResolve)) {
            $resolved = $this->resolveSlimNotation($toResolve);
            $resolved[1] = $resolved[1] ?? ($resolved[0] instanceof HandlesServerRequests ? 'handle' : '__invoke');
        }
        $callable = $this->assertCallable($resolved, $toResolve);
        return $this->bindToContainer($callable);
    }

    private function resolveSlimNotation(string $toResolve): array
    {
        preg_match(CallableResolver::$callablePattern, $toResolve, $matches);
        [$class, $method] = $matches ? [$matches[1], $matches[2]] : [$toResolve, null];

        if ($this->kernel && $this->kernel->has($class)) {
            return [$instance = $this->kernel->get($class), $method];
        }

        throw new RuntimeException(sprintf('Callable %s does not exist', $class));
    }

    private function assertCallable($resolved, $toResolve): callable
    {
        if (!is_callable($resolved)) {
            throw new RuntimeException(sprintf(
                '%s is not resolvable',
                is_callable($toResolve) || is_object($toResolve) || is_array($toResolve) ?
                    json_encode($toResolve) : $toResolve
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
