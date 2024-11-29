<?php

declare(strict_types=1);

namespace Ray\Di;

use Ray\Aop\MethodInterceptor;
use Ray\Aop\ReflectionClass;
use Ray\Di\Exception\InvalidToConstructorNameParameter;
use ReflectionException;
use ReflectionMethod;

use function array_keys;
use function array_reduce;
use function assert;
use function class_exists;
use function implode;
use function interface_exists;
use function is_array;
use function is_string;

final class Bind
{
    /** @var Container */
    private $container;

    /**
     * @var string|class-string
     * @phpstan-var class-string<MethodInterceptor>|string
     */
    private $interface;

    /** @var string */
    private $name = Name::ANY;

    /** @var DependencyInterface */
    private $bound;

    /** @var BindValidator */
    private $validate;

    /** @var ?Untarget */
    private $untarget;

    /**
     * @param Container                              $container dependency container
     * @param class-string<MethodInterceptor>|string $interface interface or concrete class name
     */
    public function __construct(Container $container, string $interface)
    {
        $this->container = $container;
        $this->interface = $interface;
        $this->validate = new BindValidator();
        $bindUntarget = class_exists($interface) && ! (new \ReflectionClass($interface))->isAbstract() && ! $this->isRegistered($interface);
        $this->bound = new NullDependency();
        if ($bindUntarget) {
            $this->untarget = new Untarget($interface);

            return;
        }

        $this->validate->constructor($interface);
    }

    public function __destruct()
    {
        if ($this->untarget) {
            ($this->untarget)($this->container, $this);
            $this->untarget = null;
        }
    }

    public function __toString(): string
    {
        return $this->interface . '-' . $this->name;
    }

    /**
     * Set dependency name
     */
    public function annotatedWith(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Bind to class
     *
     * @param class-string $class
     */
    public function to(string $class): self
    {
        $this->untarget = null;
        $refClass = $this->validate->to($this->interface, $class);
        $this->bound = (new DependencyFactory())->newAnnotatedDependency($refClass);
        $this->container->add($this);

        return $this;
    }

    /**
     * Bind to constructor
     *
     * @param class-string<T>              $class class name
     * @param array<string, string>|string $name  "varName=bindName,..." or [$varName => $bindName, $varName => $bindName...]
     *
     * @throws ReflectionException
     *
     * @template T of object
     */
    public function toConstructor(string $class, $name, ?InjectionPoints $injectionPoints = null, ?string $postConstruct = null): self
    {
        if (is_array($name)) {
            $name = $this->getStringName($name);
        }

        $this->untarget = null;
        $postConstructRef = $postConstruct ? new ReflectionMethod($class, $postConstruct) : null;
        /** @var ReflectionClass<object> $reflection */
        $reflection = new ReflectionClass($class);
        $this->bound = (new DependencyFactory())->newToConstructor($reflection, $name, $injectionPoints, $postConstructRef);
        $this->container->add($this);

        return $this;
    }

    /**
     * Bind to provider
     *
     * @phpstan-param class-string $provider
     */
    public function toProvider(string $provider, string $context = ''): self
    {
        $this->untarget = null;
        $refClass = $this->validate->toProvider($provider);
        $this->bound = (new DependencyFactory())->newProvider($refClass, $context);
        $this->container->add($this);

        return $this;
    }

    /**
     * Bind to instance
     *
     * @param mixed $instance
     */
    public function toInstance($instance): self
    {
        $this->untarget = null;
        $this->bound = new Instance($instance);
        $this->container->add($this);

        return $this;
    }

    /**
     * Bind to NullObject
     */
    public function toNull(): self
    {
        $this->untarget = null;
        assert(interface_exists($this->interface));
        $this->bound = new NullObjectDependency($this->interface);
        $this->container->add($this);

        return $this;
    }

    /**
     * Set scope
     */
    public function in(string $scope): self
    {
        if ($this->bound instanceof Dependency || $this->bound instanceof DependencyProvider || $this->bound instanceof NullDependency) {
            $this->bound->setScope($scope);
        }

        if ($this->untarget) {
            $this->untarget->setScope($scope);
        }

        return $this;
    }

    public function getBound(): DependencyInterface
    {
        return $this->bound;
    }

    public function setBound(DependencyInterface $bound): void
    {
        $this->bound = $bound;
    }

    private function isRegistered(string $interface): bool
    {
        return isset($this->container->getContainer()[$interface . '-' . Name::ANY]);
    }

    /**
     * Return string
     *
     * input: ['varA' => 'nameA', 'varB' => 'nameB']
     * output: "varA=nameA,varB=nameB"
     *
     * @param array<string, string> $name
     *
     * @psalm-pure
     */
    private function getStringName(array $name): string
    {
        $keys = array_keys($name);

        $names = array_reduce(
            $keys,
            /**
             * @param list<string> $carry
             * @param array-key $key
             */
            static function (array $carry, $key) use ($name): array {
                if (! is_string($key)) { // @phpstan-ignore-line
                    throw new InvalidToConstructorNameParameter((string) $key);
                }

                $varName = $name[$key];
                $carry[] = $key . '=' . $varName;

                return $carry;
            },
            []
        );

        return implode(',', $names);
    }
}
