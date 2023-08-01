<?php

declare(strict_types=1);

namespace MyFinances\Util;

use Countable;

final class IterableUtil
{
    use StaticClassTrait;

    /**
     * @template TValue
     *
     * @param TValue[] $inArray
     *
     * @return iterable<TValue>
     */
    public static function arraySuffix(array $inArray, int $suffixStartIndex): iterable
    {
        foreach (RangeUtil::generateFromToIncluding($suffixStartIndex, count($inArray) - 1) as $index) {
            yield $inArray[$index];
        }
    }

    /**
     * @param iterable<mixed> $iterable
     */
    public static function count(iterable $iterable): int
    {
        if ($iterable instanceof Countable) {
            return count($iterable);
        }

        $result = 0;
        foreach ($iterable as $ignored) {
            ++$result;
        }
        return $result;
    }
}
