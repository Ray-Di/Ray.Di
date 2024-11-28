<?php

declare(strict_types=1);

namespace Ray\Di;

use Doctrine\Common\Annotations\AnnotationException;
use Ray\Aop\Compiler;

use function file_exists;
use function spl_autoload_register;
use function sprintf;
use function str_replace;

final class Grapher
{
    /** @var non-empty-string */
    private $classDir;

    /** @var Container */
    private $container;

    /**
     * @param AbstractModule   $module   Binding module
     * @param non-empty-string $classDir Class directory
     *
     * @throws AnnotationException
     */
    public function __construct(AbstractModule $module, string $classDir)
    {
        $module->install(new AssistedModule());
        $this->container = $module->getContainer();
        $this->classDir = $classDir;
        $this->container->weaveAspects(new Compiler($this->classDir));

        // builtin injection
        (new Bind($this->container, InjectorInterface::class))->toInstance(new Injector($module));
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
     * Build an object graph with give constructor parameters
     *
     * @param string            $class  class name
     * @param array<int, mixed> $params construct parameters
     *
     * @return mixed
     */
    public function newInstanceArgs(string $class, array $params)
    {
        return $this->container->getInstanceWithArgs($class, $params);
    }
}
