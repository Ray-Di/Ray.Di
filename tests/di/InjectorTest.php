<?php

declare(strict_types=1);

namespace Ray\Di;

use LogicException;
use PDO;
use PHPUnit\Framework\TestCase;
use Ray\Aop\NullInterceptor;
use Ray\Di\Exception\InvalidToConstructorNameParameter;
use Ray\Di\Exception\Unbound;

use function assert;
use function defined;
use function file_get_contents;
use function is_string;
use function passthru;
use function serialize;
use function spl_object_hash;
use function unlink;
use function unserialize;

class InjectorTest extends TestCase
{
    public function testNew(): void
    {
        $injector = new Injector(new FakeInstanceBindModule());
        $this->assertInstanceOf(Injector::class, $injector);
    }

    public function testGetToInstance(): void
    {
        $injector = new Injector(new FakeInstanceBindModule());
        $instance = $injector->getInstance('', 'one');
        $this->assertSame(1, $instance);
    }

    public function testToInstance(): void
    {
        $engine = new FakeEngine();
        $injector = new Injector(new FakeClassInstanceBindModule($engine));
        $this->assertSame($engine, $injector->getInstance(FakeEngine::class));
    }

    public function testUnbound(): void
    {
        $this->expectException(Unbound::class);
        $injector = new Injector(new FakeInstanceBindModule());
        $injector->getInstance('', 'invalid-binding-xxx');
    }

    public function testInstall(): void
    {
        $injector = new Injector(new FakeInstallModule());
        $instance = $injector->getInstance('', 'one');
        $this->assertSame(1, $instance);
        $instance = $injector->getInstance('', 'two');
        $this->assertSame(2, $instance);
    }

    public function testFormerBindingHasPriority(): void
    {
        $injector = new Injector(new FakeFormerBindingHasPriorityModule());
        $instance = $injector->getInstance('', 'one');
        $this->assertSame(1, $instance);
    }

    public function testLatterBindingHasPriorityWithThisParameter(): void
    {
        $injector = new Injector(new FakeOverrideInstallModule());
        $instance = $injector->getInstance('', 'one');
        $this->assertSame(3, $instance);
    }

    public function testModuleInModule(): void
    {
        $injector = new Injector(new FakeModuleInModule());
        $instance = $injector->getInstance('', 'one');
        $this->assertSame(1, $instance);
        $instance = $injector->getInstance('', 'two');
        $this->assertSame(2, $instance);
    }

    public function testModuleInModuleOverride(): void
    {
        $injector = new Injector(new FakeModuleInModuleOverride());
        $instance = $injector->getInstance('', 'one');
        $this->assertSame(3, $instance);
    }

    public function testToBinding(): void
    {
        $injector = new Injector(new FakeToBindModule());
        $instance = $injector->getInstance(FakeRobotInterface::class);
        $this->assertInstanceOf(FakeRobot::class, $instance);
    }

    public function testClassToClassBinding(): void
    {
        $injector = new Injector(new FakeCarEngineModule());
        $instance = $injector->getInstance(FakeEngine::class);
        $this->assertInstanceOf(FakeCarEngine::class, $instance);
    }

    public function testToBindingPrototype(): void
    {
        $injector = new Injector(new FakeToBindModule());
        $instance1 = $injector->getInstance(FakeRobotInterface::class);
        $instance2 = $injector->getInstance(FakeRobotInterface::class);
        $this->assertNotSame(spl_object_hash($instance1), spl_object_hash($instance2));
    }

    public function testToBindingSingleton(): void
    {
        $injector = new Injector(new FakeToBindSingletonModule());
        $instance1 = $injector->getInstance(FakeRobotInterface::class);
        $instance2 = $injector->getInstance(FakeRobotInterface::class);
        $this->assertSame(spl_object_hash($instance1), spl_object_hash($instance2));
    }

    public function testToProviderBinding(): void
    {
        $injector = new Injector(new FakeToProviderBindModule());
        $instance1 = $injector->getInstance(FakeRobotInterface::class);
        $instance2 = $injector->getInstance(FakeRobotInterface::class);
        $this->assertNotSame(spl_object_hash($instance1), spl_object_hash($instance2));
    }

