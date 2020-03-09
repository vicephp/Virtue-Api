<?php
namespace Vice\Routing;

use Psr\Http\Server\MiddlewareInterface;
use Slim\Interfaces\AdvancedCallableResolverInterface;
use Slim\Interfaces\CallableResolverInterface;
use Slim\Interfaces\RouteCollectorProxyInterface;
use Slim\Interfaces\RouteGroupInterface;
use Slim\MiddlewareDispatcher;

class RouteGroup implements RouteGroupInterface
{
    /** @var callable|string */
    protected $callable;
    /** @var CallableResolverInterface */
    protected $callableResolver;
    /** @var RouteCollectorProxyInterface */
    protected $routeCollectorProxy;
    /** @var MiddlewareInterface[] */
    protected $middleware = [];
    /** @var string */
    protected $pattern;

    /**
     * @param string                       $pattern
     * @param callable|string              $callable
     * @param CallableResolverInterface    $callableResolver
     * @param RouteCollectorProxyInterface $routeCollectorProxy
     */
    public function __construct(
        string $pattern,
        $callable,
        CallableResolverInterface $callableResolver,
        RouteCollectorProxyInterface $routeCollectorProxy
    ) {
        $this->pattern = $pattern;
        $this->callable = $callable;
        $this->callableResolver = $callableResolver;
        $this->routeCollectorProxy = $routeCollectorProxy;
    }

    /**
     * {@inheritdoc}
     */
    public function collectRoutes(): RouteGroupInterface
    {
        if ($this->callableResolver instanceof AdvancedCallableResolverInterface) {
            $callable = $this->callableResolver->resolveRoute($this->callable);
        } else {
            $callable = $this->callableResolver->resolve($this->callable);
        }
        $callable($this->routeCollectorProxy);
        return $this;
    }

    /**
     * @deprecated
     * {@inheritdoc}
     */
    public function add($middleware): RouteGroupInterface
    {
        trigger_error(sprintf("The %s method is deprecated and will be removed.", __METHOD__), E_USER_DEPRECATED);
        $this->middleware[] = $middleware;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addMiddleware(MiddlewareInterface $middleware): RouteGroupInterface
    {
        $this->middleware[] = $middleware;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function appendMiddlewareToDispatcher(MiddlewareDispatcher $dispatcher): RouteGroupInterface
    {
        foreach ($this->middleware as $middleware) {
            $dispatcher->addMiddleware($middleware);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getPattern(): string
    {
        return $this->pattern;
    }
}
