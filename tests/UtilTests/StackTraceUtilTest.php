<?php

declare(strict_types=1);

namespace MyFinancesTests\UtilTests;

use MyFinances\Util\ArrayUtil;
use MyFinances\Util\Log\ContextStack;
use MyFinances\Util\StackTraceFrame;
use MyFinances\Util\StackTraceUtil;
use MyFinancesTests\Util\DataProviderForTestBuilder;
use MyFinancesTests\Util\MixedMap;
use MyFinancesTests\Util\StackTraceFrameExpectations;
use MyFinancesTests\Util\TestCaseBase;

// use function dummyFuncForTestsWithoutNamespace;
//
// use const DUMMY_FUNC_FOR_TESTS_WITHOUT_NAMESPACE_CALLABLE_FILE_NAME;
// use const DUMMY_FUNC_FOR_TESTS_WITHOUT_NAMESPACE_CALLABLE_LINE_NUMBER;

final class StackTraceUtilTest extends TestCaseBase
{
    private const NUMBER_OF_STACK_FRAMES_TO_SKIP_KEY = 'number_of_stack_frames_to_skip';
    private const MAX_NUMBER_OF_FRAMES_KEY = 'max_number_of_frames';
    private const INCLUDE_ARGS_KEY = 'include_args';
    private const INCLUDE_THIS_OBJ_KEY = 'include_this_obj';

    // private const INPUT_KEY = 'input';
    // private const FULL_EXPECTED_OUTPUT_KEY = 'full_expected_output';

    private const VERY_LARGE_STACK_TRACE_SIZE_LIMIT = 1_000;

    public function dummyToUseStackTraceFrame(): StackTraceFrame
    {
        return new StackTraceFrame();
    }

    public function testClosureExpectations(): void
    {
        $closureFrameExpectations = StackTraceFrameExpectations::fromClosure(__FILE__, /* temp dummy lineNumber */ 0, __NAMESPACE__, __CLASS__, isStatic: false);
        /**
         * @return StackTraceFrame[]
         */
        $closure = function () use ($closureFrameExpectations): array {
            self::assertNull($this->dummyToUseStackTraceFrame()->file); // Dummy use of $this to force closure to be non-static
            $closureFrameExpectations->line->setValue(__LINE__ + 1);
            return StackTraceUtil::capture();
        };
        $lineWithCallToClosure = __LINE__ + 1;
        $actualStackTrace = $closure();
        self::assertCountAtLeast(3, $actualStackTrace);
        $closureFrameExpectations->assertMatches($actualStackTrace[0]);
        $thisFuncFrameExpectations = StackTraceFrameExpectations::fromClassMethod(__FILE__, $lineWithCallToClosure, __CLASS__, isStatic: false, method: __FUNCTION__);
        $thisFuncFrameExpectations->assertMatches($actualStackTrace[1]);
    }

    public function testStaticClosureExpectations(): void
    {
        $closureFrameExpectations = StackTraceFrameExpectations::fromClosure(__FILE__, /* temp dummy lineNumber */ 0, __NAMESPACE__, __CLASS__, isStatic: true);
        /**
         * @return StackTraceFrame[]
         */
        $closure = static function () use ($closureFrameExpectations): array {
            $closureFrameExpectations->line->setValue(__LINE__ + 1);
            return StackTraceUtil::capture();
        };
        $lineWithCallToClosure = __LINE__ + 1;
        $actualStackTrace = $closure();
        self::assertCountAtLeast(3, $actualStackTrace);
        $closureFrameExpectations->assertMatches($actualStackTrace[0]);
        $thisFuncFrameExpectations = StackTraceFrameExpectations::fromClassMethod(__FILE__, $lineWithCallToClosure, __CLASS__, isStatic: false, method: __FUNCTION__);
        $thisFuncFrameExpectations->assertMatches($actualStackTrace[1]);
    }

    /**
     * @param null|(mixed[]) $actualFrameArgs
     *
     * @noinspection PhpSameParameterValueInspection
     */
    private static function assertFrameArgsCount(bool $includeArgs, int $expectedArgsCount, ?array $actualFrameArgs): void
    {
        if ($includeArgs) {
            self::assertNotNull($actualFrameArgs);
            self::assertCount($expectedArgsCount, $actualFrameArgs);
        } else {
            self::assertNull($actualFrameArgs);
        }
    }

    /**
     * @param null|(mixed[]) $actualFrameArgs
     *
     * @noinspection PhpSameParameterValueInspection
     */
    private static function assertFrameArgSame(bool $includeArgs, int $argIndex, mixed $expectedValue, ?array $actualFrameArgs): void
    {
        if ($includeArgs) {
            self::assertNotNull($actualFrameArgs);
            self::assertCountAtLeast($argIndex, $actualFrameArgs);
            self::assertArrayHasKeyWithValue($argIndex, $expectedValue, $actualFrameArgs);
        }
    }

