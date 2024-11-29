<?php

declare(strict_types=1);

namespace Ray\Di\MultiBinding;

use Ray\Di\InjectorInterface;
use Ray\Di\ProviderInterface;

use function assert;

/**
 * @template T of ProviderInterface
 */
final class LazyProvider implements LazyInteterface
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
     * @return mixed
     */
    public function __invoke(InjectorInterface $injector)
    {
        $provider = $injector->getInstance($this->class);

        return $provider->get();
    }
}
