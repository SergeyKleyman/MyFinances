<?php

declare(strict_types=1);

namespace MyFinancesTests\Util;

use ArrayAccess;
use MyFinances\Util\ArrayUtil;
use MyFinances\Util\Log\ContextStack;
use MyFinances\Util\Log\LoggableInterface;
use MyFinances\Util\Log\LogStreamInterface;

/**
 * @implements ArrayAccess<string, mixed>
 */
final class MixedMap implements LoggableInterface, ArrayAccess
{
    /** @var array<string, mixed> */
    private array $map;

    /**
     * @param array<string, mixed> $initialMap
     */
    public function __construct(array $initialMap)
    {
        $this->map = $initialMap;
    }

    /**
     * @param array<mixed> $array
     *
     * @return array<string, mixed>
     */
    public static function assertValidMixedMapArray(array $array): array
    {
        ContextStack::newScope(/* out */ $logCtx, ContextStack::funcArgs());

        foreach ($array as $key => $ignored) {
            TestCaseBase::assertIsString($key);
        }

        /** @phpstan-var array<string, mixed> $array */
        return $array;
    }

    /**
     * @param array<mixed> $from
     */
    public static function getFrom(string $key, array $from): mixed
    {
        TestCaseBase::assertArrayHasKey($key, $from);
        return $from[$key];
    }

    public function get(string $key): mixed
    {
        return self::getFrom($key, $this->map);
    }

    /** @noinspection PhpUnused */
    public function getIfKeyExistsElse(string $key, mixed $fallbackValue): mixed
    {
        return ArrayUtil::getValueIfKeyExistsElse($key, $this->map, $fallbackValue);
    }

    /**
     * @param array<array-key, mixed> $from
     */
    public static function getBoolFrom(string $key, array $from): bool
    {
        ContextStack::newScope(/* out */ $logCtx, ContextStack::funcArgs());
        $value = self::getFrom($key, $from);
        TestCaseBase::assertIsBool($value);
        return $value;
    }

    public function getBool(string $key): bool
    {
        return self::getBoolFrom($key, $this->map);
    }

    /**
     * @param array<array-key, mixed> $from
     */
    public static function getNullableStringFrom(string $key, array $from): ?string
    {
        ContextStack::newScope(/* out */ $logCtx, ContextStack::funcArgs());
        $logCtx->add(['from' => $from]);
        $value = self::getFrom($key, $from);
        if ($value !== null) {
            TestCaseBase::assertIsString($value);
        }
        return $value;
    }

    public function getNullableString(string $key): ?string
    {
        return self::getNullableStringFrom($key, $this->map);
    }

    public function getString(string $key): string
    {
        ContextStack::newScope(/* out */ $logCtx, array_merge(['this' => $this], ContextStack::funcArgs()));
        $value = $this->getNullableString($key);
        TestCaseBase::assertNotNull($value);
        return $value;
    }

    public function getNullableFloat(string $key): ?float
    {
        ContextStack::newScope(/* out */ $logCtx, array_merge(['this' => $this], ContextStack::funcArgs()));
        $value = $this->get($key);
        if ($value === null || is_float($value)) {
            return $value;
        }
        if (is_int($value)) {
            return floatval($value);
        }
        $logCtx->add(['value type' => get_debug_type($value), 'value' => $value]);
        TestCaseBase::fail('Value is not a float');
    }

    /** @noinspection PhpUnused */
    public function getFloat(string $key): float
    {
        ContextStack::newScope(/* out */ $logCtx, array_merge(['this' => $this], ContextStack::funcArgs()));
        $value = $this->getNullableFloat($key);
        TestCaseBase::assertNotNull($value);
        return $value;
    }

    public function getNullableInt(string $key): ?int
    {
        ContextStack::newScope(/* out */ $logCtx, array_merge(['this' => $this], ContextStack::funcArgs()));
        $value = $this->get($key);
        if ($value === null || is_int($value)) {
            return $value;
        }

        $logCtx->add(['value type' => get_debug_type($value), 'value' => $value]);
        TestCaseBase::fail('Value is not a int');
    }

    /**
     * @return null|positive-int|0
     *
     * @noinspection PhpUnused
     */
    public function getNullablePositiveOrZeroInt(string $key): ?int
    {
        ContextStack::newScope(/* out */ $logCtx, array_merge(['this' => $this], ContextStack::funcArgs()));
        $value = $this->getNullableInt($key);
        if ($value !== null) {
            TestCaseBase::assertGreaterThanOrEqual(0, $value);
        }
        /** @phpstan-var null|positive-int|0 $value */
        return $value;
    }

    public function getInt(string $key): int
    {
        ContextStack::newScope(/* out */ $logCtx, array_merge(['this' => $this], ContextStack::funcArgs()));
        $value = $this->getNullableInt($key);
        TestCaseBase::assertNotNull($value);
        return $value;
    }

    /**
     * @return positive-int|0
     *
     * @noinspection PhpUnused
     */
    public function getPositiveOrZeroInt(string $key): int
    {
        ContextStack::newScope(/* out */ $logCtx, array_merge(['this' => $this], ContextStack::funcArgs()));
        $value = $this->getInt($key);
        TestCaseBase::assertGreaterThanOrEqual(0, $value);
        /** @phpstan-var positive-int|0 $value */
        return $value;
    }

    /**
     * @return ?array<array-key, mixed>
     */
    public function getNullableArray(string $key): ?array
    {
        ContextStack::newScope(/* out */ $logCtx, array_merge(['this' => $this], ContextStack::funcArgs()));
        $value = $this->get($key);
        if ($value !== null) {
            TestCaseBase::assertIsArray($value);
        }
        return $value;
    }

    /**
     * @return array<array-key, mixed>
     */
    public function getArray(string $key): array
    {
        $value = $this->getNullableArray($key);
        TestCaseBase::assertNotNull($value);
        return $value;
    }

    public function clone(): self
    {
        return new MixedMap($this->map);
    }

    /**
     * @return array<string, mixed>
     *
     * @noinspection PhpUnused
     */
    public function cloneAsArray(): array
    {
        return $this->map;
    }

    /**
     * @inheritDoc
     *
     * @param string $offset
     */
    public function offsetExists($offset): bool
    {
        return array_key_exists($offset, $this->map);
    }

    /**
     * @inheritDoc
     *
     * @param string $offset
     */
    public function offsetGet($offset): mixed
    {
        return $this->map[$offset];
    }

    /**
     * @inheritDoc
     *
     * @param string $offset
     * @param mixed $value
     */
    public function offsetSet($offset, mixed $value): void
    {
        TestCaseBase::assertNotNull($offset);
        $this->map[$offset] = $value;
    }

    /**
     * @inheritDoc
     *
     * @param string $offset
     */
    public function offsetUnset($offset): void
    {
        TestCaseBase::assertArrayHasKey($offset, $this->map);
        unset($this->map[$offset]);
    }

    public function toLog(LogStreamInterface $stream): void
    {
        $stream->write($this->map);
    }
}