    private static function assertFrameThisObjSame(bool $includeThisObj, object $expectedThisObj, ?object $actualFrameThisObj): void
    {
        if ($includeThisObj) {
            self::assertSame($expectedThisObj, $actualFrameThisObj);
        } else {
            self::assertNull($actualFrameThisObj);
        }
    }

    /**
     * @return iterable<string, array{MixedMap}>
     */
    public static function dataProviderForTestCapture(): iterable
    {
        $result = (new DataProviderForTestBuilder())
            ->addKeyedDimensionAllValuesCombinable(self::NUMBER_OF_STACK_FRAMES_TO_SKIP_KEY, [0, 1, 2, 3, 4, 5, 10, self::VERY_LARGE_STACK_TRACE_SIZE_LIMIT])
            ->addKeyedDimensionAllValuesCombinable(self::MAX_NUMBER_OF_FRAMES_KEY, [null, 0, 1, 2, 3, 4, 5, 10, self::VERY_LARGE_STACK_TRACE_SIZE_LIMIT])
            ->addBoolKeyedDimensionAllValuesCombinable(self::INCLUDE_ARGS_KEY)
            ->addBoolKeyedDimensionAllValuesCombinable(self::INCLUDE_THIS_OBJ_KEY)
            ->build();

        return DataProviderForTestBuilder::convertEachDataSetToMixedMap($result);
    }

    /**
     * @dataProvider dataProviderForTestCapture
     */
    public function testCaptureOneTestFrame(MixedMap $testArgs): void
    {
        ContextStack::newScope(/* out */ $logCtx, ContextStack::funcArgs());

        $numberOfFramesToSkip = $testArgs->getPositiveOrZeroInt(self::NUMBER_OF_STACK_FRAMES_TO_SKIP_KEY);
        $maxNumberOfFrames = $testArgs->getNullablePositiveOrZeroInt(self::MAX_NUMBER_OF_FRAMES_KEY);
        $includeArgs = $testArgs->getBool(self::INCLUDE_ARGS_KEY);
        $includeThisObj = $testArgs->getBool(self::INCLUDE_THIS_OBJ_KEY);

        $phpFormatStackTrace = debug_backtrace();
        $logCtx->add(['phpFormatStackTrace' => $phpFormatStackTrace]);
        if ($maxNumberOfFrames === 0) {
            $lineCaptureCall = -1;
            $actualCapturedStackTrace = [];
        } else {
            $lineCaptureCall = __LINE__ + 1;
            $actualCapturedStackTrace = StackTraceUtil::capture($numberOfFramesToSkip, $maxNumberOfFrames, $includeArgs, $includeThisObj);
        }
        $logCtx->add(['actualCapturedStackTrace' => $actualCapturedStackTrace]);

        if ($maxNumberOfFrames === 0 || $numberOfFramesToSkip >= count($phpFormatStackTrace)) {
            self::assertEmpty($actualCapturedStackTrace);
        } else {
            self::assertNotEmpty($actualCapturedStackTrace);
        }

        if ($numberOfFramesToSkip === 0 && $maxNumberOfFrames !== 0) {
            $frame = $actualCapturedStackTrace[0];
            self::assertSame(__FUNCTION__, $frame->function);
            self::assertSame(__FILE__, $frame->file);
            self::assertSame($lineCaptureCall, $frame->line);
            self::assertSame(__CLASS__, $frame->class);
            self::assertFalse($frame->isStatic);
            self::assertFrameThisObjSame($includeThisObj, $this, $frame->thisObj);
            self::assertFrameArgsCount($includeArgs, 1, $frame->args);
            self::assertFrameArgSame($includeArgs, /* testArgs param index */ 0, $testArgs, $frame->args);
        } elseif (!ArrayUtil::isEmpty($actualCapturedStackTrace)) {
            $frame = $actualCapturedStackTrace[0];
            self::assertNotEquals(__FUNCTION__, $frame->function);
        }
    }

    /**
     * @param array<string, mixed>[] &$expectedFramesProps
     * @param array<string, mixed>   &$dbgStackTrace
     *
     * @return StackTraceFrame[]
     *
     * @param-out array<string, mixed> $dbgStackTrace
     */
    private function helper1ForTestCaptureMultipleTestFrames(MixedMap $testArgs, array &$expectedFramesProps, ?array &$dbgStackTrace): array
    {
        array_unshift(/* ref */ $expectedFramesProps, [StackTraceUtil::LINE_KEY => __LINE__ + 1]);
        return self::helper2StaticForTestCaptureMultipleTestFrames($testArgs, $expectedFramesProps, $dbgStackTrace);
    }

