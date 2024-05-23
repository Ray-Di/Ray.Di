<?php

declare(strict_types=1);

namespace src;

use Ray\Di\Container;
use Ray\Di\DependencyInterface;
use Ray\Di\NullObjectDependency;

/**
 * Convert NullObjectDependency to Dependency
 */
final class CompileNullObject
{
    /**
     * @retrun void
     */
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
