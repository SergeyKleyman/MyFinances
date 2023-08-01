<?php

declare(strict_types=1);

namespace MyFinancesTests\Util;

use MyFinances\Util\Log\LoggableToString;

final class DataProviderForTestBuilderTest extends TestCaseBase
{
    public function testOneList(): void
    {
        $inputList = ['a', 'b', 'c'];
        $expected =
            IterableUtilForTests::toList(CombinatorialUtilForTests::cartesianProduct([$inputList]));
        self::assertEqualAsSets([['a'], ['b'], ['c']], $expected);
        foreach (IterableUtilForTests::ALL_BOOL_VALUES as $onlyFirstValueCombinable) {
            $actual = IterableUtilForTests::toList(
                $onlyFirstValueCombinable
                    ? (new DataProviderForTestBuilder())
                    ->addDimensionOnlyFirstValueCombinable($inputList)
                    ->build()
                    : (new DataProviderForTestBuilder())
                    ->addDimensionAllValuesCombinable($inputList)
                    ->build()
            );
            self::assertEqualAsSets($expected, $actual);
        }
    }

    /**
     * @return iterable<array{bool, bool}>
     */
    public static function dataProviderForTwoBoolArgs(): iterable
    {
        foreach (IterableUtilForTests::ALL_BOOL_VALUES as $onlyFirstValueCombinable1) {
            foreach (IterableUtilForTests::ALL_BOOL_VALUES as $onlyFirstValueCombinable2) {
                yield [$onlyFirstValueCombinable1, $onlyFirstValueCombinable2];
            }
        }
    }

    /**
     * @dataProvider dataProviderForTwoBoolArgs
     */
    public function testTwoLists(bool $onlyFirstValueCombinable1, bool $onlyFirstValueCombinable2): void
    {
        $inputList1 = ['a', 'b'];
        $inputList2 = [1, 2, 3];
        $actual = IterableUtilForTests::toList(
            (new DataProviderForTestBuilder())
                ->addDimension($onlyFirstValueCombinable1, $inputList1)
                ->addDimension($onlyFirstValueCombinable2, $inputList2)
                ->build()
        );
        if ($onlyFirstValueCombinable1 && $onlyFirstValueCombinable2) {
            $expected = [
                ['a', 1],
                ['b', 1],
                ['a', 2],
                ['a', 3],
            ];
        } else {
            $expected = IterableUtilForTests::toList(
                CombinatorialUtilForTests::cartesianProduct([$inputList1, $inputList2])
            );
        }
        TestCaseBase::assertEqualAsSets(
            $expected,
            $actual,
            LoggableToString::convert(
                [
                    'onlyFirstValueCombinable1' => $onlyFirstValueCombinable1,
                    'onlyFirstValueCombinable2' => $onlyFirstValueCombinable2,
                    '$expected'                 => $expected,
                    'actual'                    => $actual,
                ]
            )
        );
    }

    /**
     * @dataProvider dataProviderForTwoBoolArgs
     */
    public function testOneGeneratorAddsMultipleDimensions(bool $disableInstrumentationsOnlyFirstValueCombinable, bool $dbNameOnlyFirstValueCombinable): void
    {
        $disableInstrumentationsVariants = [
            ''    => true,
            'pdo' => false,
            'db'  => false,
        ];
        $dbNameVariants = ['memory', '/tmp/file'];
        $actual = IterableUtilForTests::toList(
            (new DataProviderForTestBuilder())
                ->addGenerator(
                    $disableInstrumentationsOnlyFirstValueCombinable,
                    /**
                     * @param array<string|int, mixed> $resultSoFar
                     *
                     * @return iterable<array<string|int, mixed>>
                     */
                    static function (array $resultSoFar) use ($disableInstrumentationsVariants): iterable {
                        foreach ($disableInstrumentationsVariants as $optVal => $isInstrumentationEnabled) {
                            yield array_merge($resultSoFar, [$optVal, $isInstrumentationEnabled]);
                        }
                    }
                )
                ->addDimension($dbNameOnlyFirstValueCombinable, $dbNameVariants)
                ->build()
        );

        $disableInstrumentationsVariantsPairs = [];
        foreach ($disableInstrumentationsVariants as $optVal => $isInstrumentationEnabled) {
            $disableInstrumentationsVariantsPairs[] = [$optVal, $isInstrumentationEnabled];
        }
        $cartesianProductPacked = IterableUtilForTests::toList(
            CombinatorialUtilForTests::cartesianProduct([$disableInstrumentationsVariantsPairs, $dbNameVariants])
        );
        // Unpack $disableInstrumentationsVariants pair in each row
        $cartesianProduct = [];
        foreach ($cartesianProductPacked as $cartesianProductPackedRow) {
            self::assertIsArray($cartesianProductPackedRow);
            self::assertCount(2, $cartesianProductPackedRow);
            $pair = $cartesianProductPackedRow[0];
            self::assertIsArray($pair);
            self::assertCount(2, $pair);
            $cartesianProductRow = [];
            $cartesianProductRow[] = $pair[0];
            $cartesianProductRow[] = $pair[1];
            $cartesianProductRow[] = $cartesianProductPackedRow[1];
            $cartesianProduct[] = $cartesianProductRow;
        }

        if ($disableInstrumentationsOnlyFirstValueCombinable && $dbNameOnlyFirstValueCombinable) {
            $expected = [
                ['', true, 'memory'],
                ['pdo', false, 'memory'],
                ['db', false, 'memory'],
                ['', true, '/tmp/file'],
            ];
        } else {
            $expected = $cartesianProduct;
        }

        TestCaseBase::assertEqualAsSets(
            $expected,
            $actual,
            LoggableToString::convert(
                [
                    'disableInstrumentationsOnlyFirstValueCombinable' => $disableInstrumentationsOnlyFirstValueCombinable,
                    'dbNameOnlyFirstValueCombinable'                  => $dbNameOnlyFirstValueCombinable,
                    '$expected'                                       => $expected,
                    'actual'                                          => $actual,
                ]
            )
        );
    }

