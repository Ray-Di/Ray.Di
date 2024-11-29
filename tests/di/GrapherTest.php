<?php

declare(strict_types=1);

namespace Ray\Di;

use LogicException;
use PHPUnit\Framework\TestCase;

use function assert;
use function file_get_contents;
use function is_object;
use function is_string;
use function passthru;
use function property_exists;
use function unserialize;

class GrapherTest extends TestCase
{
    public function testNew(): void
    {
        $grapher = new Grapher(new FakeInstanceBindModule(), __DIR__ . '/tmp');
        $this->assertInstanceOf(Grapher::class, $grapher);
    }

    public function testGetInstanceWithArgs(): void
    {
        $grapher = new Grapher(new FakeUntargetModule(), __DIR__ . '/tmp');
        $instance = $grapher->newInstanceArgs(FakeUntargetChild::class, ['1']);
        $this->assertInstanceOf(FakeUntargetChild::class, $instance);
        assert(property_exists($instance, 'val'));
        $this->assertSame('1', $instance->val);
    }

    public function testAopClassAutoloader(): void
    {
        passthru('php ' . __DIR__ . '/script/grapher.php');
        $cacheFile = __DIR__ . '/script/grapher.php.txt';
        $cache = file_get_contents($cacheFile);
        if (! is_string($cache)) {
            throw new LogicException();
        }

        $grapher = unserialize($cache);
        assert($grapher instanceof Grapher);
        $this->assertInstanceOf(Grapher::class, $grapher);

        $instance = $grapher->newInstanceArgs(FakeAopInterface::class, ['a']);
        /** @var FakeAopGrapher $instance */
        $result = $instance->returnSame(2);
        $this->assertSame(4, $result);
        $this->assertSame('a', $instance->a);
    }
}
