<?php

declare(strict_types=1);

namespace MyFinances\Util\Log;

use MyFinances\Util\JsonUtil;

final class StreamDefaultImpl implements LogStreamInterface
{
    public string $writtenContentEncodedAsJson;

    public function __construct(private readonly bool $prettyPrint = false)
    {
    }

    public function canWrite(): bool
    {
        return true;
    }

    public function write(mixed $value): void
    {
        $this->writtenContentEncodedAsJson = JsonUtil::encode($value, $this->prettyPrint);
    }
}