    /**
     * @param array<string, mixed>[] &$expectedFramesProps
     * @param array<string, mixed>   &$dbgStackTrace
     *
     * @return StackTraceFrame[]
     *
     * @param-out array<string, mixed> $dbgStackTrace
     */
    private static function helper2StaticForTestCaptureMultipleTestFrames(MixedMap $testArgs, array &$expectedFramesProps, ?array &$dbgStackTrace): array
    {
        /**
         * @return StackTraceFrame[]
         */
        $func = static function () use ($testArgs, &$expectedFramesProps, &$dbgStackTrace): array {
            array_unshift(/* ref */ $expectedFramesProps, [StackTraceUtil::LINE_KEY => DUMMY_FUNC_FOR_TESTS_WITHOUT_NAMESPACE_CALLABLE_LINE_NUMBER]);
            $numberOfFramesToSkip = $testArgs->getPositiveOrZeroInt(self::NUMBER_OF_STACK_FRAMES_TO_SKIP_KEY);
            $maxNumberOfFrames = $testArgs->getNullablePositiveOrZeroInt(self::MAX_NUMBER_OF_FRAMES_KEY);
            $includeArgs = $testArgs->getBool(self::INCLUDE_ARGS_KEY);
            $includeThisObj = $testArgs->getBool(self::INCLUDE_THIS_OBJ_KEY);
            $dbgStackTrace = debug_backtrace();
            array_unshift(/* ref */ $expectedFramesProps, [StackTraceUtil::LINE_KEY => __LINE__ + 1, StackTraceUtil::FUNCTION_KEY => __FUNCTION__]);
            return $maxNumberOfFrames === 0 ? [] : StackTraceUtil::capture($numberOfFramesToSkip, $maxNumberOfFrames, $includeArgs, $includeThisObj);
        };

        array_unshift(/* ref */ $expectedFramesProps, [StackTraceUtil::LINE_KEY => __LINE__ + 1]);
        return dummyFuncForTestsWithoutNamespace($func);
    }

    /**
     * @phpstan-param 0|positive-int      $numberOfFramesToSkip
     * @phpstan-param null|0|positive-int $maxNumberOfFrames
     *
     * @noinspection PhpVarTagWithoutVariableNameInspection
     */
    private static function shouldCaptureFrameWithIndex(int $fullStackFrameIndex, int $numberOfFramesToSkip, ?int $maxNumberOfFrames): bool
    {
        return $numberOfFramesToSkip <= $fullStackFrameIndex && ($maxNumberOfFrames === null || $maxNumberOfFrames > ($fullStackFrameIndex - $numberOfFramesToSkip));
    }

