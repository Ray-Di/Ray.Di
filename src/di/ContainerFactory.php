<?php

declare(strict_types=1);

namespace Ray\Di;

use Ray\Aop\Compiler;

use function array_shift;

final class ContainerFactory
{
    /**
     * @param AbstractModule|non-empty-array<AbstractModule>|null $module Module(s)
     */
    public function __invoke($module, string $classDir): Container
    {
        $oneModule = $this->getModule($module);
        // install built-in module
        $appModule = (new BuiltinModule())($oneModule);
        $container = $appModule->getContainer();
        // Compile null objects
        (new CompileNullObject())($container, $classDir);
        // Compile aspects
        $container->weaveAspects(new Compiler($classDir));

        return $container;
    }

    /**
     * @param AbstractModule|non-empty-array<AbstractModule>|null $module Module(s)
     */
    private function getModule($module): AbstractModule
    {
        if ($module instanceof AbstractModule) {
            return $module;
        }

        if ($module === null) {
            return new NullModule();
        }

        $modules = $module;
        $oneModule = array_shift($modules);
        foreach ($modules as $module) {
            $oneModule->install($module);
        }

        return $oneModule;
    }
}
