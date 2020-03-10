<?php

namespace Vice\Routing;

use Psr\Container\ContainerInterface as Locator;
use Psr\Http\Message\ResponseFactoryInterface as ResponseFactory;
use RuntimeException;
use Slim\Handlers\Strategies\RequestResponse;
use Slim\Interfaces\CallableResolverInterface;
use Slim\Interfaces\InvocationStrategyInterface;
use function array_pop;
use function sprintf;

/**
 * RouteCollector is used to collect routes and route groups
 * as well as generate paths and URLs relative to its environment
 */
class RouteCollector
{
    /** @var RouteParser */
    protected $routeParser;
    /** @var CallableResolverInterface */
    protected $callableResolver;
    /** @var Locator */
    protected $services;
    /** @var InvocationStrategyInterface */
    protected $defaultInvocationStrategy;
    /** @var string */
    protected $basePath = '';
    /** @var Route[] */
    protected $routes = [];
    /** @var RouteGroup[] */
    protected $routeGroups = [];
    /** @var int */
    protected $routeCounter = 0;
    /** @var ResponseFactory */
    protected $responseFactory;

    public function __construct(
        ResponseFactory $responseFactory,
        CallableResolverInterface $callableResolver,
        Locator $services = null,
        InvocationStrategyInterface $defaultInvocationStrategy = null,
        RouteParser $routeParser = null
    ) {
        $this->responseFactory = $responseFactory;
        $this->callableResolver = $callableResolver;
        $this->services = $services;
        $this->defaultInvocationStrategy = $defaultInvocationStrategy ?? new RequestResponse();
        $this->routeParser = $routeParser ?? new RouteParser($this);
    }

    /**
     * @return RouteParser
     */
    public function getRouteParser(): RouteParser
    {
        return $this->routeParser;
    }

    /**
     * Get default route invocation strategy
     *
     * @return InvocationStrategyInterface
     */
    public function getDefaultInvocationStrategy(): InvocationStrategyInterface
    {
        return $this->defaultInvocationStrategy;
    }

    /**
     * @param InvocationStrategyInterface $strategy
     * @return self
     */
    public function setDefaultInvocationStrategy(InvocationStrategyInterface $strategy): RouteCollectorInterface
    {
        trigger_error(sprintf("The %s method is deprecated and will be removed.", __METHOD__), E_USER_DEPRECATED);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheFile(): ?string
    {
        trigger_error(sprintf("The %s method is deprecated and will be removed.", __METHOD__), E_USER_DEPRECATED);
        return $this->cacheFile;
    }

    /**
     * {@inheritdoc}
     */
    public function setCacheFile(string $cacheFile): RouteCollectorInterface
    {
        trigger_error(sprintf("The %s method is deprecated and will be removed.", __METHOD__), E_USER_DEPRECATED);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getBasePath(): string
    {
        return $this->basePath;
    }

    /**
     * Set the base path used in urlFor()
     *
     * @param string $basePath
     *
     * @return self
     */
    public function setBasePath(string $basePath): RouteCollectorInterface
    {
        $this->basePath = $basePath;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * {@inheritdoc}
     */
    public function removeNamedRoute(string $name): RouteCollector
    {
        $route = $this->getNamedRoute($name);
        unset($this->routes[$route->getIdentifier()]);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getNamedRoute(string $name): Route
    {
        foreach ($this->routes as $route) {
            if ($name === $route->getName()) {
                return $route;
            }
        }
        throw new RuntimeException('Named route does not exist for name: ' . $name);
    }

    /**
     * {@inheritdoc}
     */
    public function lookupRoute(string $identifier): Route
    {
        if (!isset($this->routes[$identifier])) {
            throw new RuntimeException('Route not found, looks like your route cache is stale.');
        }
        return $this->routes[$identifier];
    }

    /**
     * {@inheritdoc}
     */
    public function group(string $pattern, $callable): RouteGroup
    {
        $routeCollectorProxy = new RouteCollectorProxy(
            $this->services,
            $this->callableResolver,
            $this,
            $pattern
        );

        $routeGroup = new RouteGroup($pattern, $callable, $this->services, $this->callableResolver, $routeCollectorProxy);
        $this->routeGroups[] = $routeGroup;

        $routeGroup->collectRoutes();
        array_pop($this->routeGroups);

        return $routeGroup;
    }

    /**
     * {@inheritdoc}
     */
    public function map(array $methods, string $pattern, $handler): Route
    {
        $route = $this->createRoute($methods, $pattern, $handler);
        $this->routes[$route->getIdentifier()] = $route;
        $this->routeCounter++;

        return $route;
    }

    /**
     * {@inheritdoc}
     */
    protected function createRoute(array $methods, string $pattern, $callable): Route
    {
        return new Route(
            $methods,
            $pattern,
            $callable,
            $this->services,
            $this->callableResolver,
            $this->defaultInvocationStrategy,
            $this->routeGroups,
            $this->routeCounter
        );
    }
}
