<?php

declare(strict_types=1);

namespace MyFinancesTests\Util;

use Generator;
use Iterator;
use MyFinances\Util\ArrayUtil;
use MyFinances\Util\StaticClassTrait;
use PHPUnit\Framework\TestCase;

use function is_array;

final class IterableUtilForTests
{
    use StaticClassTrait;

    public const ALL_BOOL_VALUES = [true, false];

    /**
     * @param iterable<mixed> $iterable
     */
    public static function isEmpty(iterable $iterable): bool
    {
        /** @noinspection PhpLoopNeverIteratesInspection */
        foreach ($iterable as $ignored) {
            return false;
        }
        return true;
    }

    /**
     * @template TValue
     *
     * @param iterable<TValue> $iterable
     *
     * @return array<TValue>
     */
    public static function toList(iterable $iterable): array
    {
        if (is_array($iterable)) {
            return $iterable;
        }

        $result = [];
        foreach ($iterable as $val) {
            $result[] = $val;
        }
        return $result;
    }

    /**
     * @param iterable<mixed> $iterable
     */
    public static function getFirstValue(iterable $iterable, /* out */ mixed &$valOut): bool
    {
        return self::getNthValue($iterable, /* n: */ 0, /* out */ $valOut);
    }

    /**
     * @template TValue
     *
     * @param iterable<mixed>  $iterable
     * @param TValue          &$valOut
     */
    public static function getNthValue(iterable $iterable, int $n, /* out */ &$valOut): bool
    {
        $i = 0;
        foreach ($iterable as $val) {
            if ($i === $n) {
                $valOut = $val;
                return true;
            }
            ++$i;
        }
        return false;
    }

    /**
     * @param iterable<mixed, mixed> $iterable
     *
     * @return iterable<mixed, mixed>
     */
    public static function skipFirst(iterable $iterable): iterable
    {
        $isFirst = true;
        foreach ($iterable as $key => $val) {
            if ($isFirst) {
                $isFirst = false;
                continue;
            }
            yield $key => $val;
        }
    }

    /**
     * @template T
     *
     * @param iterable<T> $inputIterable
     *
     * @return Generator<T>
     */
    public static function iterableToGenerator(iterable $inputIterable): Generator
    {
        foreach ($inputIterable as $val) {
            yield $val;
        }
    }

    /**
     * @template T
     *
     * @param iterable<T> $inputIterable
     *
     * @return Iterator<T>
     */
    public static function iterableToIterator(iterable $inputIterable): Iterator
    {
        if ($inputIterable instanceof Iterator) {
            return $inputIterable;
        }

        return self::iterableToGenerator($inputIterable);
    }

    /**
     * @param iterable<mixed> $iterables
     *
     * @return Generator<mixed[]>
     */
    public static function zip(iterable ...$iterables): Generator
    {
        if (ArrayUtil::isEmpty($iterables)) {
            return;
        }

        /** @var Iterator<mixed>[] $iterators */
        $iterators = [];
        foreach ($iterables as $inputIterable) {
            $iterator = self::iterableToIterator($inputIterable);
            $iterator->rewind();
            $iterators[] = $iterator;
        }

        while (true) {
            $tuple = [];
            foreach ($iterators as $iterator) {
                if ($iterator->valid()) {
                    $tuple[] = $iterator->current();
                    $iterator->next();
                } else {
                    TestCase::assertTrue(ArrayUtil::isEmpty($tuple));
                }
            }

            if (ArrayUtil::isEmpty($tuple)) {
                return;
            }

            TestCase::assertSame(count($iterables), count($tuple));
            yield $tuple;
        }
    }
}
