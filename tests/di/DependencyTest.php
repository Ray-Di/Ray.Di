<?php

declare(strict_types=1);

namespace Ray\Di;

use PHPUnit\Framework\TestCase;
use Ray\Aop\Compiler;
use Ray\Aop\Matcher;
use Ray\Aop\Pointcut;
use Ray\Aop\WeavedInterface;
use ReflectionClass;
use ReflectionMethod;

use function assert;
use function is_object;
use function property_exists;
use function spl_object_hash;

class DependencyTest extends TestCase
{
    /** @var Dependency */
    private $dependency;

    protected function setUp(): void
    {
        $class = new ReflectionClass(FakeCar::class);
        $setters = [];
        $name = new Name(Name::ANY);
        $setters[] = new SetterMethod(new ReflectionMethod(FakeCar::class, 'setTires'), $name);
        $setters[] = new SetterMethod(new ReflectionMethod(FakeCar::class, 'setHardtop'), $name);
        $setterMethods = new SetterMethods($setters);
        $newInstance = new NewInstance($class, $setterMethods);
        $this->dependency = new Dependency($newInstance, new ReflectionMethod(FakeCar::class, 'postConstruct'));
    }

    /**
     * @return Container[][]
     * @psalm-return array{0: array{0: Container}}
     */
    public function containerProvider(): array
    {
        $container = new Container();
        (new Bind($container, FakeTyreInterface::class))->to(FakeTyre::class);
        (new Bind($container, FakeEngineInterface::class))->to(FakeEngine::class);
        (new Bind($container, FakeHardtopInterface::class))->to(FakeHardtop::class);

        return [[$container]];
    }

    /**
     * @dataProvider containerProvider
     */
    public function testInject(Container $container): void
    {
        $car = $this->dependency->inject($container);
        /** @var FakeCar $car */
        $this->assertInstanceOf(FakeCar::class, $car);
    }

    /**
     * @dataProvider containerProvider
     */
    public function testSetterInjection(Container $container): void
    {
        $car = $this->dependency->inject($container);
        /** @var FakeCar $car */
        $this->assertInstanceOf(FakeCar::class, $car);
        $this->assertInstanceOf(FakeTyre::class, $car->frontTyre);
    }

    /**
     * @dataProvider containerProvider
     */
    public function testPostConstruct(Container $container): void
    {
        $car = $this->dependency->inject($container);
        /** @var FakeCar $car */
        $this->assertTrue($car->isConstructed);
    }

    /**
     * @dataProvider containerProvider
     */
    public function testPrototype(Container $container): void
    {
        $this->dependency->setScope(Scope::PROTOTYPE);
        $car1 = $this->dependency->inject($container);
        $car2 = $this->dependency->inject($container);
        assert(is_object($car1) && is_object($car2));
        $this->assertNotSame(spl_object_hash($car1), spl_object_hash($car2));
    }

    /**
     * @dataProvider containerProvider
     */
    public function testSingleton(Container $container): void
    {
        $this->dependency->setScope(Scope::SINGLETON);
        $car1 = $this->dependency->inject($container);
        $car2 = $this->dependency->inject($container);
        assert(is_object($car1) && is_object($car2));
        $this->assertSame(spl_object_hash($car1), spl_object_hash($car2));
    }

    public function testInjectInterceptor(): void
    {
        $dependency = new Dependency(new NewInstance(new ReflectionClass(FakeAop::class), new SetterMethods([])));
        $pointcut = new Pointcut((new Matcher())->any(), (new Matcher())->any(), [FakeDoubleInterceptor::class]);
        $dependency->weaveAspects(new Compiler(__DIR__ . '/tmp'), [$pointcut]);
        $container = new Container();
        $container->add((new Bind($container, FakeDoubleInterceptor::class))->to(FakeDoubleInterceptor::class));
        $instance = $dependency->inject($container);
        assert(is_object($instance));
        $isWeave = (new ReflectionClass($instance))->implementsInterface(WeavedInterface::class);
        $this->assertTrue($isWeave);
        assert(property_exists($instance, 'bindings'));
        $this->assertArrayHasKey('returnSame', (array) $instance->bindings);
    }

    /**
     * @dataProvider containerProvider
     * @covers \Ray\Di\Dependency::injectWithArgs
     */
    public function testInjectWithArgsPostConstruct(Container $container): void
    {
        $car = $this->dependency->injectWithArgs($container, [new FakeEngine()]);
        $this->assertInstanceOf(FakeCar::class, $car);
    }

    /**
     * @dataProvider containerProvider
     * @covers \Ray\Di\Dependency::injectWithArgs
     */
    public function testInjectWithArgsSingleton(Container $container): void
    {
        $this->dependency->setScope(Scope::SINGLETON);
        $this->dependency->injectWithArgs($container, [new FakeEngine()]);
        $car = $this->dependency->injectWithArgs($container, [new FakeEngine()]);
        $this->assertInstanceOf(FakeCar::class, $car);
    }
}
