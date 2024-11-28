<?php

declare(strict_types=1);

namespace Ray\Di\Exception;

use RuntimeException;

class DirectoryNotWritable extends RuntimeException implements ExceptionInterface
{
}
