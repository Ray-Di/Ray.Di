<?php

declare(strict_types=1);

namespace Ray\Di;

interface DependencyInterface
{
    /**
     * @return string
     */
    public function __toString();

    /**
     * Inject dependencies into dependent objects
     *
     * @return mixed
     */
    public function inject(Container $container);

    /**
     * Register dependency to container
     *
     * @param DependencyInterface[] $container
     *
     * @return void
     */
    public function register(array &$container, Bind $bind);

    /**
     * Set scope
     *
     * @param string $scope
     *
     * @return void
     */
    public function setScope($scope);

    /**
     * Accept a visitor for this object
     *
     * @param VisitorInterface $visitor The visitor instance that will be accepted by the method.
     */
    public function accept(VisitorInterface $visitor);
}
