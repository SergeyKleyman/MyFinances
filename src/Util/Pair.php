<?php

declare(strict_types=1);

namespace MyFinances\Util;

/**
 * @template TFirst
 * @template TSecond
 */
final class Pair
{
    /** @var TFirst */
    public $first;

    /** @var TSecond */
    public $second;

    /**
     * @param TFirst $first
     * @param TSecond $second
     */
    public function __construct($first, $second)
    {
        $this->first = $first;
        $this->second = $second;
    }
}