    public function testClassToProviderBinding(): void
    {
        $injector = new Injector(new FakeEngineToProviderModule());
        $instance = $injector->getInstance(FakeEngine::class);
        $this->assertInstanceOf(FakeEngine::class, $instance);
    }

    public function testToProviderBindingSingleton(): void
    {
        $injector = new Injector(new FakeToProviderSingletonBindModule());
        $instance1 = $injector->getInstance(FakeRobotInterface::class);
        $instance2 = $injector->getInstance(FakeRobotInterface::class);
        $this->assertSame(spl_object_hash($instance1), spl_object_hash($instance2));
    }

    public function testGetConcreteClass(): void
    {
        $injector = new Injector();
        $robot = $injector->getInstance(FakeRobot::class);
        $this->assertInstanceOf(FakeRobot::class, $robot);
    }

    public function testGetConcreteHavingDependency(): void
    {
        $injector = new Injector(new class extends AbstractModule{
            protected function configure()
            {
                $this->bind(FakeRobot::class);
            }
        });
        $team = $injector->getInstance(FakeRobotTeam::class);
        $this->assertInstanceOf(FakeRobotTeam::class, $team);
        $this->assertInstanceOf(FakeRobot::class, $team->robot1);
        $this->assertInstanceOf(FakeRobot::class, $team->robot2);
    }

    public function testGetConcreteClassWithModule(): void
    {
        $injector = new Injector(new FakeCarModule());
        $car = $injector->getInstance(FakeCar::class);
        $this->assertInstanceOf(FakeCar::class, $car);
    }

    public function testAnnotationBasedInjection(): Injector
    {
        $injector = new Injector(new FakeCarModule());
        $car = $injector->getInstance(FakeCarInterface::class);
        /** @var FakeCar $car */
        $this->assertInstanceOf(FakeCar::class, $car);
        $this->assertInstanceOf(FakeTyre::class, $car->frontTyre);
        $this->assertInstanceOf(FakeTyre::class, $car->rearTyre);
        $this->assertInstanceOf(FakeHardtop::class, $car->hardtop);
        $this->assertInstanceOf(FakeMirrorInterface::class, $car->rightMirror);
        $this->assertInstanceOf(FakeMirrorInterface::class, $car->spareMirror);
        $this->assertSame(spl_object_hash($car->rightMirror), spl_object_hash($car->spareMirror));
        $this->assertInstanceOf(FakeHandle::class, $car->handle);
        $this->assertSame($car->handle->logo, 'momo');

        return $injector;
    }

    /**
     * @depends testAnnotationBasedInjection
     */
    public function testSerialize(Injector $injector): void
    {
        $extractedInjector = unserialize(serialize($injector));
        assert($extractedInjector instanceof InjectorInterface);
        $car = $extractedInjector->getInstance(FakeCarInterface::class);
        $this->assertInstanceOf(FakeCar::class, $car);
    }

    public function testAop(): void
    {
        $injector = new Injector(new FakeAopModule());
        $instance = $injector->getInstance(FakeAopInterface::class);
        /** @var FakeAop $instance */
        $result = $instance->returnSame(2);
        $this->assertSame(4, $result);
    }

    public function testIntefaceBindingAop(): void
    {
        $module = new class extends AbstractModule{
            protected function configure()
            {
                $this->bind(FakeAopInterface::class)->to(FakeAop::class);
                $this->bind(FakeDoubleInterceptorInterface::class)->to(FakeDoubleInterceptor::class);
                $this->bindInterceptor(
                    $this->matcher->any(),
                    $this->matcher->any(),
                    [FakeDoubleInterceptorInterface::class]
                );
            }
        };
        $injector = new Injector($module);
        $instance = $injector->getInstance(FakeAopInterface::class);
        /** @var FakeAop $instance */
        $result = $instance->returnSame(2);
        $this->assertSame(4, $result);
    }

    public function testBuiltinBinding(): void
    {
        $instance = (new Injector())->getInstance(FakeBuiltin::class);
        $this->assertInstanceOf(Injector::class, $instance->injector);
    }

    public function testSerializeBuiltinBinding(): void
    {
        $injector = unserialize(serialize(new Injector()));
        assert($injector instanceof InjectorInterface);
        $instance = $injector->getInstance(FakeBuiltin::class);
        $this->assertInstanceOf(Injector::class, $instance->injector);
    }

