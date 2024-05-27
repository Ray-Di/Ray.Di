<?php

declare(strict_types=1);

namespace Ray\Di;

use Ray\Di\MultiBinding\MultiBindingModule;

final class BuiltinModule
{
    public function __invoke(AbstractModule $module): AbstractModule
    {
        $module->install(new AssistedModule());
        $module->install(new ProviderSetModule());
        $module->install(new MultiBindingModule());

        return $module;
    }
}
