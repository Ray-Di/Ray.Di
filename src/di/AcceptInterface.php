<?php

declare(strict_types=1);

namespace Ray\Di;

/**
 * This interface defines the accept method, which allows an object to accept a visitor.
 */
interface AcceptInterface
{
    /**
     * Accepts a visitor and applies its behavior on the current object.
     *
     * @param VisitorInterface $visitor The visitor to accept
     *
     * @return mixed|void
     */
    public function accept(VisitorInterface $visitor);
}
