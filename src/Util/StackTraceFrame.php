<?php

declare(strict_types=1);

namespace MyFinances\Util;

final class StackTraceFrame
{
    public ?string $file = null;

    public ?int $line = null;

    /** @var ?class-string */
    public ?string $class = null;

    public ?string $function = null;

    public ?bool $isStatic = null;

    public ?object $thisObj = null;

    /** @var null|(mixed[]) */
    public ?array $args = null;

    /**
     * @param ?class-string $class
     * @param null|(mixed[]) $args
     */
    public function __construct(?string $file = null, ?int $line = null, ?string $class = null, ?bool $isStatic = null, ?string $function = null, ?object $thisObj = null, ?array $args = null)
    {
        $this->file = $file;
        $this->line = $line;
        $this->class = $class;
        $this->function = $function;
        $this->isStatic = $isStatic;
        $this->thisObj = $thisObj;
        $this->args = $args;
    }
}
