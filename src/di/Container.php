<?php

declare(strict_types=1);

namespace Ray\Di;

use BadMethodCallException;
use Ray\Aop\Compiler;
use Ray\Aop\CompilerInterface;
use Ray\Aop\Pointcut;
use Ray\Di\Exception\Unbound;
use Ray\Di\Exception\Untargeted;
use Ray\Di\MultiBinding\MultiBindings;
use ReflectionClass;

use function array_key_exists;
use function array_merge;
use function class_exists;
use function explode;
use function ksort;

final class Container implements InjectorInterface
{
    /** @var MultiBindings */
    public $multiBindings;

    /** @var DependencyInterface[] */
    private $container = [];

    /** @var array<int, Pointcut> */
    private $pointcuts = [];

    public function __construct()
    {
        $this->multiBindings = new MultiBindings();
    }

    /**
     * @return list<string>
     */
    public function __sleep()
    {
        return ['container', 'pointcuts', 'multiBindings'];
    }

    /**
     * Add binding to container
     */
    public function add(Bind $bind): void
    {
        $dependency = $bind->getBound();
        $dependency->register($this->container, $bind);
    }

    /**
     * Add Pointcut to container
     */
    public function addPointcut(Pointcut $pointcut): void
    {
        $this->pointcuts[] = $pointcut;
    }

    /**
     * {@inheritDoc}
     */
    public function getInstance($interface, $name = Name::ANY)
    {
        /**
         * @psalm-var T is object ? T : mixed
         * @phpstan-var mixed
         */
        return $this->getDependency($interface . '-' . $name);
    }

    /**
     * Return dependency injected instance
     *
     * @param array<int, mixed> $params
     *
     * @return mixed
     *
     * @throws Unbound
     */
    public function getInstanceWithArgs(string $interface, array $params)
    {
        $index = $interface . '-';
        if (! isset($this->container[$index])) {
            throw $this->unbound($index);
        }

        $dependency = $this->container[$index];
        if (! $dependency instanceof Dependency) {
            throw new BadMethodCallException($interface);
        }

        return $dependency->injectWithArgs($this, $params);
    }

    /**
     * Return dependency injected instance
     *
     * @return mixed
     *
     * @throws Unbound
     */
    public function getDependency(string $index)
    {
        if (! isset($this->container[$index])) {
            throw $this->unbound($index);
        }

        return $this->container[$index]->inject($this);
    }

    /**
     * Rename existing dependency interface + name
     */
    public function move(string $sourceInterface, string $sourceName, string $targetInterface, string $targetName): void
    {
        $sourceIndex = $sourceInterface . '-' . $sourceName;
        if (! isset($this->container[$sourceIndex])) {
            throw $this->unbound($sourceIndex);
        }

        $targetIndex = $targetInterface . '-' . $targetName;
        $this->container[$targetIndex] = $this->container[$sourceIndex];
        unset($this->container[$sourceIndex]);
    }

    /**
     * Return Unbound exception
     *
     * @param string $index {interface}-{bind name}
     *
     * @return Unbound|Untargeted
     */
    public function unbound(string $index)
    {
        [$class, $name] = explode('-', $index);
        if (class_exists($class) && ! (new ReflectionClass($class))->isAbstract()) {
            return new Untargeted($class);
        }

        return new Unbound("{$class}-{$name}");
    }

    /**
     * Return container
     *
     * @return DependencyInterface[]
     */
    public function getContainer(): array
    {
        return $this->container;
    }

    /**
     * Return pointcuts
     *
     * @return array<int, Pointcut>
     */
    public function getPointcuts(): array
    {
        return $this->pointcuts;
    }

    /**
     * Merge container
     */
    public function merge(self $container): void
    {
        $this->multiBindings->merge($container->multiBindings);
        $this->container += $container->getContainer();
        $this->pointcuts = array_merge($this->pointcuts, $container->getPointcuts());
    }

    /**
     * Weave aspects to all dependency in container
     */
    public function weaveAspects(CompilerInterface $compiler): void
    {
        foreach ($this->container as $dependency) {
            if ($dependency instanceof Dependency) {
                $dependency->weaveAspects($compiler, $this->pointcuts);
            }
        }
    }

    /**
     * Weave aspect to single dependency
     */
    public function weaveAspect(Compiler $compiler, Dependency $dependency): self
    {
        $dependency->weaveAspects($compiler, $this->pointcuts);

        return $this;
    }

    /**
     * @param callable(DependencyInterface): DependencyInterface $f
     */
    public function map(callable $f): void
    {
        foreach ($this->container as &$index) {
            $index = $f($index);
        }
    }

    public function sort(): void
    {
        ksort($this->container);
    }
}
