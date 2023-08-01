<?php

declare(strict_types=1);

namespace MyFinances\Util;

trait StaticClassTrait
{
    /**
     * Constructor is hidden because it's a "static" class
     */
    use HiddenConstructorTrait;
}
