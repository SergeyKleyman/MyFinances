<?php

declare(strict_types=1);

namespace MyFinancesTests\Util;

use MyFinances\Util\Log\Backend;
use MyFinances\Util\Log\Level;
use MyFinances\Util\Log\SinkInterface;
use MyFinances\Util\Log\Record;

final class MockSink implements SinkInterface
{
    /** @var Record[] */
    public array $statements = [];

    /** @inheritDoc */
    public function consume(Record $statement): void
    {
        $this->statements[] = $statement;
    }

    /**
     * @template TReturn
     *
     * @param callable(): TReturn $callableToRun
     *
     * @return TReturn
     *
     * @noinspection PhpDocSignatureIsNotCompleteInspection
     */
    public static function runAndRestore(callable $callableToRun, ?SinkInterface $mockLogSink = null, ?Level $mockLevelThreshold = null): mixed
    {
        $savedLevelThreshold = $mockLevelThreshold === null ? null : Backend::singletonInstance()->getLevelThreshold();
        $savedSink = $mockLogSink === null ? null : Backend::singletonInstance()->getSink();

        try {
            if ($mockLevelThreshold !== null) {
                Backend::singletonInstance()->setLevelThreshold($mockLevelThreshold);
            }
            if ($mockLogSink !== null) {
                Backend::singletonInstance()->setSink($mockLogSink);
            }
            return $callableToRun();
        } finally {
            if ($savedSink !== null) {
                Backend::singletonInstance()->setSink($savedSink);
            }
            if ($savedLevelThreshold !== null) {
                Backend::singletonInstance()->setLevelThreshold($savedLevelThreshold);
            }
        }
    }
}
