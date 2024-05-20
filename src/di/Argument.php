<?php

declare(strict_types=1);

namespace Ray\Di;

use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use Serializable;

use function assert;
use function in_array;
use function serialize;
use function sprintf;
use function unserialize;

final class Argument implements Serializable
{
    public const UNBOUND_TYPE = ['bool', 'int', 'float', 'string', 'array', 'resource', 'callable', 'iterable'];

    /** @var string */
    private $index;

    /** @var bool */
    private $isDefaultAvailable;

    /** @var mixed */
    private $default;

    /** @var string */
    private $meta;

    /** @var ReflectionParameter */
    private $reflection;

    public function __construct(ReflectionParameter $parameter, string $name)
    {
        $type = $this->getType($parameter);
        $isOptional = $parameter->isOptional();
        $this->isDefaultAvailable = $parameter->isDefaultValueAvailable() || $isOptional;
        if ($isOptional) {
            $this->default = null;
        }

        $this->setDefaultValue($parameter);
        $this->index = $type . '-' . $name;
        $this->reflection = $parameter;
        $this->meta = sprintf(
            "dependency '%s' with name '%s' used in %s:%d ($%s)",
            $type,
            $name,
            $this->reflection->getDeclaringFunction()->getFileName(),
            $this->reflection->getDeclaringFunction()->getStartLine(),
            $parameter->getName()
        );
    }

    public function __toString(): string
    {
        return $this->index;
    }

    /**
     * Return reflection
     */
    public function get(): ReflectionParameter
    {
        return $this->reflection;
    }

    public function isDefaultAvailable(): bool
    {
        return $this->isDefaultAvailable;
    }

    /**
     * @return mixed
     */
    public function getDefaultValue()
    {
        return $this->default;
    }

    public function getMeta(): string
    {
        return $this->meta;
    }

    /**
     * {@inheritDoc}
     */
    public function serialize(): ?string
    {
        return serialize($this->__serialize());
    }

    /**
     * {@inheritDoc}
     *
     * @psalm-param string $data
     */
    public function unserialize($data): void
    {
        /** @var array{0: string, 1: bool, 2: string, 3: string, 4: string, 5: array{0: string, 1: string, 2:string}} $array */
        $array = unserialize($data, ['allowed_classes' => false]);
        $this->__unserialize($array);
    }

    /**
     * @return array<mixed>
     */
    public function __serialize(): array
    {
        $method = $this->reflection->getDeclaringFunction();
        assert($method instanceof ReflectionMethod);
        $ref = [
            $method->class,
            $method->name,
            $this->reflection->getName(),
        ];

        return [
            $this->index,
            $this->isDefaultAvailable,
            $this->default,
            $this->meta,
            $ref,
        ];
    }

    /**
     * @param array{0: string, 1: bool, 2: string, 3: string, 4: string, 5: array{0: string, 1: string, 2:string}} $unserialized
     */
    public function __unserialize(array $unserialized): void
    {
        [
            $this->index,
            $this->isDefaultAvailable,
            $this->default,
            $this->meta,
            $ref,
        ] = $unserialized;
        $this->reflection = new ReflectionParameter([$ref[0], $ref[1]], $ref[2]);
    }

    public function accept(VisitorInterface $visitor)
    {
        $visitor->visitArgument($this->index, $this->isDefaultAvailable, $this->default);
    }

    private function setDefaultValue(ReflectionParameter $parameter): void
    {
        if (! $this->isDefaultAvailable) {
            return;
        }

        try {
            $this->default = $parameter->getDefaultValue();
            // @codeCoverageIgnoreStart
        } catch (ReflectionException $e) {
            $this->default = null;
            // @codeCoverageIgnoreEnd
        }
    }

    private function getType(ReflectionParameter $parameter): string
    {
        $type = $parameter->getType();

        return $type instanceof ReflectionNamedType && ! in_array($type->getName(), self::UNBOUND_TYPE, true) ? $type->getName() : '';
    }
}
