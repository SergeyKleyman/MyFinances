<?php

declare(strict_types=1);

namespace MyFinancesTests\UtilTests;

use MyFinances\Util\Log\ContextStack;
use MyFinances\Util\Log\LoggableToString;
use MyFinances\Util\Log\Logger;
use MyFinances\Util\Log\EnabledProxy;
use MyFinances\Util\Log\Level;
use MyFinances\Util\Log\LogStackTraceUtil;
use MyFinances\Util\Log\StdErrWriter;
use MyFinances\Util\RangeUtil;
use MyFinancesTests\Util\IterableUtilForTests;
use MyFinancesTests\Util\MockSink;
use MyFinancesTests\Util\TestCaseBase;

use function count;

final class LogTest extends TestCaseBase
{
    public function testLevelIsBelowThreshold(): void
    {
        ContextStack::newScope(/* out */ $logCtx);

        $levels = Level::cases();

        foreach (RangeUtil::generateUpTo(count($levels)) as $i) {
            $logCtx->pushSubScope();
            foreach (RangeUtil::generateUpTo(count($levels)) as $j) {
                $logCtx->clearCurrentSubScope(['i' => $i, 'j' => $j, 'levels[i]' => $levels[$i], 'levels[j]' => $levels[$j]]);
                self::assertSame($levels[$i]->isBelowThreshold($levels[$j]), $i <= $j);
            }
            $logCtx->popSubScope();
        }
    }

    /**
     * @return iterable<array{Level, bool, Level, ?bool}>
     */
    public static function dataProviderForTestLogByLevel(): iterable
    {
        foreach (Level::cases() as $levelThreshold) {
            foreach (IterableUtilForTests::ALL_BOOL_VALUES as $useGeneric) {
                foreach (Level::statementLevels() as $statementLevel) {
                    foreach ([true, false, null] as $includeStackTrace) {
                        yield [$levelThreshold, $useGeneric, $statementLevel, $includeStackTrace];
                    }
                }
            }
        }
    }

    /**
     * @param array<array-key, mixed> $context
     *
     * @return array{string, int, int}
     */
    private static function prodCodeFunc(bool $useGeneric, Level $statementLevel, string $message, ?bool $includeStackTrace, array $context): array
    {
        static $logger = new Logger(srcCodeNamespace: __NAMESPACE__, srcCodeClass: __CLASS__, srcCodeFunc: __FUNCTION__, srcCodeFile: __FILE__);

        $condIncludeStackTrace = static function (?EnabledProxy $logEnabled) use ($includeStackTrace): ?EnabledProxy {
            if ($logEnabled === null || $includeStackTrace === null) {
                return $logEnabled;
            }
            return $logEnabled->includeStackTrace($includeStackTrace);
        };

        $lineRangeBegin = __LINE__ + 1;
        if ($useGeneric) {
            $condIncludeStackTrace($logger->ifLevelEnabled($statementLevel))?->log(__LINE__, $message, $context);
        } else {
            match ($statementLevel) {
                Level::critical => $condIncludeStackTrace($logger->critical())?->log(__LINE__, $message, $context),
                Level::error => $condIncludeStackTrace($logger->error())?->log(__LINE__, $message, $context),
                Level::warning => $condIncludeStackTrace($logger->warning())?->log(__LINE__, $message, $context),
                Level::info => $condIncludeStackTrace($logger->info())?->log(__LINE__, $message, $context),
                Level::debug => $condIncludeStackTrace($logger->debug())?->log(__LINE__, $message, $context),
                Level::trace => $condIncludeStackTrace($logger->trace())?->log(__LINE__, $message, $context),
                default => self::fail(LoggableToString::convert($context)),
            };
        }
        $lineRangeEnd = __LINE__;

        return [__FUNCTION__, $lineRangeBegin, $lineRangeEnd];
    }

    /**
     * @param array<array-key, mixed> $context
     *
     * @return array{string, int, int}
     */
    private static function prodCodeFuncHelper(
        MockSink $mockLogSink,
        Level $levelThreshold,
        bool $useGeneric,
        Level $statementLevel,
        string $message,
        ?bool $includeStackTrace,
        array $context,
    ): array {
        return MockSink::runAndRestore(
            static fn () => self::prodCodeFunc($useGeneric, $statementLevel, $message, $includeStackTrace, $context),
            $mockLogSink,
            $levelThreshold
        );
    }

    /**
     * @dataProvider dataProviderForTestLogByLevel
     */
    public function testLogByLevel(Level $levelThreshold, bool $useGeneric, Level $statementLevel, ?bool $includeStackTrace): void
    {
        ContextStack::newScope(/* out */ $logCtx, $context = ContextStack::funcArgs());

        $mockLogSink = new MockSink();
        $message = 'My log message';
        $expectedToLog = $statementLevel->isBelowThreshold($levelThreshold);
        $logCtx->add(compact('expectedToLog'));
        $expectedIncludeStackTrace = $includeStackTrace || $statementLevel->isBelowThreshold(Level::error);
        $logCtx->add(compact('expectedIncludeStackTrace'));

        [$func, $lineRangeBegin, $lineRangeEnd] = self::prodCodeFuncHelper($mockLogSink, $levelThreshold, $useGeneric, $statementLevel, $message, $includeStackTrace, $context);

        self::assertCount($expectedToLog ? 1 : 0, $mockLogSink->statements);
        if (!$expectedToLog) {
            return;
        }

        $statement = $mockLogSink->statements[0];
        self::assertSame($statementLevel, $statement->level);
        self::assertSame($message, $statement->message);
        self::assertMapIsSubsetOf($context, $statement->context);
        self::assertSame(__NAMESPACE__, $statement->srcCodeNamespace);
        self::assertSame(__CLASS__, $statement->srcCodeClass);
        self::assertSame($func, $statement->srcCodeFunc);
        self::assertSame(__FILE__, $statement->srcCodeFile);
        self::assertGreaterThanOrEqual($lineRangeBegin, $statement->srcCodeLine);
        self::assertLessThan($lineRangeEnd, $statement->srcCodeLine);

        if ($expectedIncludeStackTrace) {
            self::assertArrayHasKey(LogStackTraceUtil::STACK_TRACE_KEY, $statement->context);
        } else {
            self::assertArrayNotHasKey(LogStackTraceUtil::STACK_TRACE_KEY, $statement->context);
        }

        $closure = static function (): void {
            StdErrWriter::singletonInstance()->write('__NAMESPACE__: ' . __NAMESPACE__ . ' | ' . '__CLASS__: ' . __CLASS__ . ' | ' . '__FUNCTION__: ' . __FUNCTION__ . PHP_EOL);
        };
        $closure();
    }
}
