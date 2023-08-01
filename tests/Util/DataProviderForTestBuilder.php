<?php

declare(strict_types=1);

namespace MyFinancesTests\Util;

use MyFinances\Util\IterableUtil;
use MyFinances\Util\Log\LoggableToString;
use MyFinances\Util\RangeUtil;

final class DataProviderForTestBuilder
{
    /** @var bool[] */
    private array $onlyFirstValueCombinable = [];

    /** @var array<callable(array<mixed>): iterable<array<mixed>>> */
    private array $generators = [];

    private ?int $emitOnlyDataSetWithIndex = null;

    private function assertValid(): void
    {
        TestCaseBase::assertSameSize($this->generators, $this->onlyFirstValueCombinable);
    }

    /**
     * @template T
     *
     * @param array<T>|(callable(): iterable<T>) $values
     *
     * @return callable(): iterable<T>
     */
    private static function adaptArrayToMultiUseIterable(array|callable $values): callable
    {
        if (!is_array($values)) {
            return $values;
        }

        /**
         * @return iterable<T>
         */
        return static fn () => $values;
    }

    /**
     * @param callable(array<mixed>): iterable<array<mixed>> $generator
     *
     * @return $this
     *
     * @noinspection PhpDocSignatureIsNotCompleteInspection
     */
    public function addGenerator(bool $onlyFirstValueCombinable, callable $generator): self
    {
        $this->assertValid();

        $this->onlyFirstValueCombinable[] = $onlyFirstValueCombinable;
        $this->generators[] = $generator;

        $this->assertValid();
        return $this;
    }

    /**
     * @param callable(array<mixed> $resultSoFar): iterable<array<mixed>> $generator
     *
     * @return $this
     *
     * @noinspection PhpUnused
     */
    public function addGeneratorOnlyFirstValueCombinable(callable $generator): self
    {
        return $this->addGenerator(/* onlyFirstValueCombinable: */ true, $generator);
    }

    /**
     * @param callable(array<mixed>): iterable<array<mixed>> $generator
     *
     * @return $this
     *
     * @noinspection PhpUnused
     */
    public function addGeneratorAllValuesCombinable(callable $generator): self
    {
        return $this->addGenerator(/* onlyFirstValueCombinable: */ false, $generator);
    }

    /**
     * @param array<mixed>|(callable(): iterable<mixed>) $values
     *
     * @return $this
     */
    public function addDimension(bool $onlyFirstValueCombinable, array|callable $values): self
    {
        $this->addGenerator(
            $onlyFirstValueCombinable,
            /**
             * @param array<mixed> $resultSoFar
             * @return iterable<array<mixed>>
             */
            static function (array $resultSoFar) use ($values): iterable {
                $expectedKeyForList = 0;
                foreach (self::adaptArrayToMultiUseIterable($values)() as $key => $val) {
                    yield array_merge($resultSoFar, ($key === $expectedKeyForList) ? [$val] : [$key => $val]);
                    ++$expectedKeyForList;
                }
            }
        );
        return $this;
    }

    /**
     * @param array<mixed>|(callable(): iterable<mixed>) $values
     *
     * @return $this
     */
    public function addDimensionOnlyFirstValueCombinable(array|callable $values): self
    {
        return $this->addDimension(/* onlyFirstValueCombinable: */ true, $values);
    }

    /**
     * @param array<mixed>|(callable(): iterable<mixed>) $values
     *
     * @return $this
     */
    public function addDimensionAllValuesCombinable(array|callable $values): self
    {
        return $this->addDimension(/* onlyFirstValueCombinable: */ false, $values);
    }

    /**
     * @param array<mixed>|(callable(): iterable<mixed>) $values
     *
     * @return $this
     */
    public function addKeyedDimension(string $dimensionKey, bool $onlyFirstValueCombinable, array|callable $values): self
    {
        $this->addGenerator(
            $onlyFirstValueCombinable,
            /**
             * @param array<mixed> $resultSoFar
             * @return iterable<array<mixed>>
             */
            static function (array $resultSoFar) use ($dimensionKey, $values): iterable {
                foreach (self::adaptArrayToMultiUseIterable($values)() as $val) {
                    yield array_merge($resultSoFar, [$dimensionKey => $val]);
                }
            }
        );
        return $this;
    }

    /**
     * @param array<mixed>|(callable(): iterable<mixed>) $values
     *
     * @return $this
     *
     * @noinspection PhpUnused
     */
    public function addKeyedDimensionOnlyFirstValueCombinable(string $dimensionKey, array|callable $values): self
    {
        return $this->addKeyedDimension($dimensionKey, /* onlyFirstValueCombinable: */ true, $values);
    }

