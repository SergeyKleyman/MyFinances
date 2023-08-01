<?php

declare(strict_types=1);

namespace MyFinances\Util;

final class DbgUtil
{
    use StaticClassTrait;

    /**
     * @phpstan-param 0|positive-int $numberOfStackFramesToSkip
     */
    public static function getCallerInfoFromStacktrace(int $numberOfStackFramesToSkip): CallerInfo
    {
        $stackFrames = StackTraceUtil::capture(/* offset */ $numberOfStackFramesToSkip + 1, /* maxNumberOfFrames */ 1);

        if (ArrayUtil::isEmpty($stackFrames)) {
            return new CallerInfo(null, null, null, null);
        }

        $stackFrame = $stackFrames[0];
        return new CallerInfo($stackFrame->file, $stackFrame->line, $stackFrame->class, $stackFrame->function);
    }
}
