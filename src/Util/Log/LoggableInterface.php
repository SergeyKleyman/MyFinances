<?php

declare(strict_types=1);

namespace MyFinances\Util\Log;

interface LoggableInterface
{
    public function toLog(LogStreamInterface $stream): void;
}
