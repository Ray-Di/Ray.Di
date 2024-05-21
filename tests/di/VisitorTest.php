<?php

declare(strict_types=1);

namespace Ray\Di;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

class VisitorTest extends TestCase
{
    /* @var VisitorInterface */
    private $visitor;

    public function setUp(): void
    {
        $conatiner = (new FakeCarModule())->getContainer();
        $this->visitor = new CompileVisitor($conatiner);
    }

    public function testVisit(): void
    {
        $script = $this->visitor->visitDependency($this->getNewInstance(), 'postConstruct', true);
    }

    private function getNewInstance(): NewInstance
    {
        $class = new ReflectionClass(FakeCar::class);
        $setters = [];
        $name = new Name(Name::ANY);
        $setters[] = new SetterMethod(new ReflectionMethod(FakeCar::class, 'setTires'), $name);
        $setters[] = new SetterMethod(new ReflectionMethod(FakeCar::class, 'setHardtop'), $name);
        $setterMethods = new SetterMethods($setters);

        return new NewInstance($class, $setterMethods);
    }
}
