<?php

declare(strict_types=1);

namespace MyFinances\Util;

final class OsUtil
{
    use StaticClassTrait;

    public static function isWindows(): bool
    {
        return strnatcasecmp(PHP_OS_FAMILY, 'Windows') === 0;
    }
}
