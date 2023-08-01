<?php

declare(strict_types=1);

namespace MyFinances\Util;

trait SingletonInstanceTrait
{
    /**
     * Constructor is hidden because instance() should be used instead
     */
    use HiddenConstructorTrait;

    private static ?self $singletonInstance = null;

    public static function singletonInstance(): static
    {
        if (self::$singletonInstance === null) {
            self::$singletonInstance = new static(); // @phpstan-ignore-line
        }
        return self::$singletonInstance;
    }
}
