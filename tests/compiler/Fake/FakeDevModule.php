<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Ray\Di\AbstractModule;

class FakeDevModule extends AbstractModule
{
    protected function configure()
    {
        $this->bind(FakeRobotInterface::class)->to(FakeDevRobot::class);
    }
}
