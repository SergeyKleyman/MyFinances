<?php

declare(strict_types=1);

namespace MyFinances\Util;

use MyFinances\Util\Log\LoggableToString;
use MyFinances\Util\Log\LogStackTraceUtil;

final class ExceptionUtil
{
    use StaticClassTrait;

    /**
     * @param array<array-key, mixed> $context
     *
     * @phpstan-param null|0|positive-int $numberOfStackFramesToSkip
     * @noinspection PhpVarTagWithoutVariableNameInspection
     */
    public static function buildMessage(string $messagePrefix, array $context = [], ?int $numberOfStackFramesToSkip = null): string
    {
        if ($numberOfStackFramesToSkip !== null) {
            $context = array_merge([LogStackTraceUtil::STACK_TRACE_KEY => LogStackTraceUtil::buildForCurrent($numberOfStackFramesToSkip + 1)], $context);
        }
        $messageSuffix = LoggableToString::convert($context);
        return $messagePrefix . (TextUtil::isEmptyString($messageSuffix) ? '' : ('. ' . $messageSuffix));
    }
}
