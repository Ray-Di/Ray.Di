<?php

declare(strict_types=1);

namespace Ray\Di\MultiBinding;

use Ray\Di\InjectorInterface;

interface LazyInterface
{
    /**
     * @return mixed
     */
    public function __invoke(InjectorInterface $injector);
}
