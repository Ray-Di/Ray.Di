<?php

declare(strict_types=1);

namespace Ray\Di;

use Ray\Di\Di\Set;

/**
 * @implements ProviderInterface<mixed>
 * @template T of object
 */
final class ProviderProvider implements ProviderInterface
{
    /** @var InjectorInterface  */
    private $injector;

    /** @var Set<T> */
    private $set;

    /** @param Set<T> $set */
    public function __construct(InjectorInterface $injector, Set $set)
    {
        $this->injector = $injector;
        $this->set = $set;
    }

    /** @return mixed */
    public function get()
    {
        return $this->injector->getInstance($this->set->interface, $this->set->name);
    }
}
