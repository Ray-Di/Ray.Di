<?php

declare(strict_types=1);

namespace Ray\Di\Di;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

/**
 * Annotates your class methods into which the Injector should inject values
 *
 * @Annotation
 * @Target("METHOD")
 * @NamedArgumentConstructor
 * @psalm-immutable
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_PARAMETER)]
final class Inject implements InjectInterface
{
    /**
     * If true, and the appropriate binding is not found, the Injector will skip injection of this method or field rather than produce an error.
     *
     * @var bool
     */
    public $optional = false;

    /**
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag) // @phpstan-ignore-line
     */
    public function __construct(bool $optional = false)
    {
        $this->optional = $optional;
    }

    /**
     * {@inheritdoc}
     */
    public function isOptional(): bool
    {
        return $this->optional;
    }
}
