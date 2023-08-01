<?php

declare(strict_types=1);

namespace MyFinances\Util;

trait HiddenConstructorTrait
{
    /**
     * Constructor is hidden
     *
     * @noinspection PhpUnused
     */
    private function __construct()
    {
        /**
         * Empty body since it should never be called
         */
    }
}
