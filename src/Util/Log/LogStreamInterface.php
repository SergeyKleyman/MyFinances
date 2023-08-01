<?php

declare(strict_types=1);

namespace MyFinances\Util\Log;

interface LogStreamInterface
{
    public function canWrite(): bool;

    public function write(mixed $value): void;
}
