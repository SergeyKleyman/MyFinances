<?php

declare(strict_types=1);

namespace MyFinancesTests\UtilTests;

use MyFinances\Util\Log\ContextStack;
use MyFinances\Util\Log\LoggableToString;
use MyFinances\Util\ProdAssert\ProdAssert;
use MyFinances\Util\ProdAssert\Backend;
use MyFinances\Util\ProdAssert\ProdAssertionFailedException;
use MyFinances\Util\ProdAssert\Level;
use MyFinances\Util\RangeUtil;
use MyFinancesTests\Util\IterableUtilForTests;
use MyFinancesTests\Util\TestCaseBase;

use function count;

final class ProdAssertTest extends TestCaseBase
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
     * @return iterable<array{Level, bool, Level, bool}>
     */
    public static function dataProviderForTestProdAssert(): iterable
    {
        foreach (Level::cases() as $levelThreshold) {
            foreach (IterableUtilForTests::ALL_BOOL_VALUES as $useGeneric) {
                foreach (Level::statementLevels() as $statementLevel) {
                    foreach (IterableUtilForTests::ALL_BOOL_VALUES as $condition) {
                        yield [$levelThreshold, $useGeneric, $statementLevel, $condition];
                    }
                }
            }
        }
    }

    private static function prodCodeFunc(bool $useGeneric, Level $statementLevel, bool $condition): void
    {
        static $assert = new ProdAssert(__NAMESPACE__, __CLASS__, __FUNCTION__);

        /** @var array<array-key, mixed> $context */
        $context = compact('useGeneric', 'statementLevel', 'condition');

        if ($useGeneric) {
            $assert->ifLevelEnabled($statementLevel)?->that($condition, $context);
        } else {
            match ($statementLevel) {
                Level::O1 => $assert->o1()?->that($condition, $context),
                Level::On => $assert->oN()?->that($condition, $context),
                Level::On2 => $assert->oN2()?->that($condition, $context),
                default => self::fail(LoggableToString::convert($context)),
            };
        }
    }

    /**
     * @dataProvider dataProviderForTestProdAssert
     */
    public function testProdAssert(Level $levelThreshold, bool $useGeneric, Level $statementLevel, bool $condition): void
    {
        Backend::singletonInstance()->setLevelThreshold($levelThreshold);

        $shouldThrow = !$condition && $statementLevel->isBelowThreshold($levelThreshold);
        $logCtx = LoggableToString::convert(compact('levelThreshold', 'useGeneric', 'statementLevel', 'condition'));
        try {
            self::prodCodeFunc($useGeneric, $statementLevel, $condition);
            self::assertTrue(!$shouldThrow, $logCtx);
        } catch (ProdAssertionFailedException $e) {
            self::assertTrue($shouldThrow, $logCtx);
            self::assertStringContainsString(LoggableToString::convert(compact('useGeneric', 'statementLevel', 'condition')), $e->getMessage());
        }

        self::dummyAssert();
    }
}
