<?php
/**
 * This file is part of the Ray package.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace Ray\Di;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Cache\Cache;
use PHPParser_PrettyPrinter_Default;
use Ray\Aop\Bind;
use Ray\Aop\Compiler;

final class DiCompiler implements InstanceInterface, \Serializable
{
    /**
     * @var array
     */
    private $classMap = [];

    /**
     * @var InjectorInterface
     */
    private $injector;

    /**
     * @var CompilationLoggerInterface
     */
    private $logger;

    /**
     * @var Cache
     */
    private $cache;

    /**
     * @var string
     */
    private $cacheKey;

    /**
     * @var array
     */
    private static $args;

    /**
     * @param InjectorInterface $injector
     * @param CompilationLogger     $logger
     */
    public function __construct(
        InjectorInterface $injector,
        CompilationLoggerInterface $logger,
        Cache $cache,
        $cacheKey
    ) {
        $logger->setConfig($injector->getContainer()->getForge()->getConfig());
        $injector->setLogger($logger);
        $this->injector = $injector;
        $this->logger = $logger;
        $this->cache = $cache;
        $this->cacheKey = $cacheKey;
    }

    /**
     * Return di compiler
     *
     * @param callable $moduleProvider
     * @param Cache    $cache
     * @param string   $cacheKey
     *
     * @return mixed|DiCompiler
     */
    public static function create(callable $moduleProvider, Cache $cache, $cacheKey, $tmpDir)
    {
        self::$args = func_get_args();
        if ($cache->contains($cacheKey)) {
            (new AopClassLoader)->register($tmpDir);
            $diCompiler = $cache->fetch($cacheKey);

            return $diCompiler;
        }
        $diCompiler = self::createInstance($moduleProvider, $cache, $cacheKey, $tmpDir);

        return $diCompiler;
    }

    /**
     * @param callable $moduleProvider
     * @param Cache    $cache
     * @param string   $cacheKey
     * @param string   $tmpDir
     *
     * @return DiCompiler
     */
    private static function createInstance($moduleProvider, Cache $cache, $cacheKey, $tmpDir)
    {
        $config = new Config(
            new Annotation(
                new Definition,
                new AnnotationReader
            )
        );
        $logger = new CompilationLogger(new Logger);
        $logger->setConfig($config);
        $injector = new ChildInjector(
            new Container(new Forge($config)),
            $moduleProvider(),
            new Bind,
            new Compiler(
                $tmpDir,
                new PHPParser_PrettyPrinter_Default
            ),
            $logger
        );

        $diCompiler = new DiCompiler($injector, $logger, $cache, $cacheKey);
        $injector->setChildInjector( $diCompiler );

        return $diCompiler;
    }

    /**
     * Compile fluent interface
     *
     * @param string $class
     * @return self
     */
    public function compile($class)
    {
        $this->injector->getInstance($class);
        $this->classMap = $this->logger->setClassMap($this->classMap, $class);
        $this->cache->save($this->cacheKey, $this);

        return $this;
    }

    /**
     * Get instance from container / injector
     *
     * @param string $class The class to instantiate.
     *
     * @return object
     */
    public function getInstance($class)
    {
        if (! isset($this->classMap[$class])) {
            return $this->recompile($class);
        }
        $hash = $this->classMap[$class];
        $instance = $this->logger->newInstance($hash);

        return $instance;
    }

    /**
     * @param $class
     *
     * @return object
     */
    private function recompile($class)
    {
        $this->cache->delete($this->cacheKey);
        $diCompiler = $this->injector ? $this : call_user_func_array([$this, 'createInstance'], self::$args);
        /** @var $diCompiler DiCompiler */
        $mappedClass = array_keys($this->classMap);
        $mappedClass[] = $class;
        foreach ($mappedClass as $newClass) {
            $diCompiler->compile($newClass);
        }

        return $diCompiler->getInstance($class);
    }

    /**
     * Destroy injector for runtime
     *
     * @return string
     */
    public function serialize()
    {
          $serialized = serialize(
              [
                $this->classMap,
                $this->logger,
                $this->cache,
                $this->cacheKey
              ]
          );

        return $serialized;
    }

    public function unserialize($serialized)
    {
        list(
            $this->classMap,
            $this->logger,
            $this->cache,
            $this->cacheKey
        ) = unserialize($serialized);
    }

    public function __toString()
    {
        return (string) $this->logger;
    }
}
