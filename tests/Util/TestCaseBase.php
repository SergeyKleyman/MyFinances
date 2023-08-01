<?php

declare(strict_types=1);

namespace MyFinancesTests\Util;

use Countable;
use MyFinances\Util\Log\ContextStack;
use MyFinances\Util\RangeUtil;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;

abstract class TestCaseBase extends TestCase
{
    public static function dummyAssert(): void
    {
        self::assertTrue(true);
    }

    /**
     * @param mixed[] $expected
     * @param mixed[] $actual
     */
    public static function assertEqualAsSets(array $expected, array $actual, string $message = ''): void
    {
        self::assertTrue(sort(/* ref */ $expected));
        self::assertTrue(sort(/* ref */ $actual));
        self::assertEqualsCanonicalizing($expected, $actual, $message);
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
     * @template T
     *
     * @param Optional<T> $expected
     * @param T           $actual
     */
    public static function assertSameExpectedOptional(Optional $expected, $actual): void
    {
        if ($expected->isValueSet()) {
            self::assertSame($expected->getValue(), $actual);
        }
    }

    /**
     * @param array<array-key, mixed>|Countable $haystack
     */
    public static function assertCountAtLeast(int $expectedMinCount, array|Countable $haystack): void
    {
        ContextStack::newScope(/* out */ $logCtx, ContextStack::funcArgs());
        self::assertGreaterThanOrEqual($expectedMinCount, count($haystack));
    }

    /**
     * @param array<array-key, mixed> $array
     */
    public static function assertArrayHasKeyWithValue(string|int $key, mixed $expectedValue, array $array, string $message = ''): void
    {
        self::assertArrayHasKey($key, $array, $message);
        self::assertSame($expectedValue, $array[$key], $message);
    }

    /**
     * @param mixed[] $expected
     * @param mixed[] $actual
     */
    public static function assertEqualLists(array $expected, array $actual): void
    {
        ContextStack::newScope(/* out */ $logCtx, ContextStack::funcArgs());
        self::assertSame(count($expected), count($actual));
        $logCtx->pushSubScope();
        foreach (RangeUtil::generateUpTo(count($expected)) as $i) {
            $logCtx->clearCurrentSubScope(['i' => $i]);
            self::assertSame($expected[$i], $actual[$i]);
        }
        $logCtx->popSubScope();
    }

    /**
     * @param mixed[] $subSet
     * @param mixed[] $largerSet
     */
    public static function assertListIsSubsetOf(array $subSet, array $largerSet): void
    {
        self::assertIsList($subSet);
        self::assertIsList($largerSet);

        $intersect = array_intersect($subSet, $largerSet);
        $diff = array_diff($subSet, $largerSet);
        ContextStack::newScope(
            $logCtx,
            [
                'count(diff)'      => count($diff),
                'count(subSet)'    => count($subSet),
                'count(largerSet)' => count($largerSet),
                'count(intersect)' => count($intersect),
                'diff'             => $diff,
                'intersect'        => $intersect,
                'subSet'           => $subSet,
                'largerSet'        => $largerSet,
            ]
        );
        self::assertSame(count($intersect), count($subSet));
    }

    /**
     * @template TKey of array-key
     * @template TValue
     *
     * @param array<TKey, TValue> $subsetMap
     * @param array<TKey, TValue> $containingMap
     */
    public static function assertMapIsSubsetOf(array $subsetMap, array $containingMap): void
    {
        ContextStack::newScope(/* out */ $dbgCtx, ContextStack::funcArgs());
        self::assertGreaterThanOrEqual(count($subsetMap), count($containingMap));
        $dbgCtx->pushSubScope();
        foreach ($subsetMap as $subsetMapKey => $subsetMapVal) {
            $dbgCtx->clearCurrentSubScope(['subsetMapKey' => $subsetMapKey, 'subsetMapVal' => $subsetMapVal]);
            self::assertArrayHasKey($subsetMapKey, $containingMap);
            self::assertEquals($subsetMapVal, $containingMap[$subsetMapKey]);
        }
        $dbgCtx->popSubScope();
    }

    public static function failEx(string $message = ''): void
    {
        try {
            Assert::fail($message);
        } catch (AssertionFailedError $ex) {
            PhpUnitExtension::printLogContextStackToStdErr();
            throw $ex;
        }
    }
}
