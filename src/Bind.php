<?php

declare(strict_types=1);

/**
 * This file is part of the Ray.Di package.
 *
 * @license http://opensource.org/licenses/MIT MIT
 */
namespace Ray\Di;

use Ray\Di\Exception\InvalidContext;
use Ray\Di\Exception\NotFound;

final class Bind
{
    /**
     * @var Container
     */
    private $container;

    /**
     * @var string
     */
    private $interface;

    /**
     * @var string
     */
    private $name = Name::ANY;

    /**
     * @var DependencyInterface
     */
    private $bound;

    /**
     * @var BindValidator
     */
    private $validate;

    /**
     * @var Untarget
     */
    private $untarget;

    /**
     * @param Container $container dependency container
     * @param string    $interface interface or concrete class name
     */
    public function __construct(Container $container, string $interface)
    {
        $this->container = $container;
        $this->interface = $interface;
        $this->validate = new BindValidator;
        $bindUntarget = class_exists($interface) && ! (new \ReflectionClass($interface))->isAbstract() && ! $this->IsRegistered($interface);
        if ($bindUntarget) {
            $this->untarget = new Untarget($interface);

            return;
        }
        $this->validate->constructor($interface);
    }

    public function __destruct()
    {
        if ($this->untarget) {
            $this->untarget->__invoke($this->container, $this);
            $this->untarget = null;
        }
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->interface . '-' . $this->name;
    }

    /**
     * Bind dependency name
     */
    public function annotatedWith(string $name) : self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Bind to clss
     */
    public function to(string $class) : self
    {
        $this->untarget = null;
        $this->validate->to($this->interface, $class);
        $this->bound = (new DependencyFactory)->newAnnotatedDependency(new \ReflectionClass($class));
        $this->container->add($this);

        return $this;
    }

    /**
     * Bind to constroctur
     *
     * @param string          $class           class name
     * @param string | array  $name            "varName=bindName,..." or [[varName=>bindName],...]
     * @param InjectionPoints $injectionPoints injection points
     * @param null            $postConstruct   method name of initialization after all dependencies are injected*
     */
    public function toConstructor($class, $name, InjectionPoints $injectionPoints = null, $postConstruct = null) : self
    {
        if (is_array($name)) {
            $name = $this->getStringName($name);
        }
        $this->untarget = null;
        $postConstruct = $postConstruct ? new \ReflectionMethod($class, $postConstruct) : null;
        $this->bound = (new DependencyFactory)->newToConstructor(new \ReflectionClass($class), $name, $injectionPoints, $postConstruct);
        $this->container->add($this);

        return $this;
    }

    /**
     * Bind to provider
     *
     * @throws NotFound
     */
    public function toProvider(string $provider, $context = null) : self
    {
        if (! is_null($context) && ! is_string($context)) {
            throw new InvalidContext(gettype($context));
        }
        $this->untarget = null;
        $this->validate->toProvider($provider);
        $this->bound = (new DependencyFactory)->newProvider(new \ReflectionClass($provider), $context);
        $this->container->add($this);

        return $this;
    }

    /**
     * @param mixed $instance
     *
     * @return $this
     */
    public function toInstance($instance)
    {
        $this->untarget = null;
        $this->bound = new Instance($instance);
        $this->container->add($this);

        return $this;
    }

    /**
     * @param string $scope
     *
     * @return $this
     */
    public function in($scope)
    {
        if ($this->bound instanceof Dependency || $this->bound instanceof DependencyProvider) {
            $this->bound->setScope($scope);
        }
        if ($this->untarget) {
            $this->untarget->setScope($scope);
        }

        return $this;
    }

    /**
     * @return DependencyInterface
     */
    public function getBound()
    {
        return $this->bound;
    }

    /**
     * @param DependencyInterface $bound
     */
    public function setBound(DependencyInterface $bound)
    {
        $this->bound = $bound;
    }

    private function IsRegistered(string $interface) : bool
    {
        $isRegistered = isset($this->container->getContainer()[$interface . '-' . Name::ANY]);

        return $isRegistered;
    }

    /**
     * Return string
     *
     * input: [['varA' => 'nameA'], ['varB' => 'nameB']]
     * output: "varA=nameA,varB=nameB"
     *
     * @param array $name
     *
     * @return string
     */
    private function getStringName(array $name)
    {
        $names = array_reduce(array_keys($name), function ($carry, $key) use ($name) {
            $carry[] .= $key . '=' . $name[$key];

            return $carry;
        }, []);
        $string = implode(',', $names);

        return $string;
    }
}
