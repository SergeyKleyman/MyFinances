<?php

declare(strict_types=1);

namespace MyFinances\Util\Log;

use MyFinances\Util\VerbosityLevelEnumTrait;
use MyFinances\Util\ProdAssert\ProdAssert;
use JsonSerializable;

enum Level: int implements LoggableInterface, JsonSerializable
{
    use VerbosityLevelEnumTrait;

    case off = 0;
    case critical = 1;
    case error = 2;
    case warning = 3;
    case info = 4;
    case debug = 5;
    case trace = 6;
    case max = 7;

    /** @inheritDoc */
    private function toInt(): int
    {
        return $this->value;
    }

    public function toDisplayString(): string
    {
        return strtoupper($this->name);
    }

    public function toSyslogPriority(): int
    {
        static $assert = new ProdAssert(__NAMESPACE__, __CLASS__, __FUNCTION__);
        $assert->o1()?->that($this->isForStatement(), compact('this'));

        /** @noinspection PhpDuplicateMatchArmBodyInspection */
        return match ($this) {
            self::critical => LOG_CRIT,
            self::error => LOG_ERR,
            self::warning => LOG_WARNING,
            self::info => LOG_INFO,
            self::debug, self::trace => LOG_DEBUG,
            default => LOG_CRIT
        };
    }
}
