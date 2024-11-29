<?php

declare(strict_types=1);

namespace Ray\Di\MultiBinding;

use Ray\Di\InjectorInterface;

/**
 * @template T of mixed
 * @psalm-immutable
 */
final class LazyInstance implements LazyInteterface
{
    /** @var T */
    private $instance;

    /**
     * @param T $class
     */
    public function __construct($class)
    {
        $this->instance = $class;
    }

    /**
     * @return T
     */
    public function __invoke(InjectorInterface $injector)
    {
        unset($injector);

        return $this->instance;
    }
}
