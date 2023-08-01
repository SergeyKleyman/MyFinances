<?php

declare(strict_types=1);

namespace MyFinances\Util\Log;

use MyFinances\Util\SingletonInstanceTrait;

final class StdErrWriter
{
    use SingletonInstanceTrait;

    private bool $isEnabled;

    protected function __construct()
    {
        if (!defined('STDERR')) {
            define('STDERR', fopen('php://stderr', 'w'));
        }
        $this->isEnabled = defined('STDERR');
    }

    public function write(string $text): void
    {
        if (!$this->isEnabled) {
            return;
        }

        fwrite(STDERR, $text);
    }
}
