<?php

declare(strict_types=1);

namespace Ray\Di;

use Koriym\ParamReader\ParamReader;
use Koriym\ParamReader\ParamReaderInterface;

class PramReaderModule extends AbstractModule
{
    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->bind(ParamReaderInterface::class)->to(ParamReader::class);
    }
}
