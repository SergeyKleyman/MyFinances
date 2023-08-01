<?php

declare(strict_types=1);

namespace MyFinancesTests\UtilTests;

use MyFinances\Util\Log\ContextStack;
use MyFinances\Util\Log\LoggableToString;
use MyFinances\Util\Pair;
use MyFinances\Util\RangeUtil;
use MyFinancesTests\Util\IterableUtilForTests;
use PHPUnit\Framework\TestCase;

/**
 * This class extends TestCase and TestCaseBase on purpose because TestCaseBase uses LogContextStack
 */
final class LogContextStackTest extends TestCase
{
    /**
     * @template TKey of array-key
     * @template TValue
     *
     * @param TKey                $expectedKey
     * @param TValue              $expectedVal
     * @param array<TKey, TValue> $actualArray
     */
    public static function assertSameValueInArray($expectedKey, $expectedVal, array $actualArray): void
    {
        self::assertArrayHasKey($expectedKey, $actualArray);
        self::assertSame($expectedVal, $actualArray[$expectedKey]);
    }

    /**
     * @param Pair<string, array<string, mixed>>[] $expected
     */
    private static function assertContextsStack(array $expected): void
    {
        $actual = ContextStack::singletonInstance()->getContextsStack();
        $logCtx = ['expected' => $expected, 'actual' => $actual];
        self::assertSame(count($expected), count($actual), LoggableToString::convert($logCtx));
        foreach (IterableUtilForTests::zip(array_keys($expected), array_keys($actual)) as [$expectedCtxIndex, $actualCtxDesc]) {
            self::assertIsInt($expectedCtxIndex);
            self::assertIsString($actualCtxDesc);
            $logCtxPerIt = array_merge($logCtx, ['expectedCtxIndex' => $expectedCtxIndex, 'actualCtxDesc' => $actualCtxDesc]);
            $expectedCtxFuncName = $expected[$expectedCtxIndex]->first;
            /** @var array{string, array<string, mixed>} $expectedCtx */
            $expectedCtx = $expected[$expectedCtxIndex]->second;
            $actualCtx = $actual[$actualCtxDesc];
            $logCtxPerIt = array_merge($logCtxPerIt, ['expectedCtxFuncName' => $expectedCtxFuncName, 'expectedCtx' => $expectedCtx, 'actualCtx' => $actualCtx]);
            self::assertStringContainsString($expectedCtxFuncName, $actualCtxDesc, LoggableToString::convert($logCtxPerIt));
            self::assertStringContainsString(basename(__FILE__), $actualCtxDesc, LoggableToString::convert($logCtxPerIt));
            self::assertStringContainsString(__CLASS__, $actualCtxDesc, LoggableToString::convert($logCtxPerIt));
            self::assertSame(count($expectedCtx), count($actualCtx), LoggableToString::convert($logCtxPerIt));
            foreach (IterableUtilForTests::zip(array_keys($expectedCtx), array_keys($actualCtx)) as [$expectedKey, $actualKey]) {
                $logCtxPerCtxKey = array_merge($logCtxPerIt, ['expectedKey' => $expectedKey, 'actualKey' => $actualKey]);
                self::assertSame($expectedKey, $actualKey, LoggableToString::convert($logCtxPerCtxKey));
                self::assertSame($expectedCtx[$expectedKey], $actualCtx[$actualKey], LoggableToString::convert($logCtxPerCtxKey));
            }
        }
    }

    /**
     * @param array<string, mixed>                 $initialCtx
     * @param Pair<string, array<string, mixed>>[] $expectedContextsStackFromCaller
     *
     * @return Pair<string, array<string, mixed>>[]
     */
    private static function newExpectedScope(string $funcName, array $initialCtx = [], array $expectedContextsStackFromCaller = []): array
    {
        $expectedContextsStack = $expectedContextsStackFromCaller;
        $newCount = array_unshift(/* ref */ $expectedContextsStack, new Pair($funcName, $initialCtx));
        self::assertSame(count($expectedContextsStackFromCaller) + 1, $newCount);
        self::assertContextsStack($expectedContextsStack);
        return $expectedContextsStack;
    }