    /**
     * @param array<mixed>|(callable(): iterable<mixed>) $values
     *
     * @return $this
     *
     * @noinspection PhpUnused
     */
    public function addKeyedDimensionAllValuesCombinable(string $dimensionKey, array|callable $values): self
    {
        return $this->addKeyedDimension($dimensionKey, /* onlyFirstValueCombinable: */ false, $values);
    }

    /**
     * @return $this
     */
    public function addBoolDimension(bool $onlyFirstValueCombinable): self
    {
        return $this->addDimension($onlyFirstValueCombinable, IterableUtilForTests::ALL_BOOL_VALUES);
    }

    /** @noinspection PhpUnused */
    public function addBoolDimensionOnlyFirstValueCombinable(): self
    {
        return $this->addBoolDimension(/* onlyFirstValueCombinable: */ true);
    }

    /** @noinspection PhpUnused */
    public function addBoolDimensionAllValuesCombinable(): self
    {
        return $this->addBoolDimension(/* onlyFirstValueCombinable: */ false);
    }

    /**
     * @return $this
     */
    public function addBoolKeyedDimension(string $dimensionKey, bool $onlyFirstValueCombinable): self
    {
        $this->addKeyedDimension($dimensionKey, $onlyFirstValueCombinable, IterableUtilForTests::ALL_BOOL_VALUES);
        return $this;
    }

    /**
     * @return $this
     *
     * @noinspection PhpUnused
     */
    public function addBoolKeyedDimensionOnlyFirstValueCombinable(string $dimensionKey): self
    {
        return $this->addBoolKeyedDimension($dimensionKey, /* onlyFirstValueCombinable: */ true);
    }

    /**
     * @return $this
     *
     * @noinspection PhpUnused
     */
    public function addBoolKeyedDimensionAllValuesCombinable(string $dimensionKey): self
    {
        return $this->addBoolKeyedDimension($dimensionKey, /* onlyFirstValueCombinable: */ false);
    }

    /**
     * @return $this
     *
     * @noinspection PhpUnused
     */
    public function addSingleValueKeyedDimension(string $dimensionKey, mixed $value): self
    {
        return $this->addKeyedDimension($dimensionKey, /* onlyFirstValueCombinable: */ true, [$value]);
    }

    /**
     * @param iterable<mixed> $iterable
     *
     * @return mixed
     */
    private static function getIterableFirstValue(iterable $iterable): mixed
    {
        TestCaseBase::assertTrue(IterableUtilForTests::getFirstValue($iterable, /* out */ $value));
        return $value;
    }

    /**
     * @param array<mixed> $resultSoFar
     *
     * @return iterable<array<mixed>>
     */
    private function buildForGenIndex(int $genIndexForAllValues, array $resultSoFar, int $currentGenIndex): iterable
    {
        TestCaseBase::assertLessThanOrEqual(count($this->generators), $currentGenIndex);
        if ($currentGenIndex === count($this->generators)) {
            yield $resultSoFar;
            return;
        }

        $currentGen = $this->generators[$currentGenIndex];
        TestCaseBase::assertFalse(IterableUtilForTests::isEmpty($currentGen($resultSoFar)));
        $iterable = $currentGen($resultSoFar);
        $shouldGenAfterFirst = ($currentGenIndex === $genIndexForAllValues) || (!$this->onlyFirstValueCombinable[$currentGenIndex]);
        $resultsToGen = $shouldGenAfterFirst ? $iterable : [self::getIterableFirstValue($iterable)];
        $shouldGenFirst = ($genIndexForAllValues === 0) || ($currentGenIndex !== $genIndexForAllValues);
        $resultsToGen = $shouldGenFirst ? $resultsToGen : IterableUtilForTests::skipFirst($resultsToGen);

        foreach ($resultsToGen as $resultSoFarPlusCurrent) {
            /** @var array<mixed> $resultSoFarPlusCurrent */
            yield from $this->buildForGenIndex($genIndexForAllValues, $resultSoFarPlusCurrent, $currentGenIndex + 1);
        }
    }

    /**
     * @param array<mixed, array<mixed>> $iterables
     *
     * @return callable(array<mixed> $resultSoFar): iterable<array<mixed>> $generator
     */
    private static function cartesianProduct(array $iterables): callable
    {
        /**
         * @param array<string|int, mixed> $resultSoFar
         *
         * @return iterable<array<string|int, mixed>>
         */
        return static function (array $resultSoFar) use ($iterables): iterable {
            $cartesianProduct = CombinatorialUtilForTests::cartesianProduct($iterables);
            foreach ($cartesianProduct as $cartesianProductRow) {
                yield array_merge($resultSoFar, $cartesianProductRow);
            }
        };
    }

