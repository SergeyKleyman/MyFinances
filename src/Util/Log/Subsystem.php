<?php

declare(strict_types=1);

namespace MyFinances\Util\Log;

use MyFinances\Util\ExceptionUtil;
use MyFinances\Util\StaticClassTrait;
use Throwable;

final class Subsystem
{
    use StaticClassTrait;

    public static bool $isInTestingContext = false;

    private static bool $wereThereAnyInternalFailures = false;

    /**
     * @param array<string, mixed> $context
     *
     * @return string
     */
    public static function onInternalFailure(string $message, array $context, Throwable $causedBy): string
    {
        self::$wereThereAnyInternalFailures = true;
        if (self::$isInTestingContext) {
            throw new SubsystemException(ExceptionUtil::buildMessage($message, $context), $causedBy);
        }

        return $message . '. ' . LoggableToString::convert($context + ['causedBy' => $causedBy]);
    }

    public static function wereThereAnyInternalFailures(): bool
    {
        return self::$wereThereAnyInternalFailures;
    }
}
