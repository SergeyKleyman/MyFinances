<?php

declare(strict_types=1);

namespace MyFinances\Util;

final class RangeUtil
{
    use StaticClassTrait;

    /**
     * @return iterable<int>
     */
    public static function generate(int $begin, int $end, int $step = 1): iterable
    {
        for ($i = $begin; $i < $end; $i += $step) {
            yield $i;
        }
    }

    /**
     * @return iterable<int>
     */
    public static function generateDown(int $begin, int $end, int $step = 1): iterable
    {
        for ($i = $begin; $i > $end; $i -= $step) {
            yield $i;
        }
    }

    /**
     * @return iterable<int>
     */
    public static function generateUpTo(int $count): iterable
    {
        return self::generate(0, $count);
    }

    /**
     * @return iterable<int>
     */
    public static function generateFromToIncluding(int $first, int $last): iterable
    {
        return self::generate($first, $last + 1);
    }

    /**
     * @return iterable<int>
     */
    public static function generateDownFrom(int $count): iterable
    {
        for ($i = $count - 1; $i >= 0; --$i) {
            yield $i;
        }
    }
}
