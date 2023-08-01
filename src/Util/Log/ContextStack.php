<?php

declare(strict_types=1);

namespace MyFinances\Util\Log;

use MyFinances\Util\StackTraceFrame;
use MyFinances\Util\IterableUtil;
use MyFinances\Util\ProdAssert\ProdAssert;
use MyFinances\Util\RangeUtil;
use MyFinances\Util\SingletonInstanceTrait;
use MyFinances\Util\StackTraceUtil;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;

use function count;

final class ContextStack implements LoggableInterface
{
    use SingletonInstanceTrait;

    private bool $isEnabled = true;

    /** @var ContextStackScopeData[] */
    private array $scopesStack = [];

    /**
     * @noinspection PhpUnused
     */
    public static function setEnabled(bool $isEnabled): void
    {
        self::singletonInstance()->isEnabled = $isEnabled;
    }

    /**
     * @param array<array-key, mixed> $initialCtx
     *
     * @phpstan-param 0|positive-int $numberOfStackFramesToSkip
     */
    private function newScopeImpl(/* out */ ?ContextStackScopeAutoRef &$scopeVar, array $initialCtx, int $numberOfStackFramesToSkip): void
    {
        static $assert = new ProdAssert(__NAMESPACE__, __CLASS__, __FUNCTION__);
        $assert->o1()?->isNull($scopeVar);

        if (!$this->isEnabled) {
            $scopeVar = new ContextStackScopeAutoRef($this, null);
            return;
        }

        $newScopeData = new ContextStackScopeData(ContextStackScopeData::buildContextName($numberOfStackFramesToSkip + 1), $initialCtx);
        $newScope = new ContextStackScopeAutoRef($this, $newScopeData);
        $this->scopesStack[] = $newScopeData;
        $scopeVar = $newScope;
    }

    /**
     * @param array<array-key, mixed>      $initialCtx
     *
     * @param-out ContextStackScopeAutoRef $scopeVar
     */
    public static function newScope(/* out */ ?ContextStackScopeAutoRef &$scopeVar, array $initialCtx = []): void
    {
        self::singletonInstance()->newScopeImpl(/* out */ $scopeVar, $initialCtx, /* numberOfStackFramesToSkip */ 1);
    }

    /**
     * @return null|(\ReflectionParameter[])
     */
    private static function getReflectionParametersForStackFrame(StackTraceFrame $frame): ?array
    {
        if ($frame->function === null) {
            return null;
        }

        try {
            if ($frame->class === null) {
                $reflFunc = new ReflectionFunction($frame->function);
                return $reflFunc->getParameters();
            }
            /** @var class-string $className */
            $className = $frame->class;
            $reflClass = new ReflectionClass($className);
            $reflMethod = $reflClass->getMethod($frame->function);
            return $reflMethod->getParameters();
        } catch (ReflectionException) {
            return null;
        }
    }

    /**
     * @return array<string, mixed>
     */
    public static function funcArgs(): array
    {
        static $assert = new ProdAssert(__NAMESPACE__, __CLASS__, __FUNCTION__);

        $result = [];
        $frames = StackTraceUtil::capture(offset: 1, maxNumberOfFrames: 1, includeArgs: true);
        $assert->o1()?->hasCount(1, $frames);
        $frame = $frames[0];
        $assert->o1()?->isNotNull($frame->args);
        // TODO: Sergey Kleyman: Remove assert
        assert($frame->args !== null);
        /** @var null|(\ReflectionParameter[]) $reflParams */
        $reflParams = self::getReflectionParametersForStackFrame($frame);
        foreach (RangeUtil::generateUpTo(count($frame->args)) as $argIndex) {
            $argName = $reflParams === null || count($reflParams) <= $argIndex ? ('arg #' . ($argIndex + 1)) : $reflParams[$argIndex]->getName();
            $result[$argName] = $frame->args[$argIndex];
        }
        return $result;
    }

    public function autoPopScope(ContextStackScopeData $expectedTopData): void
    {
        static $assert = new ProdAssert(__NAMESPACE__, __CLASS__, __FUNCTION__);
        $logCtx = ['this' => $this, 'expectedTopData' => $expectedTopData];
        $assert->o1()?->isNotEmpty($this->scopesStack, $logCtx);
        $actualTopData = $this->scopesStack[count($this->scopesStack) - 1];
        $assert->o1()?->same($expectedTopData, $actualTopData, $logCtx);
        array_pop(/* ref */ $this->scopesStack);
    }

    /**
     * @return iterable<\MyFinances\Util\Pair<string, array<string, mixed>>>
     *
     * @noinspection PhpFullyQualifiedNameUsageInspection
     */
    private function getContextsStackAsNameCtxPairs(): iterable
    {
        $result = [];
        foreach (RangeUtil::generateDownFrom(count($this->scopesStack)) as $scopeIndex) {
            $scopeData = $this->scopesStack[$scopeIndex];
            foreach (RangeUtil::generateDownFrom(count($scopeData->subScopesStack)) as $subScopeIndex) {
                $result[] = $scopeData->subScopesStack[$subScopeIndex];
            }
        }
        return $result;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getContextsStack(): array
    {
        if (!$this->isEnabled) {
            return [];
        }

        $totalCount =  IterableUtil::count($this->getContextsStackAsNameCtxPairs());
        $result = [];
        $totalIndex = 1;
        foreach ($this->getContextsStackAsNameCtxPairs() as $nameCtxPair) {
            $result[($totalIndex++) . ' out of ' . $totalCount . ': ' . $nameCtxPair->first] = $nameCtxPair->second;
        }
        return $result;
    }

    public function toLog(LogStreamInterface $stream): void
    {
        $asArray = ['scopesStack count' => count($this->scopesStack)];
        if ($this->isEnabled) {
            $asArray = array_merge(['isEnabled' => $this->isEnabled], $asArray);
        }
        $stream->write($asArray);
    }
}
