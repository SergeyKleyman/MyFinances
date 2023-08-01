<?php

declare(strict_types=1);

namespace MyFinances\Util\Log;

use MyFinances\Util\DbgUtil;
use MyFinances\Util\Pair;
use MyFinances\Util\TextUtil;
use PHPUnit\Framework\Assert;

final class ContextStackScopeData implements LoggableInterface
{
    /** @var Pair<string, array<string, mixed>>[] */
    public array $subScopesStack;

    /**
     * @param array<string, mixed> $initialCtx
     */
    public function __construct(string $name, array $initialCtx)
    {
        $this->subScopesStack = [new Pair($name, $initialCtx)];
    }

    /**
     * @phpstan-param 0|positive-int $numberOfStackFramesToSkip
     */
    public static function buildContextName(int $numberOfStackFramesToSkip): string
    {
        $callerInfo = DbgUtil::getCallerInfoFromStackTrace($numberOfStackFramesToSkip + 1);

        $classMethodPart = '';
        if ($callerInfo->class !== null) {
            $classMethodPart .= $callerInfo->class . '::';
        }
        Assert::assertNotNull($callerInfo->function);
        $classMethodPart .= $callerInfo->function;

        $fileLinePart = '';
        if ($callerInfo->file !== null) {
            $fileLinePart .= '[';
            $fileLinePart .= $callerInfo->file;
            $fileLinePart .= TextUtil::combineWithSeparatorIfNotEmpty(':', TextUtil::emptyIfNull($callerInfo->line));
            $fileLinePart .= ']';
        }

        return $classMethodPart . TextUtil::combineWithSeparatorIfNotEmpty(' ', $fileLinePart);
    }

    public function toLog(LogStreamInterface $stream): void
    {
        $stream->write(['subScopesStack count' => count($this->subScopesStack)]);
    }
}