    /**
     * @param Pair<string, array<string, mixed>>[] $expectedContextsStack
     * @param array<string, mixed>                 $ctx
     */
    private static function addToTopExpectedScope(/* ref */ array $expectedContextsStack, array $ctx): void
    {
        self::assertNotEmpty($expectedContextsStack);
        $expectedContextsStack[0]->second = array_merge($expectedContextsStack[0]->second, $ctx);
        self::assertContextsStack($expectedContextsStack);
    }

    /**
     * @return array<string, mixed>
     */
    private static function getTopActualScope(): array
    {
        $actualContextsStack = ContextStack::singletonInstance()->getContextsStack();
        self::assertNotEmpty($actualContextsStack);
        return ArrayUtilForTests::getFirstValue($actualContextsStack);
    }

    public function testOneFunc(): void
    {
        ContextStack::newScope(/* out */ $logCtx, ['my_key' => 1]);
        $expectedContextsStack = self::newExpectedScope(__FUNCTION__, ['my_key' => 1]);
        self::assertSameValueInArray('my_key', 1, self::getTopActualScope());
        $logCtx->add(['my_key' => '2']);
        self::addToTopExpectedScope(/* ref */ $expectedContextsStack, ['my_key' => '2']);
        self::assertSameValueInArray('my_key', '2', self::getTopActualScope());
        $logCtx->add(['my_other_key' => 3.5]);
        self::addToTopExpectedScope(/* ref */ $expectedContextsStack, ['my_other_key' => 3.5]);
        self::assertSameValueInArray('my_other_key', 3.5, self::getTopActualScope());
    }

    public function testTwoFunctions(): void
    {
        ContextStack::newScope(/* out */ $logCtx, ['my context' => 'before func']);
        $expectedContextsStack = self::newExpectedScope(__FUNCTION__, ['my context' => 'before func']);

        /**
         * @param Pair<string, array<string, mixed>>[] $expectedContextsStackFromCaller
         */
        $secondFunc = static function (array $expectedContextsStackFromCaller): void {
            ContextStack::newScope(/* out */ $logCtx, ['my context' => 'func entry']);
            $expectedContextsStack = self::newExpectedScope(__FUNCTION__, ['my context' => 'func entry'], $expectedContextsStackFromCaller);
            self::assertSameValueInArray('my context', 'func entry', self::getTopActualScope());

            $logCtx->add(['some_other_key' => 'inside func']);
            self::addToTopExpectedScope(/* ref */ $expectedContextsStack, ['some_other_key' => 'inside func']);
            self::assertSameValueInArray('some_other_key', 'inside func', self::getTopActualScope());
        };

        $secondFunc($expectedContextsStack);
        self::assertSameValueInArray('my context', 'before func', self::getTopActualScope());
        self::assertArrayNotHasKey('some_other_key', self::getTopActualScope());

        $logCtx->add(['my context' => 'after func']);
        self::addToTopExpectedScope(/* ref */ $expectedContextsStack, ['my context' => 'after func']);
        self::assertSameValueInArray('my context', 'after func', self::getTopActualScope());
    }

    public function testSubScopeSimple(): void
    {
        ContextStack::newScope(/* out */ $logCtx);
        $expectedContextsStackOutsideSubScope = self::newExpectedScope(__FUNCTION__);
        $logCtx->add(['my context' => 'before sub-scope']);
        self::addToTopExpectedScope(/* ref */ $expectedContextsStackOutsideSubScope, ['my context' => 'before sub-scope']);

        $logCtx->pushSubScope();
        {
            $expectedContextsStackInsideSubScope = self::newExpectedScope(__FUNCTION__, /* initialCtx */ [], $expectedContextsStackOutsideSubScope);
            $logCtx->add(['my context' => 'inside sub-scope']);
            self::addToTopExpectedScope(/* ref */ $expectedContextsStackInsideSubScope, ['my context' => 'inside sub-scope']);
            self::assertSameValueInArray('my context', 'inside sub-scope', self::getTopActualScope());
        }
        $logCtx->popSubScope();
        self::assertContextsStack($expectedContextsStackOutsideSubScope);
        self::assertSameValueInArray('my context', 'before sub-scope', self::getTopActualScope());

        $logCtx->add(['my context' => 'after sub-scope']);
        self::addToTopExpectedScope(/* ref */ $expectedContextsStackOutsideSubScope, ['my context' => 'after sub-scope']);
        self::assertSameValueInArray('my context', 'after sub-scope', self::getTopActualScope());
    }

