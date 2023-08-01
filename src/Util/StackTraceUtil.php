<?php

declare(strict_types=1);

namespace MyFinances\Util;

use MyFinances\Util\Log\Logger;

use function count;

use const DEBUG_BACKTRACE_IGNORE_ARGS;
use const DEBUG_BACKTRACE_PROVIDE_OBJECT;

final class StackTraceUtil
{
    use StaticClassTrait;

    public const FILE_KEY = 'file';
    public const LINE_KEY = 'line';
    public const FUNCTION_KEY = 'function';
    public const CLASS_KEY = 'class';
    public const TYPE_KEY = 'type';
    public const FUNCTION_IS_STATIC_METHOD_TYPE_VALUE = '::';
    public const FUNCTION_IS_METHOD_TYPE_VALUE = '->';
    public const THIS_OBJECT_KEY = 'object';
    public const ARGS_KEY = 'args';

    /**
     * @param ?positive-int $maxNumberOfFrames
     *
     * @return StackTraceFrame[]
     *
     * @phpstan-param 0|positive-int $offset
     */
    public static function capture(int $offset = 0, ?int $maxNumberOfFrames = null, bool $includeArgs = false, bool $includeThisObj = false): array
    {
        $options = ($includeArgs ? 0 : DEBUG_BACKTRACE_IGNORE_ARGS) | ($includeThisObj ? DEBUG_BACKTRACE_PROVIDE_OBJECT : 0);
        // If there is non-null $maxNumberOfFrames we need to capture one more frame in PHP format
        $phpFormatFrames = debug_backtrace($options, /* limit */ $maxNumberOfFrames === null ? 0 : ($offset + $maxNumberOfFrames + 1));
        $phpFormatFrames = IterableUtil::arraySuffix($phpFormatFrames, $offset);

        /** @var StackTraceFrame[] $outputFrames */
        $outputFrames = [];
        $isTopFrame = true;
        /** @var ?array<string, mixed> $bufferedBeforeTopFrame */
        $bufferedBeforeTopFrame = null;
        /** @var ?array<string, mixed> $prevFrame */
        $prevFrame = null;
        $hasExitedLoopEarly = false;
        foreach ($phpFormatFrames as $currentFrame) {
            if ($prevFrame === null) {
                $prevFrame = $currentFrame;
                continue;
            }
            if (!self::captureConsume($maxNumberOfFrames, $includeArgs, $includeThisObj, $prevFrame, $currentFrame, $bufferedBeforeTopFrame, $isTopFrame, $outputFrames)) {
                $hasExitedLoopEarly = true;
                break;
            }
            $prevFrame = $currentFrame;
        }

        if (!$hasExitedLoopEarly && $prevFrame !== null) {
            self::captureConsume($maxNumberOfFrames, $includeArgs, $includeThisObj, $prevFrame, /* nextInputFrame */ null, $bufferedBeforeTopFrame, $isTopFrame, $outputFrames);
        }

        return $outputFrames;
    }

    /**
     * @param array<string, mixed> $frame
     *
     * @return ?bool
     */
    private static function isStaticMethodInPhpFormat(array $frame): ?bool
    {
        static $logger = new Logger(srcCodeNamespace: __NAMESPACE__, srcCodeClass: __CLASS__, srcCodeFunc: __FUNCTION__, srcCodeFile: __FILE__);

        if (($funcType = self::getNullableStringValue(StackTraceUtil::TYPE_KEY, $frame)) === null) {
            return null;
        }

        switch ($funcType) {
            case StackTraceUtil::FUNCTION_IS_STATIC_METHOD_TYPE_VALUE:
                return true;
            case StackTraceUtil::FUNCTION_IS_METHOD_TYPE_VALUE:
                return false;
            default:
                $logger->error()?->log(__LINE__, 'Unexpected `' . StackTraceUtil::TYPE_KEY . '\' value', ['type' => $funcType]);
                return null;
        }
    }

    /**
     * @param array<string, mixed> $phpFormatFormatFrame
     */
    private static function getNullableStringValue(string $key, array $phpFormatFormatFrame): ?string
    {
        /** @var ?string $value */
        $value = self::getNullableValue($key, 'is_string', 'string', $phpFormatFormatFrame);
        return $value;
    }

    /**
     * @param array<string, mixed> $phpFormatFormatFrame
     */
    private static function getNullableIntValue(string $key, array $phpFormatFormatFrame): ?int
    {
        /** @var ?int $value */
        $value = self::getNullableValue($key, 'is_int', 'int', $phpFormatFormatFrame);
        return $value;
    }

    /**
     * @param array<string, mixed> $phpFormatFormatFrame
     */
    private static function getNullableObjectValue(string $key, array $phpFormatFormatFrame): ?object
    {
        /** @var ?object $value */
        $value = self::getNullableValue($key, 'is_object', 'object', $phpFormatFormatFrame);
        return $value;
    }

    /**
     * @param array<string, mixed> $phpFormatFormatFrame
     *
     * @return null|mixed[]
     */
    private static function getNullableArrayValue(string $key, array $phpFormatFormatFrame): ?array
    {
        /** @var ?array<mixed> $value */
        $value = self::getNullableValue($key, 'is_array', 'array', $phpFormatFormatFrame);
        return $value;
    }

