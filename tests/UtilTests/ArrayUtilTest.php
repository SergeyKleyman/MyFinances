<?php

declare(strict_types=1);

namespace MyFinancesTests\UtilTests;

use MyFinances\Util\ArrayUtil;
use MyFinancesTests\Util\IterableUtilForTests;
use MyFinancesTests\Util\TestCaseBase;

final class ArrayUtilTest extends TestCaseBase
{
    public static function testIsList(): void
    {
        self::assertTrue(ArrayUtil::isList([]));
        self::assertTrue(ArrayUtil::isList([1]));
        self::assertTrue(ArrayUtil::isList(['a', 'b']));
        self::assertTrue(ArrayUtil::isList(['different', 0, 'value', 1.23, 'types']));
        self::assertTrue(ArrayUtil::isList([0 => 'a']));
        self::assertTrue(ArrayUtil::isList([0 => 'a', 1 => 'b']));

        // The keys are not in the correct order
        self::assertFalse(ArrayUtil::isList([1 => 'b', 0 => 'a']));

        // The array does not start at 0
        self::assertFalse(ArrayUtil::isList([1 => 'b']));

        // Non-integer keys
        self::assertFalse(ArrayUtil::isList(['foo' => 'bar']));

        // Non-consecutive keys
        self::assertFalse(ArrayUtil::isList([0 => 'a', 2 => 'b']));
    }

    /**
     * @return iterable<array{mixed[], mixed[]}>
     */
    public static function dataProviderForTestIterateListInReverse(): iterable
    {
        yield [[], []];
        yield [[1], [1]];
        yield [[1, 'b'], ['b', 1]];
    }

    /**
     * @dataProvider dataProviderForTestIterateListInReverse
     *
     * @param mixed[] $inputArray
     * @param mixed[] $expectedOutputArray
     *
     * @return void
     */
    public static function testIterateListInReverse(array $inputArray, array $expectedOutputArray): void
    {
        $pair = IterableUtilForTests::zip($expectedOutputArray, ArrayUtilForTests::iterateListInReverse($inputArray));
        foreach ($pair as [$expectedVal, $actualVal]) {
            self::assertSame($expectedVal, $actualVal);
        }
    }

    /**
     * @param mixed[] $args
     *
     * @return void
     */
    private static function verifyArgs(array $args): void
    {
        self::assertCount(1, $args);
        $arg0 = $args[0];
        self::assertIsString($arg0);
    }

    /**
     * @param mixed[] $args
     *
     * @return void
     */
    private static function instrumentationFunc(array $args): void
    {
        self::assertCount(1, $args);
        self::verifyArgs($args);
        $someParam =& $args[0];
        self::assertSame('value set by instrumentedFunc caller', $someParam);
        self::assertIsString($someParam);
        $someParam = 'value set by instrumentationFunc';
    }

    /** @noinspection PhpSameParameterValueInspection */
    private static function instrumentedFunc(string $someParam): string
    {
        self::instrumentationFunc([&$someParam]);
        return $someParam;
    }

    public static function testReferencesInArray(): void
    {
        $instrumentedFuncRetVal = self::instrumentedFunc('value set by instrumentedFunc caller');
        self::assertSame('value set by instrumentationFunc', $instrumentedFuncRetVal);
    }

    public static function testRemoveElementFromTwoLevelArrayViaReferenceToFirstLevel(): void
    {
        $myArr = [
            'level 1 - a' => [
                'level 2 - a' => 'value for level 2 - a',
                'level 2 - b' => 'value for level 2 - b',
            ],
        ];
        $level1ValRef =& $myArr['level 1 - a'];
        self::assertArrayHasKey('level 2 - a', $level1ValRef);
        self::assertSame('value for level 2 - a', $level1ValRef['level 2 - a']);
        unset($level1ValRef['level 2 - a']);
        self::assertArrayNotHasKey('level 2 - a', $myArr['level 1 - a']);
        self::assertArrayHasKey('level 2 - b', $myArr['level 1 - a']);
    }
}
