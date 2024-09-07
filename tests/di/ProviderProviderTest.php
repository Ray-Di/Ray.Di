<?php

declare(strict_types=1);

namespace Ray\Di;

use PHPUnit\Framework\TestCase;
use Ray\Di\Di\Set;

class ProviderProviderTest extends TestCase
{
    public function testGet(): void
    {
        $injector = new Injector(
            new class extends AbstractModule {
                protected function configure()
                {
                    $this->bind(FakeEngineInterface::class)->toInstance(new FakeEngine());
                }
            }
        );
        $set = new Set(FakeEngineInterface::class);
        $provider = new ProviderProvider($injector, $set);
        $instance = $provider->get();
        $this->assertInstanceOf(FakeEngine::class, $instance);
    }
}
