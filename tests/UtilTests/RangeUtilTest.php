<?php

declare(strict_types=1);

namespace MyFinancesTests\UtilTests;

use MyFinances\Util\IterableUtil;
use MyFinancesTests\Util\IterableUtilForTests;
use MyFinancesTests\Util\TestCaseBase;
use MyFinances\Util\RangeUtil;

final class RangeUtilTest extends TestCaseBase
{
    public function testGenerate(): void
    {
        /**
         * @param int $begin
         * @param int $end
         * @param int $step
         *
         * @return array<int>
         */
        $generateAsArray = static fn (int $begin, int $end, int $step = 1) => IterableUtilForTests::toList(RangeUtil::generate($begin, $end, $step));

        self::assertEquals([], $generateAsArray(0, 0));
        self::assertEquals([], $generateAsArray(0, 0, 0));
        self::assertEquals([], $generateAsArray(0, 0, 1));
        self::assertEquals([], $generateAsArray(0, 0, -1));

        self::assertEquals([], $generateAsArray(100, 100));
        self::assertEquals([], $generateAsArray(100, 100, 0));
        self::assertEquals([], $generateAsArray(100, 100, 1));
        self::assertEquals([], $generateAsArray(100, 100, -1));

        self::assertEquals([0], $generateAsArray(0, 1));
        self::assertEquals([0, 1], $generateAsArray(0, 2));

        self::assertEquals([-1], $generateAsArray(-1, 0));
        self::assertEquals([-2, -1], $generateAsArray(-2, 0));
        self::assertEquals([-2], $generateAsArray(-2, 0, 2));

        self::assertEquals([], $generateAsArray(0, -1));
        self::assertEquals([], $generateAsArray(0, -1, 1));
        self::assertEquals([], $generateAsArray(0, -1, -1));

        self::assertEquals([], $generateAsArray(0, -2));
        self::assertEquals([], $generateAsArray(0, -2, 1));
        self::assertEquals([], $generateAsArray(0, -2, -1));
        self::assertEquals([], $generateAsArray(0, -2, 2));
        self::assertEquals([], $generateAsArray(0, -2, -2));

        self::assertEquals([101, 102], $generateAsArray(101, 103));
        self::assertEquals([-103, -102], $generateAsArray(-103, -101));
        self::assertEquals([], $generateAsArray(-101, -103));

        self::assertTrue(IterableUtilForTests::isEmpty(RangeUtil::generate(1_000, 1_000)));
        self::assertFalse(IterableUtilForTests::isEmpty(RangeUtil::generate(1_000, 1_001)));

        self::assertSame(0, IterableUtil::count(RangeUtil::generate(1_000, 1_000)));
        self::assertSame(1, IterableUtil::count(RangeUtil::generate(1_000, 1_001)));
    }

    public function testGenerateDown(): void
    {
        /**
         * @param int $begin
         * @param int $end
         * @param int $step
         *
         * @return array<int>
         */
        $generateDownAsArray = static fn (int $begin, int $end, int $step = 1): array => IterableUtilForTests::toList(RangeUtil::generateDown($begin, $end, $step));

        self::assertEquals([], $generateDownAsArray(0, 0));
        self::assertEquals([], $generateDownAsArray(0, 0, 0));
        self::assertEquals([], $generateDownAsArray(0, 0, 1));
        self::assertEquals([], $generateDownAsArray(0, 0, -1));

        self::assertEquals([], $generateDownAsArray(100, 100));
        self::assertEquals([], $generateDownAsArray(100, 100, 0));
        self::assertEquals([], $generateDownAsArray(100, 100, 1));
        self::assertEquals([], $generateDownAsArray(100, 100, -1));

        self::assertEquals([1], $generateDownAsArray(1, 0));
        self::assertEquals([2, 1], $generateDownAsArray(2, 0));

        self::assertEquals([0], $generateDownAsArray(0, -1));
        self::assertEquals([0, -1], $generateDownAsArray(0, -2));
        self::assertEquals([0], $generateDownAsArray(0, -2, 2));

        self::assertEquals([], $generateDownAsArray(-1, 0));
        self::assertEquals([], $generateDownAsArray(-1, 0, 1));
        self::assertEquals([], $generateDownAsArray(-1, 0, -1));

        self::assertEquals([], $generateDownAsArray(-2, 0));
        self::assertEquals([], $generateDownAsArray(-2, 0, 1));
        self::assertEquals([], $generateDownAsArray(-2, 0, -1));
        self::assertEquals([], $generateDownAsArray(-2, 0, 2));
        self::assertEquals([], $generateDownAsArray(-2, 0, -2));

        self::assertEquals([103, 102], $generateDownAsArray(103, 101));
        self::assertEquals([-101, -102], $generateDownAsArray(-101, -103));
        self::assertEquals([], $generateDownAsArray(-103, -101));

        self::assertTrue(IterableUtilForTests::isEmpty(RangeUtil::generateDown(1_000, 1_000)));
        self::assertFalse(IterableUtilForTests::isEmpty(RangeUtil::generateDown(1_001, 1_000)));

        self::assertSame(0, IterableUtil::count(RangeUtil::generateDown(1_000, 1_000)));
        self::assertSame(1, IterableUtil::count(RangeUtil::generateDown(1_001, 1_000)));
    }

    public function testGenerateUpTo(): void
    {
        /**
         * @param int $count
         *
         * @return array<int>
         */
        $generateUpToAsArray = static fn (int $count): array => IterableUtilForTests::toList(RangeUtil::generateUpTo($count));

        self::assertEquals([], $generateUpToAsArray(0));
        self::assertEquals([0], $generateUpToAsArray(1));
        self::assertEquals([0, 1], $generateUpToAsArray(2));
    }

    public function testGenerateFromToIncluding(): void
    {
        /**
         * @param int $begin
         * @param int $end
         *
         * @return array<int>
         */
        $generateFromToIncludingAsArray = static fn (int $begin, int $end): array => IterableUtilForTests::toList(RangeUtil::generateFromToIncluding($begin, $end));

        self::assertEquals([0], $generateFromToIncludingAsArray(0, 0));
        self::assertEquals([0, 1], $generateFromToIncludingAsArray(0, 1));
        self::assertEquals([0, 1, 2], $generateFromToIncludingAsArray(0, 2));
    }
}
