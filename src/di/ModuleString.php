<?php

declare(strict_types=1);

namespace Ray\Di;

use Ray\Aop\Pointcut;

use function assert;
use function implode;
use function serialize;
use function sort;
use function sprintf;
use function unserialize;

use const PHP_EOL;

final class ModuleString
{
    /**
     * @param array<int, Pointcut> $pointcuts
     */
    public function __invoke(Container $container, array $pointcuts): string
    {
        $log = [];
        /** @psalm-suppress MixedAssignment */
        $container = unserialize(serialize($container), ['allowed_classes' => true]);
        assert($container instanceof Container);
        $spy = new SpyCompiler();
        foreach ($container->getContainer() as $dependencyIndex => $dependency) {
            if ($dependency instanceof Dependency) {
                $dependency->weaveAspects($spy, $pointcuts);
            }

            $log[] = sprintf(
                '%s -> %s',
                $dependencyIndex,
                (string) $dependency
            );
        }

        sort($log);
        $numLog = [];
        foreach ($log as $key => $value) {
            $numLog[] = sprintf('%d: %s', $key, $value);
        }

        return implode(PHP_EOL, $numLog);
    }
}
