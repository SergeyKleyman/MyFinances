<?php

/** @noinspection PhpUndefinedClassInspection */

declare(strict_types=1);

namespace MyFinances\Util\Log;

use MyFinances\Util\OsUtil;
use MyFinances\Util\SingletonInstanceTrait;
use Throwable;

final class OutputDebugStringWriter
{
    use SingletonInstanceTrait;

    /**
     * @noinspection RedundantSuppression, PhpFullyQualifiedNameUsageInspection, PhpUndefinedClassInspection
     * @phpstan-ignore-next-line
     */
    private ?\FFI $ffi;

    public function __construct()
    {
        $this->ffi = self::tryToInit();
    }

    private static function tryToInit(): ?\FFI
    {
        if (!OsUtil::isWindows()) {
            return null;
        }

        try {
            /**
             * @noinspection RedundantSuppression, PhpFullyQualifiedNameUsageInspection, PhpUndefinedClassInspection
             * @phpstan-ignore-next-line
             */
            return \FFI::cdef('void OutputDebugStringA( const char* test );', 'Kernel32.dll');
        } catch (Throwable $throwable) {
            /** @noinspection SpellCheckingInspection */
            StdErrWriter::singletonInstance()->write('FFI::cdef() failed. ' . LoggableToString::convert(['throwable' => $throwable]));
            return null;
        }
    }

    public function write(string $text): void
    {
        if ($this->ffi === null) {
            return;
        }

        /**
         * @noinspection RedundantSuppression, PhpUndefinedMethodInspection
         * @phpstan-ignore-next-line
         */
        $this->ffi->OutputDebugStringA($text);
    }
}
