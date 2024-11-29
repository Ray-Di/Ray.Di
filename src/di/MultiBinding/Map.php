<?php

declare(strict_types=1);

namespace Ray\Di\MultiBinding;

use ArrayAccess;
use Countable;
use Generator;
use Iterator;
use IteratorAggregate;
use LogicException;
use Ray\Di\InjectorInterface;
use ReturnTypeWillChange;

use function array_key_exists;
use function count;

/**
 * @template T
 * @implements ArrayAccess<array-key, T>
 * @implements IteratorAggregate<string, mixed>
 */
final class Map implements IteratorAggregate, ArrayAccess, Countable
{
    /** @var array<array-key, LazyInterface> $lazies */
    private $lazies;

    /** @var InjectorInterface */
    private $injector;

    /**
     * @param array<array-key, LazyInterface> $lazies
     */
    public function __construct(array $lazies, InjectorInterface $injector)
    {
        $this->lazies = $lazies;
        $this->injector = $injector;
    }

    /**
     * @param array-key $offset
     *
     * @codeCoverageIgnore
     */
    #[ReturnTypeWillChange]
    public function offsetExists($offset): bool
    {
        return array_key_exists($offset, $this->lazies);
    }

    /**
     * @param array-key $offset
     *
     * @return T
     *
     * @codeCoverageIgnore
     */
    #[ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        /** @var T $instance */
        $instance = ($this->lazies[$offset])($this->injector);

        return $instance;
    }

    /**
     * @param array-key $offset
     * @param mixed     $value
     *
     * @return never
     *
     * @codeCoverageIgnore
     */
    #[ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        unset($offset, $value);

        throw new LogicException();
    }

    /**
     * @param array-key $offset
     *
     * @return never
     *
     * @codeCoverageIgnore
     */
    #[ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        unset($offset);

        throw new LogicException();
    }

    /** @return Generator<array-key, T, void, void> */
    public function getIterator(): Iterator
    {
        foreach ($this->lazies as $key => $lazy) {
            /** @var T $object */
            $object = ($lazy)($this->injector);

            yield $key => $object;
        }
    }

    public function count(): int
    {
        return count($this->lazies);
    }
}
