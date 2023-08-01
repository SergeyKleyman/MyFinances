<?php

declare(strict_types=1);

namespace MyFinances\Util\Log;

use RuntimeException;
use Throwable;

final class SubsystemException extends RuntimeException
{
    public function __construct(string $message, ?Throwable $causedBy = null, int $code = 0)
    {
        parent::__construct($message, $code, $causedBy);
    }
}
