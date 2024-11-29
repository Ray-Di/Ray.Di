<?php

declare(strict_types=1);

namespace Ray\Di\MultiBinding;

use ArrayObject;

use function array_merge_recursive;

/**
 * @extends ArrayObject<string, non-empty-array<array-key, LazyInterface>>
 */
final class MultiBindings extends ArrayObject
{
    public function merge(self $multiBindings): void
    {
        $this->exchangeArray(
            array_merge_recursive($this->getArrayCopy(), $multiBindings->getArrayCopy())
        );
    }
}
