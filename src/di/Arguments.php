<?php

declare(strict_types=1);

namespace Ray\Di;

use Ray\Di\Exception\Unbound;
use ReflectionMethod;

final class Arguments implements AcceptInterface
{
    /** @var Argument[] */
    private $arguments = [];

    public function __construct(ReflectionMethod $method, Name $name)
    {
        $parameters = $method->getParameters();
        foreach ($parameters as $parameter) {
            $this->arguments[] = new Argument($parameter, $name($parameter));
        }
    }

    /**
     * Return arguments
     *
     * @return array<int, mixed>
     *
     * @throws Exception\Unbound
     */
    public function inject(Container $container): array
    {
        $parameters = [];
        foreach ($this->arguments as $parameter) {
            /** @psalm-suppress MixedAssignment */
            $parameters[] = $this->getParameter($container, $parameter);
        }

        return $parameters;
    }

    public function accept(VisitorInterface $visitor)
    {
        $visitor->visitArguments($this->arguments);
    }

    /**
     * @return mixed
     *
     * @throws Unbound
     */
    private function getParameter(Container $container, Argument $argument)
    {
        $this->bindInjectionPoint($container, $argument);
        try {
            return $container->getDependency((string) $argument);
        } catch (Unbound $e) {
            if ($argument->isDefaultAvailable()) {
                return $argument->getDefaultValue();
            }

            throw new Unbound($argument->getMeta(), 0, $e);
        }
    }

    private function bindInjectionPoint(Container $container, Argument $argument): void
    {
        $isSelf = (string) $argument === InjectionPointInterface::class . '-' . Name::ANY;
        if ($isSelf) {
            return;
        }

        (new Bind($container, InjectionPointInterface::class))->toInstance(new InjectionPoint($argument->get()));
    }
}
