<?php

declare(strict_types=1);

namespace MyFinances\Util\Log;

final class EnabledProxy
{
    private bool $includeStackTrace = false;

    public function __construct(
        public readonly Logger $logger,
        public readonly Level $statementLevel,
    ) {
    }

    public function includeStackTrace(bool $includeStackTrace = true): self
    {
        $this->includeStackTrace = $includeStackTrace;
        return $this;
    }

    /**
     * @param array<array-key, mixed> $context
     */
    public function log(int $srcCodeLine, string $message, array $context = []): void
    {
        if (($this->includeStackTrace || $this->statementLevel->isBelowThreshold(Level::error)) && !array_key_exists(LogStackTraceUtil::STACK_TRACE_KEY, $context)) {
            $context[LogStackTraceUtil::STACK_TRACE_KEY] = LogStackTraceUtil::buildForCurrent(numberOfStackFramesToSkip: 1);
        }

        $statement = new Record(
            level: $this->statementLevel,
            message: $message,
            context: $context,
            srcCodeNamespace: $this->logger->srcCodeNamespace,
            srcCodeClass: $this->logger->srcCodeClass,
            srcCodeFunc: $this->logger->srcCodeFunc,
            srcCodeFile: $this->logger->srcCodeFile,
            srcCodeLine: $srcCodeLine,
        );
        Backend::singletonInstance()->log($statement);
    }
}
