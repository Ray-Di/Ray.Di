<?php

declare(strict_types=1);

namespace Ray\Di;

use Ray\Aop\NullInterceptor;

class FakeCarModule extends AbstractModule
{
    protected function configure()
    {
        $this->bind(FakeCarInterface::class)->to(FakeCar::class); // dependent
        $this->bind(FakeEngineInterface::class)->to(FakeEngine::class); // constructor
        $this->bind(FakeHardtopInterface::class)->to(FakeHardtop::class); // optional setter
        $this->bind(FakeTyreInterface::class)->to(FakeTyre::class); // setter
        $this->bind(FakeMirrorInterface::class)->annotatedWith('right')->to(FakeMirrorRight::class)->in(Scope::SINGLETON); // named binding
        $this->bind(FakeMirrorInterface::class)->annotatedWith('left')->to(FakeMirrorLeft::class)->in(Scope::SINGLETON); // named binding
        $this->bind('')->annotatedWith('logo')->toInstance('momo');
        $this->bind(FakeHandleInterface::class)->toProvider(FakeHandleProvider::class);
        $this->bind(FakeGearStickInterface::class)->to(FakeLeatherGearStick::class);
        $this->bindInterceptor(
            $this->matcher->any(),
            $this->matcher->any('start'),
            [NullInterceptor::class]
        );
    }
}
