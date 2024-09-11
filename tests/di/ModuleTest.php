<?php

declare(strict_types=1);

namespace Ray\Di;

use PHPUnit\Framework\TestCase;
use Ray\Di\Exception\NotFound;

class ModuleTest extends TestCase
{
    public function testNew(): void
    {
        $module = new FakeInstanceBindModule();
        $this->assertInstanceOf(AbstractModule::class, $module);
    }

    public function testInstall(): void
    {
        $module = new FakeInstallModule();
        $this->assertInstanceOf(AbstractModule::class, $module);
    }

    public function testToInvalidClass(): void
    {
        $this->expectException(NotFound::class);
        new FakeToBindInvalidClassModule();
    }

    public function testRename(): void
    {
        $module = new FakeRenameModule(new FakeToBindModule());
        $instance = $module->getContainer()->getInstance(FakeRobotInterface::class, 'original');
        $this->assertInstanceOf(FakeRobotInterface::class, $instance);
    }

    public function testConstructorCallModule(): void
    {
        $module = new FakelNoConstructorCallModule();
        $container = $module->getContainer();
        $this->assertInstanceOf(Container::class, $container);
    }

    public function testActivate(): void
    {
        $module = new FakeInstanceBindModule();
        $this->assertInstanceOf(Container::class, $module->getContainer());
    }

    public function testtoString(): void
    {
        $string = (string) new FakeLogStringModule();
        $this->assertSame('0: -array -> (instance: array)
1: -bool -> (instance: boolean) 1
2: -int -> (instance: integer) 1
3: -null -> (instance: NULL)
4: -object -> (instance: object) stdClass
5: -string -> (instance: string) 1
6: Ray\Di\FakeAopInterface- -> (dependency) Ray\Di\FakeAop (aop) +returnSame(Ray\Di\FakeDoubleInterceptor)
7: Ray\Di\FakeDoubleInterceptor- -> (dependency) Ray\Di\FakeDoubleInterceptor
8: Ray\Di\FakeRobotInterface- -> (provider) (dependency) Ray\Di\FakeRobotProvider', $string);
    }
}
