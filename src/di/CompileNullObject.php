<?php

declare(strict_types=1);

namespace Ray\Di;

/**
 * Convert NullObjectDependency to Dependency
 */
final class CompileNullObject
{
    public function __invoke(Container $container, string $scriptDir): void
    {
        $container->map(static function (DependencyInterface $dependency) use ($scriptDir) {
            if ($dependency instanceof NullObjectDependency) {
                return $dependency->toNull($scriptDir);
            }

            return $dependency;
        });
    }
}