    /**
     * @param callable(mixed): bool $isValueTypeFunc
     * @param array<string, mixed>  $phpFormatFormatFrame
     *
     * @noinspection PhpDocSignatureIsNotCompleteInspection
     */
    private static function getNullableValue(string $key, callable $isValueTypeFunc, string $dbgExpectedType, array $phpFormatFormatFrame): mixed
    {
        static $logger = new Logger(srcCodeNamespace: __NAMESPACE__, srcCodeClass: __CLASS__, srcCodeFunc: __FUNCTION__, srcCodeFile: __FILE__);

        if (!array_key_exists($key, $phpFormatFormatFrame)) {
            return null;
        }

        $value = $phpFormatFormatFrame[$key];
        if ($value === null) {
            return null;
        }

        if (!$isValueTypeFunc($value)) {
            $logger->error()?->log(
                __LINE__,
                'Unexpected type for value under key',
                ['key' => $key, 'expected type' => $dbgExpectedType, 'actual type' => get_debug_type($value), 'actual value' => $value]
            );
            return null;
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $frame
     */
    private static function hasNonLocationPropertiesInPhpFormat(array $frame): bool
    {
        return self::getNullableStringValue(StackTraceUtil::FUNCTION_KEY, $frame) !== null;
    }

    /**
     * @param array<string, mixed>         $srcFrame
     */
    private static function copyLocationPropertiesFromPhpToClassicFormat(array $srcFrame, StackTraceFrame $dstFrame): void
    {
        $dstFrame->file = self::getNullableStringValue(StackTraceUtil::FILE_KEY, $srcFrame);
        $dstFrame->line = self::getNullableIntValue(StackTraceUtil::LINE_KEY, $srcFrame);
    }

    /**
     * @param array<string, mixed> $srcFrame
     */
    private static function copyNonLocationPropertiesFromPhpToClassicFormat(array $srcFrame, bool $includeArgs, bool $includeThisObj, StackTraceFrame $dstFrame): void
    {
        /** @var ?class-string $class */
        $class = self::getNullableStringValue(StackTraceUtil::CLASS_KEY, $srcFrame);
        $dstFrame->class = $class;
        $dstFrame->function = self::getNullableStringValue(StackTraceUtil::FUNCTION_KEY, $srcFrame);
        $dstFrame->isStatic = self::isStaticMethodInPhpFormat($srcFrame);
        if ($includeThisObj) {
            $dstFrame->thisObj = self::getNullableObjectValue(StackTraceUtil::THIS_OBJECT_KEY, $srcFrame);
        }
        if ($includeArgs) {
            $dstFrame->args = self::getNullableArrayValue(StackTraceUtil::ARGS_KEY, $srcFrame);
        }
    }

    /**
     * @param array<string, mixed>            $currentInputFrame
     * @param ?array<string, mixed>           $nextInputFrame
     * @param ?array<string, mixed>          &$bufferedBeforeTopFrame
     * @param StackTraceFrame[] &$outputFrames
     *
     * @phpstan-param null|positive-int       $maxNumberOfFrames
     */
    private static function captureConsume(
        ?int $maxNumberOfFrames,
        bool $includeArgs,
        bool $includeThisObj,
        array $currentInputFrame,
        ?array $nextInputFrame,
        ?array &$bufferedBeforeTopFrame,
        bool &$isTopFrame,
        array &$outputFrames,
    ): bool {
        if ($isTopFrame) {
            if ($bufferedBeforeTopFrame === null) {
                $bufferedBeforeTopFrame = $currentInputFrame;
                return true;
            }

            $isTopFrame = false;
            if (self::hasNonLocationPropertiesInPhpFormat($currentInputFrame)) {
                $outputFrame = new StackTraceFrame();
                self::copyNonLocationPropertiesFromPhpToClassicFormat($currentInputFrame, $includeArgs, $includeThisObj, $outputFrame);
                self::copyLocationPropertiesFromPhpToClassicFormat($bufferedBeforeTopFrame, $outputFrame);
                if (!self::addToOutputFrames($outputFrame, $maxNumberOfFrames, /* ref */ $outputFrames)) {
                    return false;
                }
            }
        }

        $outputFrame = new StackTraceFrame();
        self::copyLocationPropertiesFromPhpToClassicFormat($currentInputFrame, $outputFrame);
        if ($nextInputFrame !== null) {
            self::copyNonLocationPropertiesFromPhpToClassicFormat($nextInputFrame, $includeArgs, $includeThisObj, $outputFrame);
        }
        return self::addToOutputFrames($outputFrame, $maxNumberOfFrames, /* ref */ $outputFrames);
    }

    /**
     * @template TOutputFrame
     *
     * @param TOutputFrame    $frameToAdd
     * @param TOutputFrame[] &$outputFrames
     *
     * @phpstan-param null|positive-int $maxNumberOfFrames
     */
    private static function addToOutputFrames($frameToAdd, ?int $maxNumberOfFrames, /* ref */ array &$outputFrames): bool
    {
        $outputFrames[] = $frameToAdd;
        return count($outputFrames) !== $maxNumberOfFrames;
    }
}
