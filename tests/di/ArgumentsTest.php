<?php

declare(strict_types=1);

namespace Ray\Di;

use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ReflectionParameter;

use function spl_object_hash;

class ArgumentsTest extends TestCase
{
    /** @var Arguments */
    protected $arguments;

    protected function setUp(): void
    {
        $this->arguments = new Arguments(new ReflectionMethod(FakeCar::class, 'setTires'), new Name(Name::ANY));
    }

    public function testInject(): void
    {
        $container = (new FakeCarModule())->getContainer();
        $parameters = $this->arguments->inject($container);
        $this->assertInstanceOf(FakeTyre::class, $parameters[0]);
        $this->assertInstanceOf(FakeTyre::class, $parameters[1]);
        $param0 = $parameters[0];
        $this->assertNotSame(spl_object_hash($param0), $parameters[1]);
    }

    public function testParameterDefaultValue(): void
    {
        $defaultValue = (new ReflectionParameter([FakeHandleProvider::class, '__construct'], 'logo'))->getDefaultValue();
        $emptyContainer = new Container();
        $parameters = new Arguments(new ReflectionMethod(FakeHandleProvider::class, '__construct'), new Name(Name::ANY));
        $parametersValue = $parameters->inject($emptyContainer);
        $this->assertSame($defaultValue, $parametersValue[0]);
    }
}
