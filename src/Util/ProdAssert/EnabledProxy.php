<?php

declare(strict_types=1);

namespace MyFinances\Util\ProdAssert;

use Countable;
use MyFinances\Util\Log\ContextUtil;
use MyFinances\Util\Log\LoggableToString;
use MyFinances\Util\SingletonInstanceTrait;

use function compact;
use function count;

final class EnabledProxy
{
    use SingletonInstanceTrait;

    /**
     * @param array<array-key, mixed> $localCtx
     * @param array<array-key, mixed> $externalCtx
     */
    private static function buildAssertionFailedException(array $localCtx, array $externalCtx = []): ProdAssertionFailedException
    {
        return new ProdAssertionFailedException(LoggableToString::convert(ContextUtil::merge($localCtx, $externalCtx)));
    }

    /**
     * @param array<array-key, mixed> $context
     *
     * @phpstan-assert true $condition
     */
    public static function that(bool $condition, array $context = []): void
    {
        if ($condition) {
            return;
        }

        throw self::buildAssertionFailedException($context);
    }

    /**
     * @param array<array-key, mixed> $context
     *
     * @noinspection PhpUnused
     */
    public static function lessThanOrEqual(int $expected, int $actual, array $context = []): void
    {
        if ($actual <= $expected) {
            return;
        }

        throw self::buildAssertionFailedException(compact('expected', 'actual'), $context);
    }

    /**
     * @param array<array-key, mixed> $context
     *
     * @noinspection PhpUnused
     */
    public static function greaterThanOrEqual(int $expected, int $actual, array $context = []): void
    {
        if ($actual >= $expected) {
            return;
        }

        throw self::buildAssertionFailedException(compact('expected', 'actual'), $context);
    }

    /**
     * @param array<array-key, mixed> $context
     *
     * @phpstan-assert null $actual
     */
    public function isNull(mixed $actual, array $context = []): void
    {
        if ($actual === null) {
            return;
        }

        throw self::buildAssertionFailedException(compact('actual'), $context);
    }

    /**
     * @param array<array-key, mixed> $context
     */
    public function isNotNull(mixed $actual, array $context = []): void
    {
        if ($actual !== null) {
            return;
        }

        throw self::buildAssertionFailedException(compact('actual'), $context);
    }

    /**
     * @param array<array-key, mixed>|Countable $actual
     * @param array<array-key, mixed>           $context
     */
    public function hasCount(int $expected, array|Countable $actual, array $context = []): void
    {
        if (count($actual) === $expected) {
            return;
        }

        throw self::buildAssertionFailedException(compact('expected', 'actual'), $context);
    }

    /**
     * @param array<array-key, mixed>|Countable $actual
     * @param array<array-key, mixed>           $context
     */
    public function isNotEmpty(array|Countable $actual, array $context = []): void
    {
        if (count($actual) !== 0) {
            return;
        }

        throw self::buildAssertionFailedException(compact('actual'), $context);
    }

    /**
     * @param array<array-key, mixed> $context
     */
    public function same(mixed $expected, mixed $actual, array $context = []): void
    {
        if ($actual === $expected) {
            return;
        }

        throw self::buildAssertionFailedException(compact('expected', 'actual'), $context);
    }

    /**
     * @param array<array-key, mixed> $context
     *
     * @return never
     *
     * @phpstan-return never-returns
     *
     * @noinspection PhpUnused
     */
    public function unreachable(array $context = []): never
    {
        throw self::buildAssertionFailedException($context);
    }
}
