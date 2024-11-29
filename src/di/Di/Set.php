<?php

declare(strict_types=1);

namespace Ray\Di\Di;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

/**
 * @Annotation
 * @Target({"METHOD","PROPERTY"})
 * @NamedArgumentConstructor()
 * @template T of object
 * @psalm-immutable
 */
#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
final class Set
{
    /** @var ''|class-string<T> */
    public $interface;

    /** @var string */
    public $name;

    /**
     * @param ''|class-string<T> $interface
     */
    public function __construct(string $interface, string $name = '')
    {
        $this->interface = $interface;
        $this->name = $name;
    }
}
