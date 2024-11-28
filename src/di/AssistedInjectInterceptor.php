<?php

declare(strict_types=1);

namespace Ray\Di;

use Ray\Aop\MethodInterceptor;
use Ray\Aop\MethodInvocation;
use Ray\Di\Di\Assisted;
use Ray\Di\Di\Inject;
use Ray\Di\Di\InjectInterface;
use Ray\Di\Di\Named;
use ReflectionAttribute;
use ReflectionNamedType;
use ReflectionParameter;

use function assert;
use function call_user_func_array;
use function get_class;
use function in_array;
use function is_callable;

/**
 * Assisted injection interceptor for #[Inject] attributed parameter
 */
final class AssistedInjectInterceptor implements MethodInterceptor
{
    /** @var InjectorInterface */
    private $injector;

    /** @var MethodInvocationProvider */
    private $methodInvocationProvider;

    public function __construct(InjectorInterface $injector, MethodInvocationProvider $methodInvocationProvider)
    {
        $this->injector = $injector;
        $this->methodInvocationProvider = $methodInvocationProvider;
    }

    /**
     * @return mixed
     */
    public function invoke(MethodInvocation $invocation)
    {
        $this->methodInvocationProvider->set($invocation);
        $params = $invocation->getMethod()->getParameters();
        $namedArguments = $this->getNamedArguments($invocation);
        foreach ($params as $param) {
            /** @var list<ReflectionAttribute> $inject */
            $inject = $param->getAttributes(InjectInterface::class, ReflectionAttribute::IS_INSTANCEOF); // @phpstan-ignore-line
            /** @var list<ReflectionAttribute> $assisted */
            $assisted = $param->getAttributes(Assisted::class);
            if (isset($assisted[0]) || isset($inject[0])) {
                /** @psalm-suppress MixedAssignment */
                $namedArguments[$param->getName()] = $this->getDependency($param);
            }
        }

        $callable = [$invocation->getThis(), $invocation->getMethod()->getName()];
        assert(is_callable($callable));

        return call_user_func_array($callable, $namedArguments);
    }

    /**
     * @param MethodInvocation<object> $invocation
     *
     * @return array<string, mixed>
     */
    private function getNamedArguments(MethodInvocation $invocation): array
    {
        $args = $invocation->getArguments();
        $params = $invocation->getMethod()->getParameters();
        $namedParams = [];
        foreach ($params as $param) {
            $pos = $param->getPosition();
            if (isset($args[$pos])) {
                /** @psalm-suppress MixedAssignment */
                $namedParams[$param->getName()] = $args[$pos];
            }
        }

        return $namedParams;
    }

    /**
     * @return mixed
     */
    private function getDependency(ReflectionParameter $param)
    {
        $named = (string) $this->getName($param);
        $type = $param->getType();
        assert($type instanceof ReflectionNamedType || $type === null);
        $typeName = $type ? $type->getName() : '';
        $interface = in_array($typeName, Argument::UNBOUND_TYPE) ? '' : $typeName;

        /** @var class-string $interface */
        return $this->injector->getInstance($interface, $named);
    }

    private function getName(ReflectionParameter $param): ?string
    {
        /** @var list<ReflectionAttribute> $nameds */
        $nameds = $param->getAttributes(Named::class);
        if (isset($nameds[0])) {
            $named = $nameds[0]->newInstance();
            assert($named instanceof Named);

            return $named->value;
        }

        if ($param->getAttributes(Inject::class)) {
            return null;
        }

        return $this->getCustomInject($param);
    }

    /**
     * @return ?class-string
     */
    private function getCustomInject(ReflectionParameter $param): ?string
    {
        /** @var list<ReflectionAttribute> $injects */
        $injects = $param->getAttributes(InjectInterface::class, ReflectionAttribute::IS_INSTANCEOF);
        if (! $injects) {
            return null;
        }

        $inject = $injects[0]->newInstance();
        assert($inject instanceof InjectInterface);

        return get_class($inject);
    }
}
