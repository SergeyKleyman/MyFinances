<?php

declare(strict_types=1);

namespace MyFinancesTests\Util;

use MyFinances\Util\Log\LoggableInterface;
use MyFinances\Util\Log\LoggableTrait;

abstract class ExpectationsBase implements LoggableInterface
{
    use LoggableTrait;

    public function isEmpty(): bool
    {
        return self::isEmptyImpl($this);
    }

    private static function isEmptyImpl(mixed $val): bool
    {
        if ($val === null) {
            return true;
        }

        if ($val instanceof Optional) {
            if (!$val->isValueSet()) {
                return true;
            }
            return self::isEmptyImpl($val->getValue());
        }

        if (is_object($val)) {
            foreach (get_object_vars($val) as $propVal) {
                if (!self::isEmptyImpl($propVal)) {
                    return false;
                }
            }
        }

        return true;
    }

    /** @noinspection PhpUnused */
    protected static function setCommonProperties(object $src, object $dst): int
    {
        $count = 0;
        foreach (get_object_vars($src) as $propName => $propValue) {
            if (!property_exists($dst, $propName)) {
                continue;
            }
            $dst->$propName = $propValue;
            ++$count;
        }
        return $count;
    }

    /**
     * @param Optional<?static> $expectationsOpt
     *
     * @noinspection PhpDocSignatureIsNotCompleteInspection
     * @noinspection PhpUnused
     */
    public static function assertNullableMatches(Optional $expectationsOpt, ?object $actual): void
    {
        if (!$expectationsOpt->isValueSet()) {
            if ($actual === null) {
                return;
            }

            /** @phpstan-ignore-next-line  */
            $actual->assertMatches(new static());
            return;
        }

        if (($expectations = $expectationsOpt->getValue()) === null) {
            TestCaseBase::assertNull($actual);
            return;
        }

        TestCaseBase::assertNotNull($actual);
        /** @phpstan-ignore-next-line  */
        $actual->assertMatches($expectations);
    }
}
