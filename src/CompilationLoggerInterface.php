<?php
/**
 * This file is part of the Ray package.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace Ray\Di;

use Aura\Di\ConfigInterface;

/**
 * Interface for di compile logger
 */
interface CompilationLoggerInterface extends LoggerInterface
{
    /**
     * @param ConfigInterface $config
     *
     * @return self
     */
    public function setConfig(ConfigInterface $config);

    /**
     * @param string $ref
     *
     * @return mixed
     * @throws \LogicException
     */
    public function newInstance($ref);

//    /**
//     * @param object $object
//     *
//     * @return string
//     */
//    public function getObjectHash($object);

    /**
     * @param string $class
     *
     * @return void
     */
    public function setMapRef($class);

    /**
     * @param string $class
     *
     * @return string
     */
    public function getMapRef($class);

    /**
     * @param string $class
     *
     * @return bool
     */
    public function isSetMapRef($class);

}
