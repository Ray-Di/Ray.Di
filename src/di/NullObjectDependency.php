<?php

declare(strict_types=1);

namespace Ray\Di;

use Koriym\NullObject\NullObject;
use ReflectionClass;

use function assert;
use function is_dir;

/**
 * @codeCoverageIgnore
 */
final class NullObjectDependency implements DependencyInterface
{
    /** @var class-string */
    private $interface;

    /**
     * @param class-string $interface
     */
    public function __construct(string $interface)
    {
        $this->interface = $interface;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function inject(Container $container)
    {
        return null;
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function register(array &$container, Bind $bind)
    {
        $container[(string) $bind] = $bind->getBound();
    }

    /**
     * {@inheritdoc}
     */
    public function setScope($scope)
    {
    }

    public function toNull(string $scriptDir): Dependency
    {
        assert(is_dir($scriptDir));
        $nullObject = new NullObject();
        $class = $nullObject->save($this->interface, $scriptDir);

        return new Dependency(new NewInstance(new ReflectionClass($class), new SetterMethods([])));
    }

    public function accept(VisitorInterface $visitor)
    {
    }
}
