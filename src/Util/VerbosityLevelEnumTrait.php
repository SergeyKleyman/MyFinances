<?php

declare(strict_types=1);

namespace MyFinances\Util;

use MyFinances\Util\Log\LoggableEnumTrait;

trait VerbosityLevelEnumTrait
{
    use JsonSerializableEnumTrait;
    use LoggableEnumTrait;

    /**
     * Dummy comment to use inheritDoc in the implementing classes
     */
    abstract private function toInt(): int;

    public function isBelowThreshold(self $levelThreshold): bool
    {
        return $this->toInt() <= $levelThreshold->toInt();
    }

    public function isForStatement(): bool
    {
        return $this !== self::off && $this !== self::max;
    }

    /**
     * @return self[]
     */
    public static function statementLevels(): array
    {
        /** @var null|(self[]) $result */
        static $result = null;
        if ($result === null) {
            $result = [];
            foreach (self::cases() as $level) {
                if ($level->isForStatement()) {
                    $result[] = $level;
                }
            }
        }
        return $result;
    }
}
