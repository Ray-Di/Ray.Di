<?php

declare(strict_types=1);
/**
 * This file is part of the Ray.Di package.
 *
 * @license http://opensource.org/licenses/MIT MIT
 */
namespace Ray\Di;

use Ray\Aop\AbstractMatcher;
use Ray\Aop\Matcher;
use Ray\Aop\PriorityPointcut;

abstract class AbstractModule
{
    /**
     * @var Matcher
     */
    protected $matcher;

    /**
     * @var AbstractModule|null
     */
    protected $lastModule;

    /**
     * @var Container
     */
    private $container;

    public function __construct(
        self $module = null
    ) {
        $this->lastModule = $module;
        $this->activate();
        if ($module instanceof self) {
            $this->container->merge($module->getContainer());
        }
    }

    public function __toString()
    {
        $log = [];
        foreach ($this->getContainer()->getContainer() as $dependencyIndex => $dependency) {
            $log[] = sprintf(
                '%s => %s',
                $dependencyIndex,
                (string) $dependency
            );
        }
        sort($log);

        return implode(PHP_EOL, $log);
    }

    /**
     * Install module
     */
    public function install(self $module)
    {
        $this->getContainer()->merge($module->getContainer());
    }

    /**
     * Override module
     */
    public function override(self $module)
    {
        $module->getContainer()->merge($this->container);
        $this->container = $module->getContainer();
    }

    /**
     * Return container
     *
     * @return Container
     */
    public function getContainer() : Container
    {
        if (! $this->container) {
            $this->activate();
        }

        return $this->container;
    }

    /**
     * Bind interceptor
     *
     * @param AbstractMatcher $classMatcher
     * @param AbstractMatcher $methodMatcher
     * @param string[]        $interceptors
     */
    public function bindInterceptor(AbstractMatcher $classMatcher, AbstractMatcher $methodMatcher, array $interceptors)
    {
        $pointcut = new Pointcut($classMatcher, $methodMatcher, $interceptors);
        $this->container->addPointcut($pointcut);
        foreach ($interceptors as $interceptor) {
            (new Bind($this->container, $interceptor))->to($interceptor)->in(Scope::SINGLETON);
        }
    }

    /**
     * Bind interceptor early
     *
     * @param AbstractMatcher $classMatcher
     * @param AbstractMatcher $methodMatcher
     * @param array           $interceptors
     */
    public function bindPriorityInterceptor(AbstractMatcher $classMatcher, AbstractMatcher $methodMatcher, array $interceptors)
    {
        $pointcut = new PriorityPointcut($classMatcher, $methodMatcher, $interceptors);
        $this->container->addPointcut($pointcut);
        foreach ($interceptors as $interceptor) {
            (new Bind($this->container, $interceptor))->to($interceptor)->in(Scope::SINGLETON);
        }
    }

    /**
     * Rename binding name
     *
     * @param string $interface       Interface
     * @param string $newName         New binding name
     * @param string $sourceName      Original binding name
     * @param string $targetInterface Original interface
     */
    public function rename(string $interface, string $newName, string $sourceName = Name::ANY, string $targetInterface = '')
    {
        $targetInterface = $targetInterface ?: $interface;
        if ($this->lastModule instanceof self) {
            $this->lastModule->getContainer()->move($interface, $sourceName, $targetInterface, $newName);
        }
    }

    /**
     * Configure binding
     */
    abstract protected function configure();

    /**
     * Bind interface
     */
    protected function bind(string $interface = '') : Bind
    {
        return new Bind($this->getContainer(), $interface);
    }

    /**
     * Activate bindings
     */
    private function activate()
    {
        $this->container = new Container;
        $this->matcher = new Matcher;
        $this->configure();
    }
}
