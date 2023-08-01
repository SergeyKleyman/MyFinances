<?php

declare(strict_types=1);

namespace MyFinances\Util\Log;

use MyFinances\Util\ClassNameUtil;
use MyFinances\Util\StackTraceUtil;

final class LogStackTraceUtil
{
    public const STACK_TRACE_KEY = 'stacktrace';

    public const MAX_NUMBER_OF_STACK_FRAMES = 100;

    /**
     * @param ?positive-int $maxNumberOfStackFrames
     *
     * @return \MyFinances\Util\StackTraceFrame[]
     *
     * @phpstan-param 0|positive-int $numberOfStackFramesToSkip
     *
     * @noinspection  PhpFullyQualifiedNameUsageInspection
     */
    public static function buildForCurrent(int $numberOfStackFramesToSkip, ?int $maxNumberOfStackFrames = self::MAX_NUMBER_OF_STACK_FRAMES): array
    {
        /**
         * @param array<array-key, mixed> $resultFrame
         */
        $setIfNotNull = static function (string $key, mixed $value, array &$resultFrame): void {
            if ($value !== null) {
                $resultFrame[$key] = $value;
            }
        };

        $classicFormatFrames = StackTraceUtil::capture(/* offset */ $numberOfStackFramesToSkip + 1, $maxNumberOfStackFrames);
        $result = [];

        foreach ($classicFormatFrames as $classicFormatFrame) {
            $resultFrame = [];
            $adaptFilePath = self::adaptSourceCodeFilePath($classicFormatFrame->file);
            $setIfNotNull(StackTraceUtil::FILE_KEY, $adaptFilePath, $resultFrame);
            $setIfNotNull(StackTraceUtil::LINE_KEY, $classicFormatFrame->line, $resultFrame);
            if ($classicFormatFrame->class !== null) {
                $classShortName = ClassNameUtil::fqToShort($classicFormatFrame->class);
                $resultFrame[StackTraceUtil::CLASS_KEY] = $classShortName;
            }
            $setIfNotNull(StackTraceUtil::FUNCTION_KEY, $classicFormatFrame->function, $resultFrame);
            $result[] = $resultFrame;
        }
        return $result;
    }

    public static function adaptSourceCodeFilePath(?string $srcFile): ?string
    {
        return $srcFile === null ? null : basename($srcFile);
    }
}
