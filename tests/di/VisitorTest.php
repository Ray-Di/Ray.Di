<?php

declare(strict_types=1);

namespace Ray\Di;

use PHPUnit\Framework\TestCase;
use Ray\Aop\Bind as AopBind;
use ReflectionParameter;

use function implode;
use function sprintf;

class VisitorTest extends TestCase
{
    /* @var VisitorInterface */
    private $visitor;

    /** @var Dependency */
    private $dependency;

    public function setUp(): void
    {
        $this->visitor = new NullVisitor();
        $this->dependency = (new FakeCarModule())->getContainer()->getContainer()['Ray\Di\FakeCarInterface-'];
    }

    public function testNullVisit(): void
    {
        $maybeTrue = $this->dependency->accept($this->visitor);
        $this->assertTrue($maybeTrue);
    }

    public function testCollectVisit(): void
    {
        $collector = new class () {
            public $args = [];
            public $methods = [];
            public $newInstance;

            public function pushArg(string $arg)
            {
                $this->args[] = $arg;
            }

            public function pushMethod(string $method)
            {
                $this->methods[] = sprintf('%s(%s)', $method, implode(',', $this->args));
                $this->args = [];
            }

            public function pushNewInstance(string $class)
            {
                $this->newInstance = sprintf('%s(%s)', $class, implode(',', $this->args));
                $this->args = [];
            }
        };

        $visitor = new class ($collector) implements VisitorInterface
        {
            private $collector;

            public function __construct($collector)
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

            public function visitInstance(Instance $value)
            {
            }

            public function visitAspectBind(AopBind $aopBind)
            {
            }

            public function visitNewInstance(string $class, SetterMethods $setterMethods, ?Arguments $arguments, ?AspectBind $bind)
            {
                $arguments->accept($this);
                $setterMethods->accept($this);
                $this->collector->pushNewInstance($class);
            }

            public function visitSetterMethods(array $setterMethods)
            {
                foreach ($setterMethods as $setterMethod) {
                    $setterMethod->accept($this);
                }
            }

            public function visitSetterMethod(string $method, Arguments $arguments)
            {
                $this->collector->pushMethod($method);
                $arguments->accept($this);
            }

            public function visitArguments(array $arguments)
            {
                foreach ($arguments as $argument) {
                    $argument->accept($this);
                }
            }

            public function visitArgument(string $index, bool $isDefaultAvailable, $defaultValue, ReflectionParameter $parameter)
            {
                $this->collector->pushArg($index);
            }
        };

        $this->dependency->accept($visitor);
        $this->assertSame('Ray\Di\FakeCar(Ray\Di\FakeGearStickInterface-)', $collector->newInstance);
        $this->assertSame('setTires(Ray\Di\FakeEngineInterface-)', $collector->methods[0]);
        $this->assertSame('setHardtop(Ray\Di\FakeTyreInterface-,Ray\Di\FakeTyreInterface-)', $collector->methods[1]);
    }
}