    /**
     * @return iterable<array{bool}>
     */
    public static function boolDataProvider(): iterable
    {
        yield [true];
        yield [false];
    }

    /**
     * @dataProvider boolDataProvider
     */
    public function testSubScopeEarlyReturn(bool $SubScopeShouldExitEarly): void
    {
        ContextStack::newScope(/* out */ $logCtx, ['my context' => 'before calling 2nd func']);
        $expectedContextsStack = self::newExpectedScope(__FUNCTION__, ['my context' => 'before calling 2nd func']);
        self::assertSameValueInArray('my context', 'before calling 2nd func', self::getTopActualScope());

        /**
         * @param Pair<string, array<string, mixed>>[] $expectedContextsStackFromCaller
         */
        $secondFunc = static function (array $expectedContextsStackFromCaller) use ($SubScopeShouldExitEarly): void {
            ContextStack::newScope(/* out */ $logCtx, ['my context' => 'before sub-scope']);
            $expectedContextsStackOutsideSubScope = self::newExpectedScope(__FUNCTION__, ['my context' => 'before sub-scope'], $expectedContextsStackFromCaller);
            self::assertSameValueInArray('my context', 'before sub-scope', self::getTopActualScope());

            $logCtx->pushSubScope();
            {
            $expectedContextsStackInsideSubScope = self::newExpectedScope(__FUNCTION__, /* initialCtx */ [], $expectedContextsStackOutsideSubScope);
            $logCtx->add(['my context' => 'inside sub-scope']);
            self::addToTopExpectedScope(/* ref */ $expectedContextsStackInsideSubScope, ['my context' => 'inside sub-scope']);
            self::assertSameValueInArray('my context', 'inside sub-scope', self::getTopActualScope());
            if ($SubScopeShouldExitEarly) {
                return;
            }
            }
            $logCtx->popSubScope();
            self::assertContextsStack($expectedContextsStackOutsideSubScope);
            self::assertSameValueInArray('my context', 'before sub-scope', self::getTopActualScope());

            $logCtx->add(['my context' => 'after sub-scope']);
            self::addToTopExpectedScope(/* ref */ $expectedContextsStackOutsideSubScope, ['my context' => 'after sub-scope']);
            self::assertSameValueInArray('my context', 'after sub-scope', self::getTopActualScope());
        };

        $secondFunc($expectedContextsStack);

        $logCtx->add(['my context' => 'after calling 2nd func']);
        self::addToTopExpectedScope(/* ref */ $expectedContextsStack, ['my context' => 'after calling 2nd func']);
        self::assertSameValueInArray('my context', 'after calling 2nd func', self::getTopActualScope());
    }

    public function testSubScopeForLoop(): void
    {
        ContextStack::newScope(/* out */ $logCtx);
        $expectedContextsStackOutsideLoop = self::newExpectedScope(__FUNCTION__);
        $logCtx->add(['my context' => 'before loop']);
        self::addToTopExpectedScope(/* ref */ $expectedContextsStackOutsideLoop, ['my context' => 'before loop']);

        $logCtx->pushSubScope();
        foreach (RangeUtil::generateUpTo(2) as $index) {
            $logCtx->clearCurrentSubScope(['index' => $index]);
            $expectedContextsStackInsideLoop = self::newExpectedScope(__FUNCTION__, /* initialCtx */ ['index' => $index], $expectedContextsStackOutsideLoop);
            self::assertSameValueInArray('index', $index, self::getTopActualScope());
            self::assertSame(1, count(self::getTopActualScope()));
            $logCtx->add(['key_with_index_' . $index => 'value_with_index_' . $index]);
            self::addToTopExpectedScope(/* ref */ $expectedContextsStackInsideLoop, ['key_with_index_' . $index => 'value_with_index_' . $index]);
            self::assertSameValueInArray('key_with_index_' . $index, 'value_with_index_' . $index, self::getTopActualScope());
            self::assertSame(2, count(self::getTopActualScope()));
        }
        $logCtx->popSubScope();

        $logCtx->add(['my context' => 'after loop']);
        self::addToTopExpectedScope(/* ref */ $expectedContextsStackOutsideLoop, ['my context' => 'after loop']);
    }