    public function testAopBoundInDifferentModule(): void
    {
        $injector = new Injector(new FakeAopInstallModule());
        $instance = $injector->getInstance(FakeAopInterface::class);
        /** @var FakeAop $instance */
        $result = $instance->returnSame(2);
        $this->assertSame(4, $result);
    }

    public function testAopBoundInDifferentModuleAfterAnotherBinding(): void
    {
        $injector = new Injector(new FakeAopInstallModule(new FakeAopModule()));
        $instance = $injector->getInstance(FakeAopInterface::class);
        /** @var FakeAop $instance */
        $result = $instance->returnSame(2);
        $this->assertSame(8, $result);
    }

    public function testAopBoundDoublyInDifferentModule(): void
    {
        $injector = new Injector(new FakeAopDoublyInstallModule());
        $instance = $injector->getInstance(FakeAopInterface::class);
        /** @var FakeAop $instance */
        $result = $instance->returnSame(2);
        $this->assertSame(8, $result);
    }

    public function testAopClassAutoloader(): void
    {
        passthru('php ' . __DIR__ . '/script/aop.php');
        $cacheFile = __DIR__ . '/script/aop.php.cache.txt';
        $cache = file_get_contents($cacheFile);
        if (! is_string($cache)) {
            throw new LogicException();
        }

        $injector = unserialize($cache);
        if (! $injector instanceof Injector) {
            throw new LogicException();
        }

        $instance = $injector->getInstance(FakeAopInterface::class);
        /** @var FakeAop $instance */
        $result = $instance->returnSame(2);
        $this->assertSame(4, $result);
        unlink($cacheFile);
    }

    public function testAopOnDemandByUnboundConcreteClass(): void
    {
        $injector = new Injector(new FakeAopInterceptorModule());
        $instance = $injector->getInstance(FakeAop::class);
        $result = $instance->returnSame(2);
        $this->assertSame(4, $result);
    }

    public function testBindOrder(): void
    {
        $injector = new Injector(new FakeAnnoModule());
        $instance = $injector->getInstance(FakeAnnoOrderClass::class);
        $instance->get();
        $expect = [FakeAnnoInterceptor4::class, FakeAnnoInterceptor1::class, FakeAnnoInterceptor2::class, FakeAnnoInterceptor3::class, FakeAnnoInterceptor5::class];
        $this->assertSame($expect, FakeAnnoClass::$order);
    }

    public function testAnnotateConstant(): void
    {
        $instance = (new Injector(new FakeConstantModule()))->getInstance(FakeConstantConsumer::class);
        $this->assertSame('default_construct', $instance->defaultByConstruct);
    }

    public function testContextualDependencyInjection(): void
    {
        $injector = new Injector(new FakeWalkRobotModule());
        $robot = $injector->getInstance(FakeWalkRobot::class);
        $this->assertInstanceOf(FakeLeftLeg::class, $robot->leftLeg);
        $this->assertInstanceOf(FakeRightLeg::class, $robot->rightLeg);
    }

    public function testNewAbstract(): void
    {
        $this->expectException(Unbound::class);
        (new Injector())->getInstance(FakeConcreteClass::class);
    }

    public function testIsOptionalValue(): void
    {
        if (! defined('HHVM_VERSION')) {
            $pdo = (new Injector(new FakePdoModule()))->getInstance(PDO::class);
            $this->assertInstanceOf(PDO::class, $pdo);
        }
    }

    public function testInternalTypes(): void
    {
        $injector = new Injector(new FakeInternalTypeModule());
        $types = $injector->getInstance(FakeInternalTypes::class);
        $this->assertInstanceOf(FakeInternalTypes::class, $types);
    }

    public function testToConstructor(): void
    {
        $module = new class extends AbstractModule {
            protected function configure()
            {
                $this->bind(PDO::class)->toConstructor(
                    PDO::class,
                    ['dsn' => 'pdo_dsn']
                )->in(Scope::SINGLETON);
                $this->bind()->annotatedWith('pdo_dsn')->toInstance('sqlite::memory:');
            }
        };
        $injector = new Injector($module);
        $pdo = $injector->getInstance(PDO::class);
        $this->assertInstanceOf(PDO::class, $pdo);
    }

