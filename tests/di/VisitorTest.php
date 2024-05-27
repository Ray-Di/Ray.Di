<?php

declare(strict_types=1);

namespace Ray\Di;

use PHPUnit\Framework\TestCase;
use Ray\Aop\Bind as AopBind;
use ReflectionParameter;

use function assert;
use function implode;
use function method_exists;
use function sprintf;

class VisitorTest extends TestCase
{
    /** @var NullVisitor */
    private $visitor;

    /** @var Dependency */
    private $dependency;

    /** @var DependencyProvider */
    private $dependencyProvider;

    /** @var Container  */
    private $container;

    public function setUp(): void
    {
        $this->visitor = new NullVisitor();
        $this->container = (new ContainerFactory())(new FakeCarModule(), __DIR__);
        $container = $this->container->getContainer();
        $dependency = $container['Ray\Di\FakeCarInterface-'];
        assert($dependency instanceof Dependency);
        $this->dependency = $dependency;
        $dependencyProvider = $container['Ray\Di\FakeHandleInterface-'];
        assert($dependencyProvider instanceof DependencyProvider);
        $this->dependencyProvider = $dependencyProvider;
    }

    public function testNullVisit(): void
    {
        $maybeTrue = $this->dependency->accept($this->visitor);
        $this->assertTrue($maybeTrue);
    }

    public function testCollectVisit(): void
    {
        $collector = new class () {
            /** @var array<string> */
            public $args = [];

            /** @var array<string> */
            public $methods = [];

            /** @var string */
            public $newInstance;

            /** @var AopBind */
            public $bind;

            public function pushArg(string $arg, bool $isSingleton): void
            {
                $type = $isSingleton ? 'singleton.' : 'prototype.';
                $this->args[] = $type . $arg;
            }

            public function pushMethod(string $method): void
            {
                $this->methods[] = sprintf('%s(%s)', $method, implode(',', $this->args));
                $this->args = [];
            }

            public function pushNewInstance(string $class): void
            {
                $this->newInstance = sprintf('%s(%s)', $class, implode(',', $this->args));
                $this->args = [];
            }

            public function pushAopBind(AopBind $bind): void
            {
                $this->bind = $bind;
            }
        };

        $visitor = new class ($collector, $this->container) implements VisitorInterface
        {
            /** @var object */
            private $collector;

            /** @var Container */
            private $container;

            public function __construct(object $collector, Container $container)
            {
                $this->collector = $collector;
                $this->container = $container;
            }

            public function visitDependency(NewInstance $newInstance, ?string $postConstruct, bool $isSingleton)
            {
                $newInstance->accept($this);
            }

            public function visitProvider(Dependency $dependency, string $context, bool $isSingleton): string
            {
                return 'visitProvider';
            }

            /** @inheritDoc */
            public function visitInstance($value): string
            {
                return 'visitInstance';
            }

            public function visitAspectBind(AopBind $aopBind)
            {
                assert(method_exists($this->collector, 'pushAopBind'));
                $this->collector->pushAopBind($aopBind);
            }

            public function visitNewInstance(string $class, SetterMethods $setterMethods, ?Arguments $arguments, ?AspectBind $bind)
            {
                if ($arguments) {
                    $arguments->accept($this);
                }

                $setterMethods->accept($this);
                assert(method_exists($this->collector, 'pushNewInstance'));
                $this->collector->pushNewInstance($class);
                if ($bind instanceof AspectBind) {
                    $bind->accept($this);
                }
            }

            /** @inheritDoc */
            public function visitSetterMethods(array $setterMethods)
            {
                foreach ($setterMethods as $setterMethod) {
                    $setterMethod->accept($this);
                }
            }

            /** @inheritDoc */
            public function visitSetterMethod(string $method, Arguments $arguments)
            {
                assert(method_exists($this->collector, 'pushMethod'));
                $this->collector->pushMethod($method);
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
            public function visitArgument(string $index, bool $isDefaultAvailable, $defaultValue, ReflectionParameter $parameter)
            {
                $container = $this->container->getContainer();
                $dependency = $container[$index];
                assert($dependency instanceof Dependency || $dependency instanceof DependencyProvider);
                $isSingleton = $dependency->isSingleton();
                assert(method_exists($this->collector, 'pushArg'));
                $this->collector->pushArg($index, $isSingleton);
            }
        };

        $this->dependency->accept($visitor);
        $this->assertStringContainsString('Ray\Di\FakeCar', $collector->newInstance);
        $this->assertStringContainsString('(prototype.Ray\Di\FakeGearStickInterface-)', $collector->newInstance);
        $this->assertSame('setTires(prototype.Ray\Di\FakeEngineInterface-)', $collector->methods[0]);
        $this->assertSame('setHardtop(prototype.Ray\Di\FakeTyreInterface-,prototype.Ray\Di\FakeTyreInterface-)', $collector->methods[1]);
    }

    public function testVisitDependencyProvider(): void
    {
        $result = $this->dependencyProvider->accept($this->visitor);
        $this->assertTrue($result);
    }

    public function testVisitInsntace(): void
    {
        $instance = new Instance('1');
        $this->assertSame('1', $instance->accept($this->visitor));
    }
}
