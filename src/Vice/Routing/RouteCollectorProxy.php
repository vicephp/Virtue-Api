<?php

namespace Vice\Routing;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface as ResponseFactory;
use Slim\Interfaces\CallableResolverInterface;
use Slim\Interfaces\RouteCollectorInterface;
use Slim\Interfaces\RouteCollectorProxyInterface;
use Slim\Interfaces\RouteGroupInterface;
use Slim\Interfaces\RouteInterface;

class RouteCollectorProxy implements RouteCollectorProxyInterface
{
    /** @var ResponseFactory */
    private $responseFactory;
    /** @var CallableResolverInterface */
    private $callableResolver;
    /** @var RouteCollectorInterface */
    private $routeCollector;
    /** @var string */
    private $groupPattern = '';

    public function __construct(
        ResponseFactory $responseFactory,
        CallableResolverInterface $callableResolver,
        RouteCollectorInterface $routeCollector,
        ?string $groupPattern = ''
    ) {
        $this->responseFactory = $responseFactory;
        $this->callableResolver = $callableResolver;
        $this->routeCollector = $routeCollector;
        $this->groupPattern = $groupPattern;
    }

    /**
     * {@inheritdoc}
     */
    public function getResponseFactory(): ResponseFactory
    {
        trigger_error(sprintf("The %s method is deprecated and will be removed.", __METHOD__), E_USER_DEPRECATED);
        return $this->responseFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function getCallableResolver(): CallableResolverInterface
    {
        trigger_error(sprintf("The %s method is deprecated and will be removed.", __METHOD__), E_USER_DEPRECATED);
        return $this->callableResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function getContainer(): ?ContainerInterface
    {
        trigger_error(sprintf("The %s method is deprecated and will be removed.", __METHOD__), E_USER_DEPRECATED);
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getRouteCollector(): RouteCollectorInterface
    {
        trigger_error(sprintf("The %s method is deprecated and will be removed.", __METHOD__), E_USER_DEPRECATED);
        return $this->routeCollector;
    }

    /**
     * {@inheritdoc}
     */
    public function getBasePath(): string
    {
        return $this->routeCollector->getBasePath();
    }

    /**
     * {@inheritdoc}
     */
    public function setBasePath(string $basePath): RouteCollectorProxyInterface
    {
        $this->routeCollector->setBasePath($basePath);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $pattern, $callable): RouteInterface
    {
        return $this->map(['GET'], $pattern, $callable);
    }

    /**
     * {@inheritdoc}
     */
    public function post(string $pattern, $callable): RouteInterface
    {
        return $this->map(['POST'], $pattern, $callable);
    }

    /**
     * {@inheritdoc}
     */
    public function put(string $pattern, $callable): RouteInterface
    {
        return $this->map(['PUT'], $pattern, $callable);
    }

    /**
     * {@inheritdoc}
     */
    public function patch(string $pattern, $callable): RouteInterface
    {
        return $this->map(['PATCH'], $pattern, $callable);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $pattern, $callable): RouteInterface
    {
        return $this->map(['DELETE'], $pattern, $callable);
    }

    /**
     * {@inheritdoc}
     */
    public function options(string $pattern, $callable): RouteInterface
    {
        return $this->map(['OPTIONS'], $pattern, $callable);
    }

    /**
     * {@inheritdoc}
     */
    public function any(string $pattern, $callable): RouteInterface
    {
        return $this->map(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], $pattern, $callable);
    }

    /**
     * {@inheritdoc}
     */
    public function map(array $methods, string $pattern, $callable): RouteInterface
    {
        return $this->routeCollector->map($methods, "{$this->groupPattern}{$pattern}", $callable);
    }

    /**
     * {@inheritdoc}
     */
    public function group(string $pattern, $callable): RouteGroupInterface
    {
        return $this->routeCollector->group("{$this->groupPattern}{$pattern}", $callable);
    }

    /**
     * {@inheritdoc}
     */
    public function redirect(string $from, $to, int $status = 302): RouteInterface
    {
        $responseFactory = $this->responseFactory;

        $handler = function () use ($to, $status, $responseFactory) {
            $response = $responseFactory->createResponse($status);
            return $response->withHeader('Location', (string) $to);
        };

        return $this->get($from, $handler);
    }
}
