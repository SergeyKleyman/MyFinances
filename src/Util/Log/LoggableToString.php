<?php

declare(strict_types=1);

namespace MyFinances\Util\Log;

use MyFinances\Util\StaticClassTrait;

final class LoggableToString
{
    use StaticClassTrait;

    public static function convert(mixed $value, bool $prettyPrint = false): string
    {
        $logStream = new StreamDefaultImpl($prettyPrint);
        $logStream->write($value);
        return $logStream->writtenContentEncodedAsJson;
    }
}