    /**
     * @param array<mixed, array<mixed>> $iterables
     *
     * @return $this
     */
    public function addCartesianProduct(bool $onlyFirstValueCombinable, array $iterables): self
    {
        return $this->addGenerator($onlyFirstValueCombinable, self::cartesianProduct($iterables));
    }

    /**
     * @param array<mixed, array<mixed>> $iterables
     *
     * @return $this
     */
    public function addCartesianProductOnlyFirstValueCombinable(array $iterables): self
    {
        return $this->addCartesianProduct(/* onlyFirstValueCombinable: */ true, $iterables);
    }

    /**
     * @param array<mixed, array<mixed>> $iterables
     *
     * @return $this
     *
     * @noinspection PhpUnused
     */
    public function addCartesianProductAllValuesCombinable(array $iterables): self
    {
        return $this->addCartesianProduct(/* onlyFirstValueCombinable: */ false, $iterables);
    }

    /**
     * @param array<array<mixed>>|(callable(): iterable<array<mixed>>) $combinationsForEnabled
     * @param array<array<mixed>>|(callable(): iterable<array<mixed>>) $combinationsForDisabled
     *
     * @return callable(array<mixed>): iterable<array<mixed>>
     *
     * @noinspection PhpDocSignatureIsNotCompleteInspection
     * @noinspection PhpUnused
     */
    public static function masterSwitchCombinationsGenerator(string $masterSwitchKey, array|callable $combinationsForEnabled, array|callable $combinationsForDisabled): callable
    {
        /**
         * @param array<mixed> $resultSoFar
         *
         * @return iterable<array<mixed>>
         */
        return static function (array $resultSoFar) use ($masterSwitchKey, $combinationsForEnabled, $combinationsForDisabled): iterable {
            foreach (self::adaptArrayToMultiUseIterable($combinationsForEnabled)() as $combination) {
                yield array_merge([$masterSwitchKey => true], array_merge($combination, $resultSoFar));
            }
            foreach (self::adaptArrayToMultiUseIterable($combinationsForDisabled)() as $combination) {
                yield array_merge([$masterSwitchKey => false], array_merge($combination, $resultSoFar));
            }
        };
    }

    /**
     * @param iterable<mixed, mixed> $iterableForTrue
     * @param iterable<mixed, mixed> $iterableForFalse
     *
     * @return $this
     */
    public function addConditionalKeyedDimension(
        string $dimensionKey,
        bool $onlyFirstValueCombinable,
        string $prevDimensionKey,
        mixed $prevDimensionTrueValue,
        iterable $iterableForTrue,
        iterable $iterableForFalse,
    ): self {
        return $this->addGenerator(
            $onlyFirstValueCombinable,
            /**
             * @param array<mixed> $resultSoFar
             *
             * @return iterable<array<mixed>>
             */
            static function (array $resultSoFar) use ($dimensionKey, $prevDimensionKey, $prevDimensionTrueValue, $iterableForTrue, $iterableForFalse): iterable {
                $iterable = $resultSoFar[$prevDimensionKey] === $prevDimensionTrueValue ? $iterableForTrue : $iterableForFalse;
                foreach ($iterable as $value) {
                    yield array_merge([$dimensionKey => $value], $resultSoFar);
                }
            }
        );
    }

    /**
     * @param iterable<mixed, mixed> $iterableForTrue
     * @param iterable<mixed, mixed> $iterableForFalse
     *
     * @return $this
     *
     * @noinspection PhpUnused
     */
    public function addConditionalKeyedDimensionOnlyFirstValueCombinable(
        string $dimensionKey,
        string $prevDimensionKey,
        mixed $prevDimensionTrueValue,
        iterable $iterableForTrue,
        iterable $iterableForFalse,
    ): self {
        return $this->addConditionalKeyedDimension($dimensionKey, /* onlyFirstValueCombinable: */ true, $prevDimensionKey, $prevDimensionTrueValue, $iterableForTrue, $iterableForFalse);
    }

