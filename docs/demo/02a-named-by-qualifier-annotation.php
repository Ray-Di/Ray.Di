<?php

namespace Ray\Di\Demo;

use Ray\Di\Injector;
use Ray\Di\AbstractModule;

require __DIR__ . '/bootstrap.php';

class BindingAnnotationModule extends AbstractModule
{
    protected function configure()
    {
        $this->bind(LegInterface::class)->annotatedWith(Left::class)->to(LeftLeg::class);
        $this->bind(LegInterface::class)->annotatedWith(Right::class)->to(RightLeg::class);
        $this->bind(QualifierRobot::class);
    }
}

$injector = new Injector(new BindingAnnotationModule);
$robot = $injector->getInstance(QualifierRobot::class);
/** @var $robot Robot */
$works = ($robot->leftLeg instanceof LeftLeg);

echo ($works ? 'It works!' : 'It DOES NOT work!') . PHP_EOL;
