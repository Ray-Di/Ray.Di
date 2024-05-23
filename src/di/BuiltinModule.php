<?php

declare(strict_types=1);

namespace Ray\Di;

use Ray\Compiler\MapModule;
use Ray\Compiler\PramReaderModule;

use function count;

final class BuiltinModule
{
    public function __invoke(AbstractModule $module): AbstractModule
    {
        $module->install(new AssistedModule());
        $module->install(new ProviderSetModule());
        $module->install(new PramReaderModule());
        $hasMultiBindings = count($module->getContainer()->multiBindings);
        if ($hasMultiBindings) {
            // Apply MapModule if multiple bindings are present
            $module->override(new MapModule());
        }

        return $module;
    }
}
