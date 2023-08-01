<?php

declare(strict_types=1);

namespace MyFinancesTests\UtilTests;

use MyFinances\Util\Log\LoggableToString;
use MyFinances\Util\StaticClassTrait;
use PHPUnit\Framework\Assert;

final class ArrayUtilForTests
{
    use StaticClassTrait;

    /**
     * @template T
     * @param   T[] $array
     * @return  T
     */
    public static function getFirstValue(array $array)
    {
        return $array[array_key_first($array)];
    }

    /**
     * @template T
     *
     * @param array<T> $array
     *
     * @return iterable<T>
     */
    public static function iterateListInReverse(array $array): iterable
    {
        $arrayCount = count($array);
        for ($i = $arrayCount - 1; $i >= 0; --$i) {
            yield $array[$i];
        }
    }

    /**
     * @template TKey of string|int
     * @template TValue
     *
     * @param array<TKey, TValue> $from
     * @param array<TKey, TValue> $to
     */
    public static function append(array $from, /* in,out */ array &$to): void
    {
        $to = array_merge($to, $from);
    }

    /**
     * @template TKey
     * @template TValue
     *
     * @param array<TKey, TValue> $map
     * @param TKey                $keyToFind
     *
     * @return  int
     *
     * @noinspection PhpUnused
     */
    public static function getAdditionOrderIndex(array $map, $keyToFind): int
    {
        $additionOrderIndex = 0;
        foreach ($map as $key => $ignored) {
            if ($key === $keyToFind) {
                return $additionOrderIndex;
            }
            ++$additionOrderIndex;
        }
        Assert::fail('Not found key in map; ' . LoggableToString::convert(['keyToFind' => $keyToFind, 'map' => $map]));
    }

    /**
     * @template TValue
     *
     * @param TValue   $value
     * @param TValue[] $list
     *
     * @noinspection PhpUnused
     */
    public static function addToListIfNotAlreadyPresent($value, array &$list, bool $shouldUseStrictEq = true): void
    {
        if (!in_array($value, $list, /*strict */ $shouldUseStrictEq)) {
            $list[] = $value;
        }
    }
}
