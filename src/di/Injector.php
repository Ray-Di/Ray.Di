<?php

declare(strict_types=1);

namespace Ray\Di;

use Doctrine\Common\Annotations\AnnotationException;
use Ray\Aop\Compiler;
use Ray\Di\Exception\Unbound;
use Ray\Di\Exception\Untargeted;

use function assert;
use function file_exists;
use function file_put_contents;
use function is_dir;
use function spl_autoload_register;
use function sprintf;
use function str_replace;
use function sys_get_temp_dir;

class Injector implements InjectorInterface
{
    /** @var string */
    private $classDir;

    /** @var Container */
    private $container;

    /**
     * @param AbstractModule|non-empty-array<AbstractModule>|null $module Module(s)
     * @param string                                              $tmpDir Temp directory for generated class
     */
    public function __construct($module = null, string $tmpDir = '')
    {
        $this->classDir = is_dir($tmpDir) ? $tmpDir : sys_get_temp_dir();
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
        } catch (Unbound $e) {
            $bindings =  (new ModuleString())($this->container, $this->container->getPointcuts());
            $logFile = sprintf('%s/module.log', $this->classDir);
            file_put_contents($logFile, $bindings);
            $message = sprintf("%s\nSee the binding log: %s", $e->getMessage(), $logFile);

            throw new Unbound($message);
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
