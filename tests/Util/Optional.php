<?php

declare(strict_types=1);

namespace MyFinancesTests\Util;

use MyFinances\Util\Log\LoggableInterface;
use MyFinances\Util\Log\LogStreamInterface;
use PHPUnit\Framework\Assert;

/**
 * @template T
 */
final class Optional implements LoggableInterface
{
    private bool $isValueSet = false;

    /** @var T */
    private mixed $value;

    /**
     * @return T
     */
    public function getValue()
    {
        Assert::assertTrue($this->isValueSet);
        return $this->value;
    }

    /**
     * @param T $value
     */
    public function setValue($value): void
    {
        $this->value = $value;
        $this->isValueSet = true;
    }

    /**
     * @param T $elseValue
     *
     * @return T
     *
     * @noinspection PhpUnused
     */
    public function getValueOr($elseValue)
    {
        return $this->isValueSet ? $this->value : $elseValue;
    }

    public function reset(): void
    {
        $this->isValueSet = false;
        unset($this->value);
    }

    public function isValueSet(): bool
    {
        return $this->isValueSet;
    }

    /**
     * @param T $value
     *
     * @noinspection PhpUnused
     */
    public function setValueIfNotSet($value): void
    {
        if (!$this->isValueSet) {
            $this->setValue($value);
        }
    }

    public function toLog(LogStreamInterface $stream): void
    {
        $stream->write($this->isValueSet ? $this->value : /** @lang text */ '<Optional NOT SET>');
    }
}
