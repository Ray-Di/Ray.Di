<?php

declare(strict_types=1);

namespace Ray\Di;

use Exception;
use LogicException;
use Ray\Di\Exception\Unbound;
use ReflectionMethod;

use function call_user_func_array;
use function is_callable;

final class SetterMethod implements AcceptInterface
{
    /** @var string */
    private $method;

    /** @var Arguments */
    private $arguments;

    /**
     * Is optional binding ?
     *
     * @var bool
     */
    private $isOptional = false;

    public function __construct(ReflectionMethod $method, Name $name)
    {
        $this->method = $method->name;
        $this->arguments = new Arguments($method, $name);
    }

    /**
     * @param object $instance
     *
     * @throws Unbound
     * @throws Exception
     */
    public function __invoke($instance, Container $container): void
    {
        try {
            $parameters = $this->arguments->inject($container);
        } catch (Unbound $e) {
            if ($this->isOptional) {
                return;
            }

            throw $e;
        }

        $callable = [$instance, $this->method];
        if (! is_callable($callable)) {
            throw new LogicException(); // @codeCoverageIgnore
        }

        call_user_func_array($callable, $parameters);
    }

    public function setOptional(): void
    {
        $this->isOptional = true;
    }

    /** @inheritDoc */
    public function accept(VisitorInterface $visitor)
    {
        try {
            $visitor->visitSetterMethod($this->method, $this->arguments);
        } catch (Unbound $e) {
            if ($this->isOptional) {
                // Return when no dependency given and @Inject(optional=true) annotated to setter method.
                return;
            }

            // Throw exception when no dependency given and @Inject(optional=false) annotated to setter method.
            throw $e;
        }
    }
}
