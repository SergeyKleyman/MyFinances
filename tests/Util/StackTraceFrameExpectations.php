<?php

declare(strict_types=1);

namespace MyFinancesTests\Util;

use MyFinances\Util\Log\ContextStack;
use MyFinances\Util\StackTraceFrame;

final class StackTraceFrameExpectations extends ExpectationsBase
{
    /** @var Optional<?string> */
    public Optional $file;

    /** @var Optional<?int> */
    public Optional $line;

    /** @var Optional<?string> */
    public Optional $class;

    /** @var Optional<?bool> */
    public Optional $isStatic;

    /** @var Optional<?string> */
    public Optional $function;

    public function __construct()
    {
        $this->file = new Optional();
        $this->line = new Optional();
        $this->class = new Optional();
        $this->isStatic = new Optional();
        $this->function = new Optional();
    }

    public static function fromFrame(StackTraceFrame $frame): self
    {
        $result = new self();
        $result->file->setValue($frame->file);
        $result->line->setValue($frame->line);
        $result->class->setValue($frame->class);
        $result->isStatic->setValue($frame->isStatic);
        $result->function->setValue($frame->function);
        return $result;
    }

    public static function fromClassMethodUnknownLocation(string $class, bool $isStatic, string $method): self
    {
        $result = new self();
        $result->class->setValue($class);
        $result->function->setValue($method);
        $result->isStatic->setValue($isStatic);
        return $result;
    }

    public static function fromClassMethodUnknownLine(string $file, string $class, bool $isStatic, string $method): self
    {
        $result = self::fromClassMethodUnknownLocation($class, $isStatic, $method);
        $result->file->setValue($file);
        return $result;
    }

    public static function fromClassMethod(string $file, int $line, string $class, bool $isStatic, string $method): self
    {
        $result = self::fromClassMethodUnknownLine($file, $class, $isStatic, $method);
        $result->line->setValue($line);
        return $result;
    }

    public static function fromClassMethodNoLocation(string $class, bool $isStatic, string $method): self
    {
        $result = self::fromClassMethodUnknownLocation($class, $isStatic, $method);
        $result->file->setValue(null);
        $result->line->setValue(null);
        return $result;
    }

    public static function fromStandaloneFunction(string $file, int $line, string $function): self
    {
        $result = new self();
        $result->file->setValue($file);
        $result->line->setValue($line);
        $result->class->setValue(null);
        $result->isStatic->setValue(null);
        $result->function->setValue($function);
        return $result;
    }

    public static function fromClosure(string $file, int $line, ?string $namespace, string $class, bool $isStatic): self
    {
        return self::fromClassMethod($file, $line, $class, $isStatic, method: ($namespace === null ? '' : ($namespace . '\\')) . '{closure}');
    }

    public static function fromLocationOnly(string $file, int $line): self
    {
        $result = new self();
        $result->file->setValue($file);
        $result->line->setValue($line);
        $result->class->setValue(null);
        $result->isStatic->setValue(null);
        $result->function->setValue(null);
        return $result;
    }

    public function assertMatches(StackTraceFrame $actual): void
    {
        ContextStack::newScope(/* out */ $logCtx, ContextStack::funcArgs());
        $logCtx->add(['this' => $this]);

        TestCaseBase::assertSameExpectedOptional($this->file, $actual->file);
        TestCaseBase::assertSameExpectedOptional($this->line, $actual->line);
        TestCaseBase::assertSameExpectedOptional($this->class, $actual->class);
        TestCaseBase::assertSameExpectedOptional($this->isStatic, $actual->isStatic);
        TestCaseBase::assertSameExpectedOptional($this->function, $actual->function);
    }
}