    /**
     * @dataProvider dataProviderForTwoBoolArgs
     */
    public function testTwoKeyedDimensions(bool $onlyFirstValueCombinable1, bool $onlyFirstValueCombinable2): void
    {
        $inputList1 = ['a', 'b'];
        $inputList2 = [1, 2, 3];
        $actual = IterableUtilForTests::toList(
            (new DataProviderForTestBuilder())
                ->addKeyedDimension('letter', $onlyFirstValueCombinable1, $inputList1)
                ->addKeyedDimension('digit', $onlyFirstValueCombinable2, $inputList2)
                ->build()
        );
        if ($onlyFirstValueCombinable1 && $onlyFirstValueCombinable2) {
            $expected = [
                ['letter' => 'a', 'digit' => 1],
                ['letter' => 'b', 'digit' => 1],
                ['letter' => 'a', 'digit' => 2],
                ['letter' => 'a', 'digit' => 3],
            ];
        } else {
            $expected = IterableUtilForTests::toList(
                CombinatorialUtilForTests::cartesianProduct([$inputList1, $inputList2])
            );
        }
        TestCaseBase::assertEqualAsSets(
            $expected,
            $actual,
            LoggableToString::convert(
                [
                    'onlyFirstValueCombinable1' => $onlyFirstValueCombinable1,
                    'onlyFirstValueCombinable2' => $onlyFirstValueCombinable2,
                    '$expected'                 => $expected,
                    'actual'                    => $actual,
                ]
            )
        );
    }

    /**
     * @dataProvider boolDataProvider
     */
    public function testCartesianProductKeyed(bool $dimAOnlyFirstValueCombinable): void
    {
        $actual = IterableUtilForTests::toList(
            (new DataProviderForTestBuilder())
                ->addKeyedDimension('dimA', $dimAOnlyFirstValueCombinable, [1.23, 4.56])
                ->addCartesianProductOnlyFirstValueCombinable(['dimB' => [1, 2, 3], 'dimC' => ['a', 'b']])
                ->build()
        );
        $expected = $dimAOnlyFirstValueCombinable
            ?
            [
                ['dimA' => 1.23, 'dimB' => 1, 'dimC' => 'a'],
                ['dimA' => 4.56, 'dimB' => 1, 'dimC' => 'a'],
                ['dimA' => 1.23, 'dimB' => 1, 'dimC' => 'b'],
                ['dimA' => 1.23, 'dimB' => 2, 'dimC' => 'a'],
                ['dimA' => 1.23, 'dimB' => 2, 'dimC' => 'b'],
                ['dimA' => 1.23, 'dimB' => 3, 'dimC' => 'a'],
                ['dimA' => 1.23, 'dimB' => 3, 'dimC' => 'b'],
            ]
            :
            [
                ['dimA' => 1.23, 'dimB' => 1, 'dimC' => 'a'],
                ['dimA' => 4.56, 'dimB' => 1, 'dimC' => 'a'],
                ['dimA' => 1.23, 'dimB' => 1, 'dimC' => 'b'],
                ['dimA' => 4.56, 'dimB' => 1, 'dimC' => 'b'],
                ['dimA' => 1.23, 'dimB' => 2, 'dimC' => 'a'],
                ['dimA' => 4.56, 'dimB' => 2, 'dimC' => 'a'],
                ['dimA' => 1.23, 'dimB' => 2, 'dimC' => 'b'],
                ['dimA' => 4.56, 'dimB' => 2, 'dimC' => 'b'],
                ['dimA' => 1.23, 'dimB' => 3, 'dimC' => 'a'],
                ['dimA' => 4.56, 'dimB' => 3, 'dimC' => 'a'],
                ['dimA' => 1.23, 'dimB' => 3, 'dimC' => 'b'],
                ['dimA' => 4.56, 'dimB' => 3, 'dimC' => 'b'],
            ];
        TestCaseBase::assertEqualAsSets(
            $expected,
            $actual,
            LoggableToString::convert(['$expected' => $expected, 'actual' => $actual])
        );
    }

