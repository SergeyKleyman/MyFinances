<?php

declare(strict_types=1);

namespace MyFinancesTests\Util;

use MyFinances\Util\IterableUtil;
use MyFinances\Util\Log\ContextStack;

final class IterableUtilTest extends TestCaseBase
{
    public static function testArraySuffix(): void
    {
        self::assertEqualLists([1, 2], IterableUtilForTests::toList(IterableUtil::arraySuffix([1, 2], 0)));
        self::assertEqualLists([2], IterableUtilForTests::toList(IterableUtil::arraySuffix([1, 2], 1)));
        self::assertEqualLists([], IterableUtilForTests::toList(IterableUtil::arraySuffix([1, 2], 2)));
        self::assertEqualLists([], IterableUtilForTests::toList(IterableUtil::arraySuffix([1, 2], 3)));
        self::assertEqualLists([], IterableUtilForTests::toList(IterableUtil::arraySuffix([], 0)));
        self::assertEqualLists([], IterableUtilForTests::toList(IterableUtil::arraySuffix([], 1)));
    }

    /**
     * @return iterable<array{mixed[][], mixed[][]}>
     */
    public static function dataProviderForTestZip(): iterable
    {
        yield [[[]], []];
        yield [[[], []], []];
        yield [[[], [], []], []];

        yield [[['a'], [1]], [['a', 1]]];
        yield [[['a', 'b'], [1, 2]], [['a', 1], ['b', 2]]];
        yield [[['a', 'b', 'c'], [1, 2, 3], [4.4, 5.5, 6.6]], [['a', 1, 4.4], ['b', 2, 5.5], ['c', 3, 6.6]]];
    }

    /**
     * @dataProvider dataProviderForTestZip
     *
     * @param mixed[][] $inputArrays
     * @param mixed[][] $expectedOutput
     *
     * @return void
     */
    public static function testZip(array $inputArrays, array $expectedOutput): void
    {
        ContextStack::newScope(/* out */ $logCtx, ContextStack::funcArgs());

        /**
         * @param iterable<mixed>[] $inputIterables
         * @param mixed[][]         $expectedOutput
         *
         * @return void
         */
        $test = static function (array $inputIterables, array $expectedOutput): void {
            ContextStack::newScope(/* out */ $logCtx, ContextStack::funcArgs());
            $logCtx->add(['count($inputIterables)' => count($inputIterables)]);
            $i = 0;
            $logCtx->pushSubScope();
            foreach (IterableUtilForTests::zip(...$inputIterables) as $actualTuple) {
                $logCtx->clearCurrentSubScope(['i' => $i, 'actualTuple' => $actualTuple]);
                self::assertLessThan(count($expectedOutput), $i);
                $expectedTuple = $expectedOutput[$i];
                self::assertEqualLists($expectedTuple, $actualTuple);
                ++$i;
            }
            $logCtx->popSubScope();
            self::assertSame(count($expectedOutput), $i);
        };

        $test($inputArrays, $expectedOutput);

        /**
         * @param mixed[] $inputArray
         *
         * @return iterable<mixed>
         */
        $arrayToGenerator = static function (array $inputArray): iterable {
            foreach ($inputArray as $val) {
                yield $val;
            }
        };

        /** @var iterable<mixed>[] $inputArraysAsGenerators */
        $inputArraysAsGenerators = [];
        foreach ($inputArrays as $inputArray) {
            $inputArraysAsGenerators[] = $arrayToGenerator($inputArray);
        }
        $test($inputArraysAsGenerators, $expectedOutput);
    }
}
