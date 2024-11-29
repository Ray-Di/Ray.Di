<?php

declare(strict_types=1);

namespace Ray\Di;

use Ray\Di\Di\Named;
use Ray\Di\Di\Qualifier;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionParameter;

use function assert;
use function class_exists;
use function explode;
use function get_class;
use function is_string;
use function preg_match;
use function substr;
use function trim;

final class Name
{
    /**
     * 'Unnamed' name
     */
    public const ANY = '';

    /** @var string */
    private $name = '';

    /**
     * Named database
     *
     * format: array<varName, NamedName>
     *
     * @var array<string, string>
     */
    private $names;

    /**
     * @param string|array<string, string>|null $name
     */
    public function __construct($name = null)
    {
        if ($name === null) {
            return;
        }

        if (is_string($name)) {
            $this->setName($name);

            return;
        }

        $this->names = $name;
    }

    /**
     * Create instance from PHP8 attributes
     *
     * psalm does not know ReflectionAttribute?? PHPStan produces no type error here.
     */
    public static function withAttributes(ReflectionMethod $method): ?self
    {
        $params = $method->getParameters();
        $names = [];
        foreach ($params as $param) {
            /** @var array<ReflectionAttribute> $attributes */
            $attributes = $param->getAttributes();
            if ($attributes) {
                $names[$param->name] = self::getName($attributes);
            }
        }

        if ($names) {
            return new self($names);
        }

        return null;
    }

    /**
     * @param non-empty-array<ReflectionAttribute> $attributes
     *
     * @throws ReflectionException
     *
     * @psalm-suppress MixedAssignment
     * @psalm-suppress MixedArgument
     */
    private static function getName(array $attributes): string
    {
        $refAttribute = $attributes[0];
        $attribute = $refAttribute->newInstance();
        if ($attribute instanceof Named) {
            return $attribute->value;
        }

        $isQualifier = (bool) (new ReflectionClass($attribute))->getAttributes(Qualifier::class);
        if ($isQualifier) {
            return get_class($attribute);
        }

        return '';
    }

    public function __invoke(ReflectionParameter $parameter): string
    {
        // single variable named binding
        if ($this->name) {
            return $this->name;
        }

        // multiple variable named binding
        return $this->names[$parameter->name] ?? $this->names[self::ANY] ?? self::ANY;
    }

    private function setName(string $name): void
    {
        // annotation
        if (class_exists($name, false)) {
            $this->name = $name;

            return;
        }

        // single name
        // @Named(name)
        if ($name === self::ANY || preg_match('/^\w+$/', $name)) {
            $this->name = $name;

            return;
        }

        // name list
        // @Named(varName1=name1, varName2=name2)]
        $this->names = $this->parseName($name);
    }

    /**
     * @return array<string, string>
     */
    private function parseName(string $name): array
    {
        $names = [];
        $keyValues = explode(',', $name);
        foreach ($keyValues as $keyValue) {
            $exploded = explode('=', $keyValue);
            if (isset($exploded[1])) {
                [$key, $value] = $exploded;
                if (isset($key[0]) && $key[0] === '$') {
                    assert(is_string($key)); // @phpstan-ignore-line
                    $key = substr($key, 1);
                }

                /** @psalm-suppress MixedArgument */
                $names[trim($key)] = trim($value);
            }
        }

        return $names;
    }
}
