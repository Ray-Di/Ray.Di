<?php

declare(strict_types=1);

namespace Ray\Di;

use function get_class;
use function gettype;
use function is_object;
use function is_scalar;
use function sprintf;

final class Instance implements DependencyInterface, AcceptInterface
{
    /** @var mixed */
    public $value;

    /**
     * @param mixed $value
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    public function __toString(): string
    {
        if (is_scalar($this->value)) {
            return sprintf(
                '(%s) %s',
                gettype($this->value),
                (string) $this->value
            );
        }

        if (is_object($this->value)) {
            return '(object) ' . get_class($this->value);
        }

        return '(' . gettype($this->value) . ')';
    }

    /**
     * {@inheritdoc}
     */
    public function register(array &$container, Bind $bind): void
    {
        $index = (string) $bind;
        $container[$index] = $bind->getBound();
    }

    /**
     * {@inheritdoc}
     */
    public function inject(Container $container)
    {
        return $this->value;
    }

    /**
     * {@inheritdoc}
     *
     * @codeCoverageIgnore
     */
    public function setScope($scope): void
    {
    }

    /** @inheritDoc */
    public function accept(VisitorInterface $visitor)
    {
        $visitor->visitInstance($this->value);
    }
}
