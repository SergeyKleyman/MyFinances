<?php

declare(strict_types=1);

namespace MyFinances\Util\Log;

final readonly class Record
{
    /**
     * @param array<array-key, mixed> $context
     */
    public function __construct(
        public Level $level,
        public string $message,
        public array $context,
        public string $srcCodeNamespace,
        public ?string $srcCodeClass,
        public string $srcCodeFunc,
        public string $srcCodeFile,
        public int $srcCodeLine,
    ) {
    }
}