    /**
     * @dataProvider dataProviderForTestCapture
     */
    public function testCaptureMultipleTestFrames(MixedMap $testArgs): void
    {
        ContextStack::newScope(/* out */ $logCtx, ContextStack::funcArgs());

        $numberOfFramesToSkip = $testArgs->getPositiveOrZeroInt(self::NUMBER_OF_STACK_FRAMES_TO_SKIP_KEY);
        $maxNumberOfFrames = $testArgs->getNullablePositiveOrZeroInt(self::MAX_NUMBER_OF_FRAMES_KEY);
        $includeArgs = $testArgs->getBool(self::INCLUDE_ARGS_KEY);
        $includeThisObj = $testArgs->getBool(self::INCLUDE_THIS_OBJ_KEY);

        /** @var array<string, mixed>[] $expectedFramesProps */
        $expectedFramesProps = [];
        array_unshift(/* ref */ $expectedFramesProps, [StackTraceUtil::LINE_KEY => __LINE__ + 1]);
        $actualCapturedStackTrace = self::helper1ForTestCaptureMultipleTestFrames($testArgs, /* out */ $expectedFramesProps, /* out */ $dbgStackTrace);
        $logCtx->add(['actualCapturedStackTrace' => $actualCapturedStackTrace, 'expectedFramesProps' => $expectedFramesProps, 'dbgStackTrace' => $dbgStackTrace]);

        if ($maxNumberOfFrames === 0 || $numberOfFramesToSkip >= count($dbgStackTrace)) {
            self::assertEmpty($actualCapturedStackTrace);
        } else {
            self::assertNotEmpty($actualCapturedStackTrace);
        }

        $shouldCaptureFrameWithIndex = static fn (int $fullStackFrameIndex) => self::shouldCaptureFrameWithIndex($fullStackFrameIndex, $numberOfFramesToSkip, $maxNumberOfFrames);

        $actualCapturedStackTraceFrameIndex = 0;
        $logCtx->add(['actualCapturedStackTraceFrameIndex' => &$actualCapturedStackTraceFrameIndex]);
        $fullStackFrameIndex = 0;
        $logCtx->add(['fullStackFrameIndex' => &$fullStackFrameIndex]);

        if ($shouldCaptureFrameWithIndex($fullStackFrameIndex)) {
            $frame = $actualCapturedStackTrace[$actualCapturedStackTraceFrameIndex];
            self::assertSame($expectedFramesProps[$fullStackFrameIndex][StackTraceUtil::FUNCTION_KEY], $frame->function);
            self::assertSame(__FILE__, $frame->file);
            self::assertSame($expectedFramesProps[$fullStackFrameIndex][StackTraceUtil::LINE_KEY], $frame->line);
            self::assertSame(__CLASS__, $frame->class);
            self::assertTrue($frame->isStatic);
            self::assertNull($frame->thisObj);
            self::assertFrameArgsCount($includeArgs, 0, $frame->args);
            ++$actualCapturedStackTraceFrameIndex;
        }

        ++$fullStackFrameIndex;
        if ($shouldCaptureFrameWithIndex($fullStackFrameIndex)) {
            $frame = $actualCapturedStackTrace[$actualCapturedStackTraceFrameIndex];
            self::assertSame('dummyFuncForTestsWithoutNamespace', $frame->function);
            self::assertSame(DUMMY_FUNC_FOR_TESTS_WITHOUT_NAMESPACE_CALLABLE_FILE_NAME, $frame->file);
            self::assertSame($expectedFramesProps[$fullStackFrameIndex][StackTraceUtil::LINE_KEY], $frame->line);
            self::assertNull($frame->class);
            self::assertNull($frame->isStatic);
            self::assertNull($frame->thisObj);
            self::assertFrameArgsCount($includeArgs, 1, $frame->args);
            ++$actualCapturedStackTraceFrameIndex;
        }

        ++$fullStackFrameIndex;
        if ($shouldCaptureFrameWithIndex($fullStackFrameIndex)) {
            $frame = $actualCapturedStackTrace[$actualCapturedStackTraceFrameIndex];
            self::assertSame('helper2StaticForTestCaptureMultipleTestFrames', $frame->function);
            self::assertSame(__FILE__, $frame->file);
            self::assertSame($expectedFramesProps[$fullStackFrameIndex][StackTraceUtil::LINE_KEY], $frame->line);
            self::assertSame(__CLASS__, $frame->class);
            self::assertTrue($frame->isStatic);
            self::assertNull($frame->thisObj);
            self::assertFrameArgsCount($includeArgs, 3, $frame->args);
            self::assertFrameArgSame($includeArgs, /* testArgs param index */ 0, $testArgs, $frame->args);
            ++$actualCapturedStackTraceFrameIndex;
        }

        ++$fullStackFrameIndex;
        if ($shouldCaptureFrameWithIndex($fullStackFrameIndex)) {
            $frame = $actualCapturedStackTrace[$actualCapturedStackTraceFrameIndex];
            self::assertSame('helper1ForTestCaptureMultipleTestFrames', $frame->function);
            self::assertSame(__FILE__, $frame->file);
            self::assertSame($expectedFramesProps[$fullStackFrameIndex][StackTraceUtil::LINE_KEY], $frame->line);
            self::assertSame(__CLASS__, $frame->class);
            self::assertFalse($frame->isStatic);
            self::assertFrameThisObjSame($includeThisObj, $this, $frame->thisObj);
            self::assertFrameArgsCount($includeArgs, 3, $frame->args);
            self::assertFrameArgSame($includeArgs, /* testArgs param index */ 0, $testArgs, $frame->args);
            ++$actualCapturedStackTraceFrameIndex;
        }

        ++$fullStackFrameIndex;
        if ($shouldCaptureFrameWithIndex($fullStackFrameIndex)) {
            $frame = $actualCapturedStackTrace[$actualCapturedStackTraceFrameIndex];
            self::assertSame(__FUNCTION__, $frame->function);
            self::assertSame(__FILE__, $frame->file);
            self::assertSame($expectedFramesProps[$fullStackFrameIndex][StackTraceUtil::LINE_KEY], $frame->line);
            self::assertSame(__CLASS__, $frame->class);
            self::assertFalse($frame->isStatic);
            self::assertFrameThisObjSame($includeThisObj, $this, $frame->thisObj);
            self::assertFrameArgsCount($includeArgs, 1, $frame->args);
            self::assertFrameArgSame($includeArgs, /* testArgs param index */ 0, $testArgs, $frame->args);
        } elseif ($actualCapturedStackTraceFrameIndex <= count($actualCapturedStackTrace) - 1) {
            $frame = $actualCapturedStackTrace[$actualCapturedStackTraceFrameIndex];
            self::assertNotEquals(__FUNCTION__, $frame->function);
        }
    }
}