    /**
     * @dataProvider boolDataProvider
     */
    public function testCartesianProduct(bool $dimAOnlyFirstValueCombinable): void
    {
        $actual = IterableUtilForTests::toList(
            (new DataProviderForTestBuilder())
                ->addDimension($dimAOnlyFirstValueCombinable, [1.23, 4.56])
                ->addCartesianProductOnlyFirstValueCombinable([[1, 2, 3], ['a', 'b']])
                ->build()
        );
        $expected = $dimAOnlyFirstValueCombinable
            ?
            [
                [1.23, 1, 'a'],
                [4.56, 1, 'a'],
                [1.23, 1, 'b'],
                [1.23, 2, 'a'],
                [1.23, 2, 'b'],
                [1.23, 3, 'a'],
                [1.23, 3, 'b'],
            ]
            :
            [
                [1.23, 1, 'a'],
                [4.56, 1, 'a'],
                [1.23, 1, 'b'],
                [4.56, 1, 'b'],
                [1.23, 2, 'a'],
                [4.56, 2, 'a'],
                [1.23, 2, 'b'],
                [4.56, 2, 'b'],
                [1.23, 3, 'a'],
                [4.56, 3, 'a'],
                [1.23, 3, 'b'],
                [4.56, 3, 'b'],
            ];
        TestCaseBase::assertEqualAsSets(
            $expected,
            $actual,
            LoggableToString::convert(['$expected' => $expected, 'actual' => $actual])
        );
    }

    public function testConditional(): void
    {
        $actual = IterableUtilForTests::toList(
            (new DataProviderForTestBuilder())
                ->addKeyedDimensionAllValuesCombinable('1st_dim_key', [1, 2])
                ->addConditionalKeyedDimensionAllValueCombinable(
                    '2ns_dim_key' /* <- new dimension key */,
                    '1st_dim_key' /* <- depends on dimension key */,
                    1 /* <- depends on dimension true value */,
                    ['a'] /* <- new dimension variants for true case */,
                    ['b', 'c'] /* <- new dimension variants for false case */
                )
                ->build()
        );
        $expected =
            [
                ['1st_dim_key' => 1, '2ns_dim_key' => 'a'],
                ['1st_dim_key' => 2, '2ns_dim_key' => 'b'],
                ['1st_dim_key' => 2, '2ns_dim_key' => 'c'],
            ];
        TestCaseBase::assertEqualAsSets($expected, $actual);
    }

    /**
     * @dataProvider boolDataProvider
     */
    public function testUsingRangeForDimensionValues(bool $dimAOnlyFirstValueCombinable): void
    {
        $actual = IterableUtilForTests::toList(
            (new DataProviderForTestBuilder())
                ->addKeyedDimension('1st_dim_key', $dimAOnlyFirstValueCombinable, DataProviderForTestBuilder::rangeUpTo(2))
                ->addKeyedDimension('2nd_dim_key', $dimAOnlyFirstValueCombinable, DataProviderForTestBuilder::rangeUpTo(2))
                ->addKeyedDimension('3rd_dim_key', $dimAOnlyFirstValueCombinable, DataProviderForTestBuilder::rangeUpTo(2))
                ->build()
        );
        $expected = $dimAOnlyFirstValueCombinable
            ?
            [
                ['1st_dim_key' => 0, '2ns_dim_key' => 0, '3rd_dim_key' => 0],
                ['1st_dim_key' => 0, '2ns_dim_key' => 0, '3rd_dim_key' => 1],
                ['1st_dim_key' => 0, '2ns_dim_key' => 1, '3rd_dim_key' => 0],
                ['1st_dim_key' => 1, '2ns_dim_key' => 0, '3rd_dim_key' => 0],
            ]
            :
            [
                ['1st_dim_key' => 0, '2ns_dim_key' => 0, '3rd_dim_key' => 0],
                ['1st_dim_key' => 0, '2ns_dim_key' => 0, '3rd_dim_key' => 1],
                ['1st_dim_key' => 0, '2ns_dim_key' => 1, '3rd_dim_key' => 0],
                ['1st_dim_key' => 0, '2ns_dim_key' => 1, '3rd_dim_key' => 1],
                ['1st_dim_key' => 1, '2ns_dim_key' => 0, '3rd_dim_key' => 0],
                ['1st_dim_key' => 1, '2ns_dim_key' => 0, '3rd_dim_key' => 1],
                ['1st_dim_key' => 1, '2ns_dim_key' => 1, '3rd_dim_key' => 0],
                ['1st_dim_key' => 1, '2ns_dim_key' => 1, '3rd_dim_key' => 1],
            ];
        TestCaseBase::assertEqualAsSets($expected, $actual);
    }
}
