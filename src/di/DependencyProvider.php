<?php

declare(strict_types=1);

namespace Ray\Di;

use Ray\Compiler\CompileVisitor;

use function assert;
use function sprintf;

final class DependencyProvider implements DependencyInterface
{
    /** @var string */
    public $context;

    /**
     * Provider dependency
     *
     * @var Dependency
     */
    private $dependency;

    /** @var bool */
    private $isSingleton = false;

    /** @var ?mixed */
    private $instance;

    public function __construct(Dependency $dependency, string $context)
    {
        $this->dependency = $dependency;
        $this->context = $context;
    }

    /**
     * @return list<string>
     */
    public function __sleep()
    {
        return ['context', 'dependency', 'isSingleton'];
    }

    public function __toString(): string
    {
        return sprintf(
            '(provider) %s',
            (string) $this->dependency
        );
    }

    /**
     * {@inheritdoc}
     */
    public function register(array &$container, Bind $bind): void
    {
        $container[(string) $bind] = $bind->getBound();
    }

    /**
     * {@inheritdoc}
     */
    public function inject(Container $container)
    {
        if ($this->isSingleton && $this->instance) {
            return $this->instance;
        }

        $provider = $this->dependency->inject($container);
        assert($provider instanceof ProviderInterface);
        if ($provider instanceof SetContextInterface) {
            $this->setContext($provider);
        }

        $this->instance = $provider->get();

        return $this->instance;
    }

    /**
     * {@inheritdoc}
     */
    public function setScope($scope): void
    {
        if ($scope === Scope::SINGLETON) {
            $this->isSingleton = true;
        }
    }

    public function setContext(SetContextInterface $provider): void
    {
        $provider->setContext($this->context);
    }

    public function isSingleton(): bool
    {
        return $this->isSingleton;
    }

    public function accept(VisitorInterface $visitor): string
    {
        assert($visitor instanceof CompileVisitor);

        return $visitor->visitProvider(
            $this->dependency,
            $this->context,
            $this->isSingleton
        );
    }
}
