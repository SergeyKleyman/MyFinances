<?php

declare(strict_types=1);

namespace MyFinances\Util\Log;

use MyFinances\Util\ArrayUtil;
use MyFinances\Util\Pair;
use PHPUnit\Framework\Assert;

final class ContextStackScopeAutoRef
{
    private ContextStack $stack;

    private ?ContextStackScopeData $data;

    public function __construct(ContextStack $stack, ?ContextStackScopeData $data)
    {
        $this->stack = $stack;
        $this->data = $data;
    }

    public function __destruct()
    {
        if ($this->data === null) {
            return;
        }

        $this->stack->autoPopScope($this->data);
    }

    /**
     * @param array<string, mixed> $ctx
     */
    public function add(array $ctx): void
    {
        if ($this->data === null) {
            return;
        }

        ArrayUtil::append(/* from */ $ctx, /* to */ $this->data->subScopesStack[count($this->data->subScopesStack) - 1]->second);
    }

    /**
     * @param array<string, mixed> $initialCtx
     */
    public function pushSubScope(array $initialCtx = []): void
    {
        if ($this->data === null) {
            return;
        }

        Assert::assertGreaterThanOrEqual(1, count($this->data->subScopesStack));
        $this->data->subScopesStack[] = new Pair(ContextStackScopeData::buildContextName(/* numberOfStackFramesToSkip */ 1), $initialCtx);
        Assert::assertGreaterThanOrEqual(2, count($this->data->subScopesStack));
    }

    /**
     * @param array<string, mixed> $initialCtx
     */
    public function clearCurrentSubScope(array $initialCtx = []): void
    {
        if ($this->data === null) {
            return;
        }

        Assert::assertGreaterThanOrEqual(2, count($this->data->subScopesStack));
        $this->data->subScopesStack[count($this->data->subScopesStack) - 1]->second = $initialCtx;
    }

    public function popSubScope(): void
    {
        if ($this->data === null) {
            return;
        }

        Assert::assertGreaterThanOrEqual(2, count($this->data->subScopesStack));
        array_pop(/* ref */ $this->data->subScopesStack);
        Assert::assertGreaterThanOrEqual(1, count($this->data->subScopesStack));
    }
}
