<?php

declare(strict_types=1);

namespace MyFinancesTests\Util;

final class CombinatorialUtilTest extends TestCaseBase
{
    public function testPermutations(): void
    {
        /**
         * @param array<mixed> $totalSet
         * @param int          $subSetSize
         *
         * @return array<array<mixed>>
         */
        $permutationsAsArray = static fn (array $totalSet, int $subSetSize) => IterableUtilForTests::toList(CombinatorialUtilForTests::permutations($totalSet, $subSetSize));

        self::assertEqualsCanonicalizing([[]], $permutationsAsArray([], 0));
        self::assertEqualsCanonicalizing([[]], $permutationsAsArray(['a'], 0));
        self::assertEqualsCanonicalizing([[]], $permutationsAsArray(['a', 'b'], 0));
        self::assertEqualsCanonicalizing([['a']], $permutationsAsArray(['a'], 1));
        self::assertEqualsCanonicalizing([['a'], ['b']], $permutationsAsArray(['a', 'b'], 1));
        self::assertEqualsCanonicalizing([['a', 'b'], ['b', 'a']], $permutationsAsArray(['a', 'b'], 2));
        self::assertEqualsCanonicalizing([['a'], ['b'], ['c']], $permutationsAsArray(['a', 'b', 'c'], 1));
        self::assertEqualsCanonicalizing(
            [
                ['a', 'b'],
                ['a', 'c'],
                ['b', 'a'],
                ['b', 'c'],
                ['c', 'a'],
                ['c', 'b'],
            ],
            $permutationsAsArray(['a', 'b', 'c'], 2)
        );
        self::assertEqualsCanonicalizing(
            [
                ['a', 'b', 'c'],
                ['a', 'c', 'b'],
                ['b', 'a', 'c'],
                ['b', 'c', 'a'],
                ['c', 'a', 'b'],
                ['c', 'b', 'a'],
            ],
            $permutationsAsArray(['a', 'b', 'c'], 3)
        );
    }

    public function testCartesianProduct(): void
    {
        /**
         * @param array<string, iterable<mixed>> $iterables
         *
         * @return array<array<string, mixed>>
         */
        $cartesianProductAsArray = static fn (array $iterables) => IterableUtilForTests::toList(CombinatorialUtilForTests::cartesianProduct($iterables));

        self::assertEqualsCanonicalizing([[]], $cartesianProductAsArray([]));

        self::assertEqualsCanonicalizing(
            [
                ['digit' => 1],
                ['digit' => 2],
                ['digit' => 3],
            ],
            $cartesianProductAsArray(['digit' => [1, 2, 3]])
        );

        self::assertEqualsCanonicalizing(
            [
                ['digit' => 1, 'letter' => 'a'],
                ['digit' => 1, 'letter' => 'b'],
                ['digit' => 2, 'letter' => 'a'],
                ['digit' => 2, 'letter' => 'b'],
                ['digit' => 3, 'letter' => 'a'],
                ['digit' => 3, 'letter' => 'b'],
            ],
            $cartesianProductAsArray(['digit' => [1, 2, 3], 'letter' => ['a', 'b']])
        );

        self::assertEqualsCanonicalizing(
            [
                [1, 'a'],
                [1, 'b'],
                [2, 'a'],
                [2, 'b'],
                [3, 'a'],
                [3, 'b'],
            ],
            $cartesianProductAsArray([[1, 2, 3], ['a', 'b']])
        );
    }
}
