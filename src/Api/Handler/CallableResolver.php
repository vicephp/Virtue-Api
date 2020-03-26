<?php

namespace Virtue\Api\Handler;

use Closure;
use Psr\Container\ContainerInterface as Locator;
use RuntimeException;
use Slim\Interfaces\CallableResolverInterface;
use function is_array;
use function is_callable;
use function is_object;
use function is_string;
use function sprintf;

final class CallableResolver implements CallableResolverInterface
{
    const INSTANCE = 0;
    const METHOD = 1;
    /** @var Locator */
    private $kernel;

    public function __construct(Locator $kernel)
    {
        $this->kernel = $kernel;
    }

    public function resolve($resolvable): callable
    {
        $resolved = $resolvable;
        if (is_string($resolvable)) {
            $resolved = $this->resolveStringNotation($resolvable);
        }
        $callable = $this->assertCallable($resolved, $resolvable);

        return $this->bindToContainer($callable);
    }

    private function resolveStringNotation(string $resolvable): array
    {
        $matches = explode('::', $resolvable);
        [$class, $method] = [$matches[self::INSTANCE], $matches[self::METHOD] ?? '__invoke'];

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
                    var_export($resolvable, true) : $resolvable
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
