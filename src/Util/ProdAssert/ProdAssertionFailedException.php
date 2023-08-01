<?php

declare(strict_types=1);

namespace MyFinances\Util\ProdAssert;

use RuntimeException;
use Throwable;

final class ProdAssertionFailedException extends RuntimeException
{
    public function __construct(string $message, ?Throwable $causedBy = null)
    {
        parent::__construct($message, /* code: */ 0, $causedBy);
    }
}