    public function testSubScopeForLoopWithContinue(): void
    {
        ContextStack::newScope(/* out */ $logCtx);
        $expectedContextsFunc = self::newExpectedScope(__FUNCTION__);
        $logCtx->add(['my context' => 'before 1st loop']);
        self::addToTopExpectedScope(/* ref */ $expectedContextsFunc, ['my context' => 'before 1st loop']);

        $logCtx->pushSubScope();
        foreach (RangeUtil::generateUpTo(3) as $index1stLoop) {
            $logCtx->clearCurrentSubScope(['index1stLoop' => $index1stLoop]);
            $expectedContexts1stLoop = self::newExpectedScope(__FUNCTION__, ['index1stLoop' => $index1stLoop], $expectedContextsFunc);

            $logCtx->add(['my context' => 'before 1st loop']);
            self::addToTopExpectedScope(/* ref */ $expectedContexts1stLoop, ['my context' => 'before 1st loop']);

            $logCtx->add(['1st loop key with index ' . $index1stLoop => '1st loop value with index ' . $index1stLoop]);
            self::addToTopExpectedScope(/* ref */ $expectedContexts1stLoop, ['1st loop key with index ' . $index1stLoop => '1st loop value with index ' . $index1stLoop]);

            $logCtx->pushSubScope();
            foreach (RangeUtil::generateUpTo(5) as $index2ndLoop) {
                $logCtx->clearCurrentSubScope(['index2ndLoop' => $index2ndLoop]);
                $expectedContexts2ndLoop = self::newExpectedScope(__FUNCTION__, ['index2ndLoop' => $index2ndLoop], $expectedContexts1stLoop);

                if ($index2ndLoop > 2) {
                    continue;
                }

                $logCtx->add(['2nd loop key with index ' . $index2ndLoop => '2nd loop value with index ' . $index2ndLoop]);
                self::addToTopExpectedScope(/* ref */ $expectedContexts2ndLoop, ['2nd loop key with index ' . $index2ndLoop => '2nd loop value with index ' . $index2ndLoop]);
            }
            $logCtx->popSubScope();

            if ($index1stLoop > 1) {
                continue;
            }

            $logCtx->add(['my context' => 'after 2nd loop']);
            self::addToTopExpectedScope(/* ref */ $expectedContexts1stLoop, ['my context' => 'after 2nd loop']);
        }
        $logCtx->popSubScope();

        $logCtx->add(['my context' => 'after 1st loop']);
        self::addToTopExpectedScope(/* ref */ $expectedContextsFunc, ['my context' => 'after 1st loop']);
    }

