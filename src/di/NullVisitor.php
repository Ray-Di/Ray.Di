<?php

declare(strict_types=1);

namespace Ray\Di;

use Ray\Aop\Bind;
use ReflectionParameter;

final class NullVisitor implements VisitorInterface
{
    /** @inheritDoc */
    public function visitDependency(
        NewInstance $newInstance,
        ?string $postConstruct,
        bool $isSingleton
    ) {
        $newInstance->accept($this);

        return true;
    }

    /** @inheritDoc */
    public function visitProvider(
        Dependency $dependency,
        string $context,
        bool $isSingleton
    ) {
    }

    /** @inheritDoc */
    public function visitInstance($value)
    {
    }

    /** @inheritDoc */
    public function visitAspectBind(Bind $aopBind)
    {
    }

    /** @inheritDoc */
    public function visitNewInstance(
        string $class,
        SetterMethods $setterMethods,
        ?Arguments $arguments,
        ?AspectBind $bind
    ) {
        $setterMethods->accept($this);
        if ($arguments) {
            $arguments->accept($this);
        }
    }

    /** @inheritDoc */
    public function visitSetterMethods(
        array $setterMethods
    ) {
        foreach ($setterMethods as $setterMethod) {
            $setterMethod->accept($this);
        }
    }

    /** @inheritDoc */
    public function visitSetterMethod(string $method, Arguments $arguments)
    {
        $arguments->accept($this);
    }

    /** @inheritDoc */
    public function visitArguments(array $arguments)
    {
        foreach ($arguments as $argument) {
            $argument->accept($this);
        }
    }

    /** @inheritDoc */
    public function visitArgument(
        string $index,
        bool $isDefaultAvailable,
        $defaultValue,
        ReflectionParameter $parameter
    ): void {
    }
}
