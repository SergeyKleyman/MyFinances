<?php

declare(strict_types=1);

namespace MyFinances\Util\Log;

trait LoggableEnumTrait
{
    public function toLog(LogStreamInterface $stream): void
    {
        $stream->write($this->name);
    }
}