    /**
     * @param Pair<string, array<string, mixed>>[] $expectedContextsStackFromCaller
     */
    private static function recursiveFunc(int $currentDepth, array $expectedContextsStackFromCaller): void
    {
        ContextStack::newScope(/* out */ $logCtx, ['my context' => 'inside recursive func before recursive call']);
        $expectedContextsStack = self::newExpectedScope(__FUNCTION__, ['my context' => 'inside recursive func before recursive call'], $expectedContextsStackFromCaller);

        $logCtx->add(['key for depth ' . $currentDepth => 'value for depth ' . $currentDepth]);
        self::addToTopExpectedScope(/* ref */ $expectedContextsStack, ['key for depth ' . $currentDepth => 'value for depth ' . $currentDepth]);

        if ($currentDepth < 3) {
            self::recursiveFunc($currentDepth + 1, $expectedContextsStack);
        }

        $assertMsgCtx = ['currentDepth' => $currentDepth, 'expectedContextsStack' => $expectedContextsStack];
        $depth = $currentDepth;
        foreach (ContextStack::singletonInstance()->getContextsStack() as $actualCtxDesc => $actualCtx) {
            $assertMsgCtxPerIt = array_merge($assertMsgCtx, ['depth' => $depth, 'actualCtxDesc' => $actualCtxDesc, 'actualCtx' => $actualCtx]);
            self::assertStringContainsString(__FUNCTION__, $actualCtxDesc, LoggableToString::convert($assertMsgCtxPerIt));
            self::assertStringContainsString(basename(__FILE__), $actualCtxDesc, LoggableToString::convert($assertMsgCtxPerIt));
            self::assertStringContainsString(__CLASS__, $actualCtxDesc, LoggableToString::convert($assertMsgCtxPerIt));

            self::assertSame('inside recursive func before recursive call', $actualCtx['my context'], LoggableToString::convert($assertMsgCtxPerIt));
            self::assertSame('value for depth ' . $depth, $actualCtx['key for depth ' . $depth], LoggableToString::convert($assertMsgCtxPerIt));

            if ($depth === 1) {
                break;
            }
            --$depth;
        }

        $logCtx->add(['my context' => 'inside recursive func after recursive call']);
        self::addToTopExpectedScope(/* ref */ $expectedContextsStack, ['my context' => 'inside recursive func after recursive call']);
    }

    public function testRecursiveFunc(): void
    {
        ContextStack::newScope(/* out */ $logCtx, ['my context' => 'before recursive func']);
        $expectedContextsStack = self::newExpectedScope(__FUNCTION__, ['my context' => 'before recursive func']);

        self::recursiveFunc(1, $expectedContextsStack);
    }

    /**
     * @return array<string, mixed>
     *
     * @noinspection PhpUnusedParameterInspection
     */
    private static function helperFuncForTestFuncArgs(int $intParam, ?string $nullableStringParam): array
    {
        return ContextStack::funcArgs();
    }

    /**
     * @return iterable<array{int, ?string, array<string, mixed>}>
     */
    public static function dataProviderForTestFuncArgs(): iterable
    {
        yield [1, 'abc', ['intParam' => 1, 'nullableStringParam' => 'abc']];
        yield [2, null, ['intParam' => 2, 'nullableStringParam' => null]];
    }

    /**
     * @dataProvider dataProviderForTestFuncArgs
     *
     * @param array<string, mixed> $expectedArgs
     */
    public function testFuncArgs(int $intParam, ?string $nullableStringParam, array $expectedArgs): void
    {
        $actualArgs = self::helperFuncForTestFuncArgs($intParam, $nullableStringParam);
        $logCtx = ['intParam' => $intParam, 'nullableStringParam' => $nullableStringParam, 'expectedArgs' => $expectedArgs, 'actualArgs' => $actualArgs];
        self::assertSame(count($expectedArgs), count($actualArgs), LoggableToString::convert($logCtx));
        foreach (IterableUtilForTests::zip(array_keys($expectedArgs), array_keys($actualArgs)) as [$expectedParamName, $actualParamName]) {
            $logCtxPerArg = array_merge($logCtx, ['expectedParamName' => $expectedParamName, 'actualParamName' => $actualParamName]);
            self::assertSame($expectedParamName, $actualParamName, LoggableToString::convert($logCtxPerArg));
            self::assertSame($expectedArgs[$expectedParamName], $actualArgs[$actualParamName], LoggableToString::convert($logCtxPerArg));
        }
    }

    public function testCaptureVarByRef(): void
    {
        ContextStack::newScope(/* out */ $logCtx);

        $localVar = 1;
        $logCtx->add(['localVar' => &$localVar]);

        $localVar = 2;

        $capturedCtxStack = ContextStack::singletonInstance()->getContextsStack();
        self::assertCount(1, $capturedCtxStack);
        $thisFuncCtx = ArrayUtilForTests::getFirstValue($capturedCtxStack);
        self::assertArrayHasKey('localVar', $thisFuncCtx);
        self::assertSame(2, $thisFuncCtx['localVar']);
    }
}
