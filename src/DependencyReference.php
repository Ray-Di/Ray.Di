<?php
/**
 * This file is part of the Ray package.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace Ray\Di;

final class DependencyReference implements ProviderInterface
{
    /**
     * @var CompilationLogger
     */
    private $logger;

    /**
     * @var string
     */
    private $refId;

    /**
     * @var object
     */
    private $instance;

    /**
     * @param string            $refId
     * @param CompilationLogger $logger
     */
    public function __construct($refId, CompilationLogger $logger)
    {
        $this->refId = $refId;
        $this->logger = $logger;
    }

    public function get()
    {
        if (is_null($this->instance)) {
            $this->instance = $this->logger->newInstance($this->refId);
        }

        return $this->instance;
    }
}
