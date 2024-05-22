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

    public function setUp(): void
    {
        $this->visitor = new NullVisitor();
        $dependency = (new FakeCarModule())->getContainer()->getContainer()['Ray\Di\FakeCarInterface-'];
        assert($dependency instanceof Dependency);
        $this->dependency = $dependency;
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

            public function pushArg(string $arg): void
            {
                $this->args[] = $arg;
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
        };

        $visitor = new class ($collector) implements VisitorInterface
        {
            /** @var object */
            private $collector;

            public function __construct(object $collector)
            {
                $this->collector = $collector;
            }

            public function visitDependency(NewInstance $newInstance, ?string $postConstruct, bool $isSingleton)
            {
                $newInstance->accept($this);
            }

            public function visitProvider(Dependency $dependency, string $context, bool $isSingleton): string
            {
                return '';
            }

            /** @inheritDoc */
            public function visitInstance($value)
            {
            }

            public function visitAspectBind(AopBind $aopBind)
            {
            }

            public function visitNewInstance(string $class, SetterMethods $setterMethods, ?Arguments $arguments, ?AspectBind $bind)
            {
                if ($arguments) {
                    $arguments->accept($this);
                }

                $setterMethods->accept($this);
                assert(method_exists($this->collector, 'pushNewInstance'));
                $this->collector->pushNewInstance($class);
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
                assert(method_exists($this->collector, 'pushArg'));
                $this->collector->pushArg($index);
            }
        };

        $this->dependency->accept($visitor);
        $this->assertSame('Ray\Di\FakeCar(Ray\Di\FakeGearStickInterface-)', $collector->newInstance);
        $this->assertSame('setTires(Ray\Di\FakeEngineInterface-)', $collector->methods[0]);
        $this->assertSame('setHardtop(Ray\Di\FakeTyreInterface-,Ray\Di\FakeTyreInterface-)', $collector->methods[1]);
    }
}
