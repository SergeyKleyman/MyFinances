<?php

declare(strict_types=1);

namespace MyFinances\Util\ProdAssert;

use JsonSerializable;
use MyFinances\Util\Log\LoggableInterface;
use MyFinances\Util\VerbosityLevelEnumTrait;

enum Level: int implements LoggableInterface, JsonSerializable
{
    use VerbosityLevelEnumTrait;

    case off = 0;
    case O1 = 1;
    case On = 2;
    case On2 = 3;
    case max = 4;

    /** @inheritDoc */
    private function toInt(): int
    {
        return $this->value;
    }

    /** @noinspection PhpUnused */
    public function toDisplayString(): string
    {
        return match ($this) {
            self::off => 'off',
            self::O1  => 'O(1)',
            self::On  => 'O(n)',
            self::On2 => 'O(n^2)',
            self::max => 'max',
        };
    }
}
