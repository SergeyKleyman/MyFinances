<?php

declare(strict_types=1);

namespace MyFinances\Util;

use MyFinances\Util\Log\LoggableInterface;
use MyFinances\Util\Log\LoggableTrait;
use MyFinances\Util\Log\LogStackTraceUtil;

final class CallerInfo implements LoggableInterface
{
    use LoggableTrait;

    public string|null $file;

    public int|null $line;

    public string|null $class;

    public string|null $function;

    public function __construct(?string $file, ?int $line, ?string $class, ?string $function)
    {
        $this->file =  LogStackTraceUtil::adaptSourceCodeFilePath($file);
        $this->line = $line;
        $this->class = $class;
        $this->function = $function;
    }
}
