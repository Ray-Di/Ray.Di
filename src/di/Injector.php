<?php

declare(strict_types=1);

namespace Ray\Di;

use Doctrine\Common\Annotations\AnnotationException;
use Ray\Aop\Compiler;
use Ray\Di\Exception\DirectoryNotWritable;
use Ray\Di\Exception\Untargeted;

use function assert;
use function file_exists;
use function is_dir;
use function is_writable;
use function spl_autoload_register;
use function sprintf;
use function str_replace;
use function sys_get_temp_dir;

class Injector implements InjectorInterface
{
    /** @var non-empty-string */
    private $classDir;

    /** @var Container */
    private $container;

    /**
     * @param AbstractModule|non-empty-array<AbstractModule>|null $module Module(s)
     * @param string                                              $tmpDir Temp directory for generated class
     */
    public function __construct($module = null, string $tmpDir = '')
    {
        /** @var non-empty-string $classDir */
        $classDir = is_dir($tmpDir) ? $tmpDir : sys_get_temp_dir();
        if (! is_writable($classDir)) {
            throw new DirectoryNotWritable($classDir); // @CodeCoverageIgnore
        }

        $this->classDir = $classDir;
        $this->container = (new ContainerFactory())($module, $this->classDir);
        // Bind injector (built-in bindings)
        (new Bind($this->container, InjectorInterface::class))->toInstance($this);
        $this->container->sort();
    }

    /**
     * Wakeup
     */
    public function __wakeup()
    {
        spl_autoload_register(
            function (string $class): void {
                $file = sprintf('%s/%s.php', $this->classDir, str_replace('\\', '_', $class));
                if (file_exists($file)) {
                    include $file; //@codeCoverageIgnore
                }
            }
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getInstance($interface, $name = Name::ANY)
    {
        try {
            /** @psalm-suppress MixedAssignment */
            $instance = $this->container->getInstance($interface, $name);
        } catch (Untargeted $e) {
            /** @psalm-var class-string $interface */
            $this->bind($interface);
            /** @psalm-suppress MixedAssignment */
            $instance = $this->getInstance($interface, $name);
        }

        /** @psalm-suppress MixedReturnStatement */
        return $instance;
    }

    /**
     * @param class-string $class
     *
     * @throws AnnotationException
     */
    private function bind(string $class): void
    {
        new Bind($this->container, $class);
        $bound = $this->container->getContainer()[$class . '-' . Name::ANY];
        assert($bound instanceof Dependency);
        $this->container->weaveAspect(new Compiler($this->classDir), $bound)->getInstance($class);
    }
}
