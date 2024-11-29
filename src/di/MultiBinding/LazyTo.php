<?php

declare(strict_types=1);

namespace Ray\Di\MultiBinding;

use Ray\Di\InjectorInterface;

/**
 * @template T of object
 */
final class LazyTo implements LazyInterface
{
    /** @var class-string<T> */
    private $class;

    /**
     * @param class-string<T> $class
     */
    public function __construct(string $class)
    {
        $this->class = $class;
    }

    /**
     * @return T
     */
    public function __invoke(InjectorInterface $injector)
    {
        return $injector->getInstance($this->class);
    }
}
