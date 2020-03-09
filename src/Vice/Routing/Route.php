<?php

namespace Vice\Routing;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Psr\Http\Server\MiddlewareInterface as ServerMiddleware;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Handlers\Strategies\RequestHandler;
use Slim\Handlers\Strategies\RequestResponse;
use Slim\Interfaces\AdvancedCallableResolverInterface;
use Slim\Interfaces\CallableResolverInterface;
use Slim\Interfaces\InvocationStrategyInterface;
use Slim\Interfaces\RequestHandlerInvocationStrategyInterface;
use Slim\Interfaces\RouteGroupInterface;
use Slim\Interfaces\RouteInterface;
use Slim\MiddlewareDispatcher;
use Vice\MiddlewareStack;
use function array_key_exists;
use function array_replace;
use function class_implements;
use function in_array;
use function is_array;

class Route implements RouteInterface, RequestHandlerInterface
{
    /** @var string[] */
    protected $methods = [];
    /** @var string */
    protected $identifier;
    /** @var null|string */
    protected $name;
    /** @var RouteGroupInterface[] */
    protected $groups;
    /**  @var InvocationStrategyInterface */
    protected $invocationStrategy;
    /** @var array */
    protected $arguments = [];
    /** @var array */
    protected $savedArguments = [];
    /** @var MiddlewareDispatcher */
    protected $middlewareDispatcher;
    /** @var callable|string */
    protected $callable;
    /** @var CallableResolverInterface */
    protected $callableResolver;
    /** @var ResponseFactoryInterface */
    protected $responseFactory;
    /** @var string */
    protected $pattern;
    /** @var bool */
    protected $groupMiddlewareAppended = false;

    public function __construct(
        array $methods,
        string $pattern,
        $callable,
        ResponseFactoryInterface $responseFactory,
        CallableResolverInterface $callableResolver,
        ?InvocationStrategyInterface $invocationStrategy = null,
        array $groups = [],
        int $identifier = 0
    ) {
        $this->methods = $methods;
        $this->pattern = $pattern;
        $this->callable = $callable;
        $this->responseFactory = $responseFactory;
        $this->callableResolver = $callableResolver;
        $this->invocationStrategy = $invocationStrategy ?? new RequestResponse();
        $this->groups = $groups;
        $this->identifier = "route{$identifier}";
        $this->middlewareDispatcher = new MiddlewareStack($this);
    }

    /**
     * {@inheritdoc}
     */
    public function getInvocationStrategy(): InvocationStrategyInterface
    {
        trigger_error(sprintf("The %s method is deprecated and will be removed.", __METHOD__), E_USER_DEPRECATED);
        return $this->invocationStrategy;
    }

    /**
     * {@inheritdoc}
     */
    public function setInvocationStrategy(InvocationStrategyInterface $invocationStrategy): RouteInterface
    {
        trigger_error(sprintf("The %s method is deprecated and will be removed.", __METHOD__), E_USER_DEPRECATED);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    /**
     * {@inheritdoc}
     */
    public function getPattern(): string
    {
        return $this->pattern;
    }

    /**
     * {@inheritdoc}
     */
    public function setPattern(string $pattern): RouteInterface
    {
        trigger_error(sprintf("The %s method is deprecated and will be removed.", __METHOD__), E_USER_DEPRECATED);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getCallable()
    {
        return $this->callable;
    }

    /**
     * @deprecated
     * {@inheritdoc}
     */
    public function setCallable($callable): RouteInterface
    {
        trigger_error(sprintf("The %s method is deprecated and will be removed.", __METHOD__), E_USER_DEPRECATED);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @deprecated
     * {@inheritdoc}
     */
    public function setName(string $name): RouteInterface
    {
        trigger_error(sprintf("The %s method is deprecated and will be removed.", __METHOD__), E_USER_DEPRECATED);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * @deprecated
     * {@inheritdoc}
     */
    public function getArgument(string $name, ?string $default = null): ?string
    {
        trigger_error(sprintf("The %s method is deprecated and will be removed.", __METHOD__), E_USER_DEPRECATED);
        if (array_key_exists($name, $this->arguments)) {
            return $this->arguments[$name];
        }
        return $default;
    }

    /**
     * {@inheritdoc}
     */
    public function getArguments(): array
    {
        trigger_error(sprintf("The %s method is deprecated and will be removed.", __METHOD__), E_USER_DEPRECATED);
        return $this->arguments;
    }

    /**
     * {@inheritdoc}
     */
    public function setArguments(array $arguments, bool $includeInSavedArguments = true): RouteInterface
    {
        trigger_error(sprintf("The %s method is deprecated and will be removed.", __METHOD__), E_USER_DEPRECATED);
        if ($includeInSavedArguments) {
            $this->savedArguments = $arguments;
        }

        $this->arguments = $arguments;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function add($middleware): RouteInterface
    {
        trigger_error(sprintf("The %s method is deprecated and will be removed.", __METHOD__), E_USER_DEPRECATED);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addMiddleware(ServerMiddleware $middleware): RouteInterface
    {
        $this->middlewareDispatcher->addMiddleware($middleware);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function prepare(array $arguments): RouteInterface
    {
        $this->arguments = array_replace($this->savedArguments, $arguments) ?? [];
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setArgument(string $name, string $value, bool $includeInSavedArguments = true): RouteInterface
    {
        trigger_error(sprintf("The %s method is deprecated and will be removed.", __METHOD__), E_USER_DEPRECATED);
        if ($includeInSavedArguments) {
            $this->savedArguments[$name] = $value;
        }

        $this->arguments[$name] = $value;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function run(ServerRequest $request): Response
    {
        if (!$this->groupMiddlewareAppended) {
            $this->appendGroupMiddlewareToRoute();
        }

        return $this->middlewareDispatcher->handle($request);
    }

    /**
     * @return void
     */
    protected function appendGroupMiddlewareToRoute(): void
    {
        $this->middlewareDispatcher = new MiddlewareStack($this->middlewareDispatcher);

        /** @var RouteGroupInterface $group */
        foreach ($this->groups as $group) {
            $group->appendMiddlewareToDispatcher($this->middlewareDispatcher);
        }

        $this->groupMiddlewareAppended = true;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(ServerRequest $request): Response
    {
        if ($this->callableResolver instanceof AdvancedCallableResolverInterface) {
            $callable = $this->callableResolver->resolveRoute($this->callable);
        } else {
            $callable = $this->callableResolver->resolve($this->callable);
        }
        $strategy = $this->invocationStrategy;

        if (
            is_array($callable)
            && $callable[0] instanceof RequestHandlerInterface
            && !in_array(RequestHandlerInvocationStrategyInterface::class, class_implements($strategy))
        ) {
            $strategy = new RequestHandler();
        }

        $response = $this->responseFactory->createResponse();
        return $strategy($callable, $request, $response, $this->arguments);
    }
}