    /**
     * @param iterable<mixed, mixed> $iterableForTrue
     * @param iterable<mixed, mixed> $iterableForFalse
     *
     * @return $this
     */
    public function addConditionalKeyedDimensionAllValueCombinable(
        string $dimensionKey,
        string $prevDimensionKey,
        mixed $prevDimensionTrueValue,
        iterable $iterableForTrue,
        iterable $iterableForFalse,
    ): self {
        return $this->addConditionalKeyedDimension($dimensionKey, /* onlyFirstValueCombinable: */ false, $prevDimensionKey, $prevDimensionTrueValue, $iterableForTrue, $iterableForFalse);
    }

    /**
     * @return iterable<array<mixed>>
     */
    public function buildWithoutDataSetName(): iterable
    {
        $this->assertValid();
        TestCaseBase::assertNotEmpty($this->generators);

        for ($genIndexForAllValues = 0; $genIndexForAllValues < count($this->generators); ++$genIndexForAllValues) {
            if ($genIndexForAllValues !== 0 && !$this->onlyFirstValueCombinable[$genIndexForAllValues]) {
                continue;
            }
            yield from $this->buildForGenIndex($genIndexForAllValues, /* resultSoFar: */ [], /* currentGenIndex */ 0);
        }
    }

    /**
     * @template TKey of array-key
     * @template TValue
     *
     * @param array<array<TKey, TValue>>|(callable(): iterable<array<TKey, TValue>>) $dataSetsSource
     *
     * @return iterable<string, array<TKey, TValue>>
     */
    public static function keyEachDataSetWithDbgDesc(array|callable $dataSetsSource, ?int $emitOnlyDataSetWithIndex = null): iterable
    {
        if (is_array($dataSetsSource)) {
            $dataSetsCount = IterableUtil::count($dataSetsSource);
            $dataSets = $dataSetsSource;
        } else {
            $dataSetsCount = IterableUtil::count($dataSetsSource());
            $dataSets = $dataSetsSource();
        }

        $dataSetIndex = 0;
        foreach ($dataSets as $dataSet) {
            ++$dataSetIndex;
            if ($emitOnlyDataSetWithIndex !== null && $dataSetIndex !== $emitOnlyDataSetWithIndex) {
                continue;
            }
            yield ('#' . $dataSetIndex . ' out of ' . $dataSetsCount . ': ' . LoggableToString::convert($dataSet)) => $dataSet;
        }
    }

    /**
     * @return $this
     *
     * @noinspection PhpUnused
     */
    public function emitOnlyDataSetWithIndex(int $emitOnlyDataSetWithIndex): self
    {
        $this->emitOnlyDataSetWithIndex = $emitOnlyDataSetWithIndex;
        return $this;
    }

    /**
     * @return iterable<string, array<mixed>>
     */
    public function build(): iterable
    {
        return self::keyEachDataSetWithDbgDesc(fn () => $this->buildWithoutDataSetName(), $this->emitOnlyDataSetWithIndex);
    }

    /**
     * @return callable(): iterable<string, array<mixed>>
     *
     * @noinspection PhpUnused
     */
    public function buildAsMultiUse(): callable
    {
        return fn () => $this->build();
    }

    /**
     * @param iterable<string, array<mixed>> $dataSets
     *
     * @return iterable<string, array{MixedMap}>
     */
    public static function convertEachDataSetToMixedMap(iterable $dataSets): iterable
    {
        foreach ($dataSets as $dbgDataSetName => $dataSet) {
            yield $dbgDataSetName => [new MixedMap(MixedMap::assertValidMixedMapArray($dataSet))];
        }
    }

    /**
     * @param callable(): iterable<array<string, mixed>> $dataSetsGenerator
     *
     * @return iterable<string, array{MixedMap}>
     *
     * @noinspection PhpUnused
     */
    public static function convertEachDataSetToMixedMapAndAddDesc(callable $dataSetsGenerator): iterable
    {
        return self::convertEachDataSetToMixedMap(self::keyEachDataSetWithDbgDesc($dataSetsGenerator));
    }

    /**
     * @return callable(): iterable<int>
     *
     * @noinspection PhpDocSignatureIsNotCompleteInspection
     */
    public static function rangeUpTo(int $count): callable
    {
        /**
         * @return iterable<array<string|int, mixed>>
         */
        return static fn (): iterable => RangeUtil::generateUpTo($count);
    }

    /**
     * @return callable(): iterable<int>
     *
     * @noinspection PhpDocSignatureIsNotCompleteInspection
     * @noinspection PhpUnused
     */
    public static function rangeFromToIncluding(int $first, int $last): callable
    {
        /**
         * @return iterable<array<string|int, mixed>>
         */
        return static fn (): iterable => RangeUtil::generateFromToIncluding($first, $last);
    }
}