    public function testToConstructorInvalidName(): void
    {
        $this->expectException(InvalidToConstructorNameParameter::class);
        $module = new class extends AbstractModule {
            protected function configure()
            {
                $this->bind(PDO::class)->toConstructor(
                    PDO::class,
                    /** @phpstan-ignore-next-line */
                    [['dsn' => 'pdo_dsn']] // wrong, cause InvalidToConstructorNameParameter exception
                )->in(Scope::SINGLETON);
                $this->bind()->annotatedWith('pdo_dsn')->toInstance('sqlite::memory:');
            }
        };
        $injector = new Injector($module);
        $pdo = $injector->getInstance(PDO::class);
        $this->assertInstanceOf(PDO::class, $pdo);
    }

    public function testNullObject(): void
    {
        $injector = (new Injector(new class extends AbstractModule {
            protected function configure()
            {
                $this->bind(FakeTyreInterface::class)->toNull();
            }
        }));
        $nullObject = $injector->getInstance(FakeTyreInterface::class);
        $this->assertInstanceOf(FakeTyreInterface::class, $nullObject);
    }

    public function testBindInterfeceInterceptor(): void
    {
        $injector = (new Injector(new class extends AbstractModule {
            protected function configure()
            {
                $this->bind(FakeAop::class);
                $this->bind(FakeDoubleInterceptorInterface::class)->to(FakeDoubleInterceptor::class);
                $this->bindInterceptor(
                    $this->matcher->any(),
                    $this->matcher->any(),
                    [FakeDoubleInterceptorInterface::class]
                );
            }
        }));
        $instance = $injector->getInstance(FakeAop::class);
        $result = $instance->returnSame(2);
        $this->assertSame(4, $result);
    }

    public function testBindInterfeceNullInterceptor(): void
    {
        $injector = (new Injector(new class extends AbstractModule {
            protected function configure()
            {
                $this->bind(FakeAop::class);
                $this->bind(FakeDoubleInterceptorInterface::class)->to(NullInterceptor::class);
                $this->bindInterceptor(
                    $this->matcher->any(),
                    $this->matcher->any(),
                    [FakeDoubleInterceptorInterface::class]
                );
            }
        }));
        $instance = $injector->getInstance(FakeAop::class);
        $result = $instance->returnSame(2);
        $this->assertSame(2, $result);
        $this->assertInstanceOf(NullInterceptor::class, $instance->bindings['returnSame'][0]); // @phpstan-ignore-line
    }

    public function testModuleArray(): void
    {
        $modules = [
            new class extends AbstractModule
            {
                protected function configure()
                {
                    $this->bind()->annotatedWith('var')->toInstance('a');
                    $this->bind()->annotatedWith('first')->toInstance('1');
                }
            },
            new class extends AbstractModule
            {
                protected function configure()
                {
                    $this->bind()->annotatedWith('var')->toInstance('b');
                    $this->bind()->annotatedWith('second')->toInstance('2');
                }
            },
        ];
        $injector = new Injector($modules);
        $this->assertSame('a', $injector->getInstance('', 'var'));
        $this->assertSame('2', $injector->getInstance('', 'second'));
        $this->assertSame('1', $injector->getInstance('', 'first'));
    }

    public function testSingleArray(): void
    {
        $modules = [
            new class extends AbstractModule
            {
                protected function configure()
                {
                    $this->bind()->annotatedWith('var')->toInstance('a');
                    $this->bind()->annotatedWith('first')->toInstance('1');
                }
            },
        ];
        $injector = new Injector($modules);
        $this->assertSame('a', $injector->getInstance('', 'var'));
    }

    /**
     * @requires PHP 8.0
     */
    public function testProviderInjectWithSet(): void
    {
        $injector = new Injector(new class extends AbstractModule{
            protected function configure()
            {
                $this->bind(FakeEngineInterface::class)->to(FakeEngine::class);
            }
        });
        $fakeSet = $injector->getInstance(FakeSet::class);
        $this->assertInstanceOf(ProviderInterface::class, $fakeSet->provider);
        $this->assertInstanceOf(FakeEngine::class, $fakeSet->provider->get());
    }
}
