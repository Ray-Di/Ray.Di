<?php

declare(strict_types=1);

namespace Ray\Di;

use Ray\Aop\Bind as AopBind;
use ReflectionParameter;

/**
 * Visits a Dependency
 */
interface VisitorInterface
{
    /**
     * Visits a Dependency
     *
     * @return mixed|void
     */
    public function visitDependency(NewInstance $newInstance, ?string $postConstruct, bool $isSingleton);

    /**
     * Visits a Provider
     *
     * @return mixed|void
     */
    public function visitProvider(Dependency $dependency, string $context, bool $isSingleton);

    /**
     * Visits an Instance
     *
     * @param mixed $value
     *
     * @return mixed|void
     */
    public function visitInstance($value);

    /**
     * Visits an AspectBind
     *
     * @return mixed|void
     */
    public function visitAspectBind(AopBind $aopBind);

    /**
     * Visits a New Instance
     *
     * @return mixed|void
     */
    public function visitNewInstance(
        string $class,
        SetterMethods $setterMethods,
        ?Arguments $arguments,
        ?AspectBind $bind
    );

    /**
     * Visits Setter Methods
     *
     * @param array<SetterMethod> $setterMethods
     *
     * @return mixed|void
     */
    public function visitSetterMethods(array $setterMethods);

    /**
     * Visits a Setter Method
     *
     * @return mixed|void
     */
    public function visitSetterMethod(string $method, Arguments $arguments);

    /**
     * Visits Arguments
     *
     * @param array<Argument> $arguments
     *
     * @return mixed|void
     */
    public function visitArguments(array $arguments);

    /**
     * Visits an Argument
     *
     * @param mixed $defaultValue
     *
     * @return mixed|void
     */
    public function visitArgument(
        string $index,
        bool $isDefaultAvailable,
        $defaultValue,
        ReflectionParameter $parameter
    );
}
