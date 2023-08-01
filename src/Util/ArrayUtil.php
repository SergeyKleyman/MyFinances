<?php

declare(strict_types=1);

namespace MyFinances\Util;

final class ArrayUtil
{
    use StaticClassTrait;

    /**
     * @param array<array-key, mixed>  $array
     *
     * @template        T
     * @phpstan-param   T[] $array
     * @phpstan-param   T   $valueDst
     *
     * @noinspection    PhpUnused
     */
    public static function getValueIfKeyExists(string $key, array $array, mixed &$valueDst): bool
    {
        if (!array_key_exists($key, $array)) {
            return false;
        }

        $valueDst = $array[$key];
        return true;
    }

    /**
     * @template TKey of string|int
     * @template TValue
     *
     * @param TKey                $key
     * @param array<TKey, TValue> $array
     * @param TValue              $fallbackValue
     *
     * @return TValue
     */
    public static function getValueIfKeyExistsElse(mixed $key, array $array, $fallbackValue)
    {
        return array_key_exists($key, $array) ? $array[$key] : $fallbackValue;
    }

    /**
     * @param array<array-key, mixed> $array
     *
     * @noinspection PhpUnused
     */
    public static function getStringValueIfKeyExistsElse(string|int $key, array $array, string $fallbackValue): string
    {
        if (!array_key_exists($key, $array)) {
            return $fallbackValue;
        }

        $value = $array[$key];

        if (!is_string($value)) {
            return $fallbackValue;
        }

        return $value;
    }

    /**
     * @param array<array-key, mixed> $array
     *
     * @noinspection PhpUnused
     */
    public static function getNullableStringValueIfKeyExistsElse(string|int $key, array $array, ?string $fallbackValue): ?string
    {
        if (!array_key_exists($key, $array)) {
            return $fallbackValue;
        }

        $value = $array[$key];

        if (!is_string($value)) {
            return $fallbackValue;
        }

        return $value;
    }

    /**
     * @param array<array-key, mixed> $array
     *
     * @noinspection PhpUnused
     */
    public static function getIntValueIfKeyExistsElse(string|int $key, array $array, int $fallbackValue): int
    {
        if (!array_key_exists($key, $array)) {
            return $fallbackValue;
        }

        $value = $array[$key];

        if (!is_int($value)) {
            return $fallbackValue;
        }

        return $value;
    }

    /**
     * @param array<array-key, mixed> $array
     *
     * @noinspection PhpUnused
     */
    public static function getNullableIntValueIfKeyExistsElse(string|int $key, array $array, ?int $fallbackValue): ?int
    {
        if (!array_key_exists($key, $array)) {
            return $fallbackValue;
        }

        $value = $array[$key];

        if (!is_int($value)) {
            return $fallbackValue;
        }

        return $value;
    }

    /**
     * @template TKey of array-key
     * @template TValue
     *
     * @phpstan-param TKey                $key
     * @phpstan-param TValue              $defaultValue
     * @phpstan-param array<TKey, TValue> $array
     *
     * @phpstan-return TValue
     *
     * @noinspection PhpUnused
     */
    public static function &getOrAdd(string|int $key, $defaultValue, array &$array): mixed
    {
        if (!array_key_exists($key, $array)) {
            $array[$key] = $defaultValue;
        }

        return $array[$key];
    }

    /**
     * @param array<mixed> $array
     *
     * @return bool
     */
    public static function isEmpty(array $array): bool
    {
        return count($array) === 0;
    }

    /**
     * @param array<mixed> $array
     *
     * @return bool
     */
    public static function isList(array $array): bool
    {
        $expectedKey = 0;
        foreach ($array as $key => $_) {
            if ($key !== $expectedKey) {
                return false;
            }
            ++$expectedKey;
        }
        return true;
    }

    /**
     * @param array<array-key, mixed>  $srcArray
     * @param array<array-key, mixed> &$dstArray
     *
     * @noinspection PhpUnused
     */
    public static function copyByArrayKeyIfExists(array $srcArray, string $key, /* ref */ array &$dstArray): void
    {
        if (array_key_exists($key, $srcArray)) {
            $dstArray[$key] = $srcArray[$key];
        }
    }

    /**
     * @param array<array-key, mixed> $array
     *
     * @noinspection PhpUnused
     */
    public static function removeKeyIfExists(string $key, /* ref */ array &$array): void
    {
        if (array_key_exists($key, $array)) {
            unset($array[$key]);
        }
    }

    /**
     * @template TKey of string|int
     * @template TValue
     *
     * @param array<TKey, TValue> $from
     * @param array<TKey, TValue> $to
     */
    public static function append(array $from, /* in,out */ array &$to): void
    {
        $to = array_merge($to, $from);
    }
}
